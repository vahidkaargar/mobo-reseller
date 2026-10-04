# Business logic: mobo-reseller

A B2B gift-card reseller portal. Resellers buy digital gift cards sourced from the Bamboo
supplier (through the `mobo.gifts` proxy and the `vahidkaargar/bamboo-card-portal` SDK).
Each reseller sees prices marked up by a per-user fee. Resellers pay from an internal wallet.
Admins manage users, fees, and the brand catalog.

Status as of 2026-10-04: catalog, pricing, cart, wallets, API tokens, and admin fee management work.
Checkout, order creation, and Bamboo order placement are not built yet.

## Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12 (`laravel/framework` v12.56), PHP ^8.2 (CI uses 8.4) |
| UI | Livewire 3 + Volt class-based single-file components, Flux + Flux Pro 2.x, Tailwind v4, Vite |
| Auth | Fortify (login, 2FA, password reset, email verification), Sanctum (API tokens) |
| Authorization | Laratrust 8 (`roles`, `permissions` tables). Installed, not enforced yet |
| Money | `vahidkaargar/laravel-wallet` ^0.4 (trait `HasWallets` on `User`) |
| Supplier SDK | `vahidkaargar/bamboo-card-portal` ^1.0 (`bamboo()` helper) |
| Other | `mongodb/laravel-mongodb` ^5.4, `intervention/image` ^3 (Imagick), `pragmarx/countries` |
| Tests / lint | Pest 3, Pint |

## Data model

### SQL (default connection; production is MySQL, `.env.example` uses sqlite)

| Table | Purpose | Notes |
|---|---|---|
| `users` | Resellers and admins | Extra columns: `can_place_order` (bool, 0), `fee_percentage` decimal(5,2) default 5, `has_api` (bool, 0), `is_active` (bool, 1), Fortify 2FA columns |
| `wallets` | One row per user wallet | `slug` unique per user, `currency`, `balance`, `credit`, `locked` decimal(15,2), `is_active` |
| `wallet_transactions` | Ledger | ULID id; `type` deposit/withdraw/lock/unlock/credit_grant/credit_revoke/credit_repay/interest_charge; `status` pending/approved/rejected/reversed; `reference`, `meta` json |
| `orders` | Customer order | id starts at 1000; `user_id`, `wallet_id`, `status` (`OrderStatusEnum`), `purchase_amount`, `sale_amount`, `paid_at`, `completed_at` (both NOT NULL today) |
| `order_items` | Line items | `supplier` (`SuppliersEnum`), `relation` json (supplier ids), `quantity`, `purchase_amount`, `profit_percentage`, `sale_amount`, `cards` (`encrypted:array`, the card codes) |
| `product_fees` | Per-user, per-product fee override | unique (`user_id`, `supplier_name`, `supplier_id`); `fee_percentage` decimal(5,2) |
| `personal_access_tokens` | Sanctum | |
| `roles`, `permissions`, `role_user`, `permission_user`, `permission_role` | Laratrust | soft deletes |

### MongoDB (`mongodb` connection)

| Collection | Model | Shape |
|---|---|---|
| `bamboo_brands` | `BambooBrand` | `{brand_id (unique), name, country, currency, image, products: [normalized product]}` |
| `cart` | `Cart` | `{user_id (unique), items: {<key>: {supplier, product_id, purchase, amount, quantity}}}` |
| `exchange_currencies` | `ExchangeCurrency` | `{currency (unique, lowercase base), rates: {<CODE>: float}}` |

## Domain flows

### 1. Catalog sync (`php artisan bamboo:fetch-catalog`)
`app/Console/Commands/FetchBambooCatalog.php`

1. The command loads the v1 catalog from `storage/app/catalog.json` if that file is less than 60 minutes old;
   otherwise it calls `bamboo()->catalogs()->get()` and rewrites the file.
2. It skips brands with no products and logs them to the `bamboo` log channel (`storage/logs/bamboo.log`).
3. It normalizes each product:
   - `fixed` = `minFaceValue === maxFaceValue`.
   - `purchase` = face value side: `{fixed, min, max, currency = brand currency}`.
   - `sale` = cost side: `{fixed, min, max, currency = price.currencyCode, unit}`.
     `unit` = `price.min` for fixed products, else `price.min / minFaceValue` (cost per 1 unit of face value).
   - `inventory` = `count`, `country` = brand country.
   - `discount` = `100 - (sale.min / purchase.min * 100)` when currencies match. When they differ, the command
     fetches the brand from the v2 catalog in the brand currency (sleeping 5 s before each call) and uses
     `price.min / minFaceValue` from v2. A product missing from v2 gets discount 0 and an error log entry.
4. It upserts the document by `brand_id` (`firstOrCreate` + `fill` + `save`).

There is no scheduler entry for this command (`routes/console.php` only has `inspire`).

A second, older path exists: `BrandIntegrationService` + `SupplierApiFactory` + `BrandAdapterFactory`
(`app/Services/Suppliers/*`) fetches brands from `https://mobo.gifts/api/bamboo/brands`. No caller uses it today.

### 2. Supplier abstraction
- `SuppliersEnum` (only `BAMBOO`) maps to a `CatalogInterface` via `->catalog()`.
- `CatalogInterface`: `categories()` (brands), `products($brandId)`, `product($productId)`.
- `BambooCatalog` reads `bamboo_brands`. A "category" in the UI is a brand; the select value is `"<SUPPLIER>|<brand_id>"`.

### 3. Pricing
For one item:

```
base  = sale.fixed ? sale.unit : sale.unit * purchase_face_value
total = base * quantity
priced = add_percent(total, fee%)      // FeeCalculator
usd    = exchange(priced, sale.currency) // ExchangeService rates, base USD, rounded to 4 dp
```

- **Fee resolution** (`FeeCalculatorService::fee()`): use the `product_fees` row for (user, supplier, product id)
  if it exists; otherwise use `users.fee_percentage`. Admin UI accepts 0.01 to 9.99; an empty/zero value deletes the override.
- **FX** (`ExchangeService::rates($base)`): reads `exchange_currencies.rates` for the base (default `usd`).
  At most once per 120 s (cache lock) it dispatches a queued closure that refreshes rates from
  `https://mobo.gifts/api/bamboo/exchange?base=<base>` (`body.rates[].{currencyCode,value}`).
  `exchange()` multiplies the amount by `rates[fromCurrency]`. The direction of that rate is unconfirmed (see Open questions).
- Displayed prices on the order page use the same formula per unit (`orders/create.blade.php`).

### 4. Cart (`CartService`, facade `Cart`)
- One Mongo document per user. Item key = `md5(base64(supplier . productId . purchaseFaceValue))`,
  so the same product with a different face value is a separate line.
- `add()` overwrites the line (it does not increment) and clamps quantity to at least 1. UI validates quantity 1 to 99
  and the face value between product `purchase.min` and `purchase.max`.
- `items()` recomputes `amount` (USD, fee included) from the live catalog and fees on every read. Cart amounts
  are never stored as truth.

### 5. Wallets (`vahidkaargar/laravel-wallet`)
- Methods on `User`: `createWallet`, `getWallet($slug)`, `deposit`, `withdraw`, `lockFunds`, `unlockFunds`,
  `grantCredit`, `revokeCredit`, plus `autoApprove: false` for pending transactions.
- `available_funds` is computed by the package (balance and credit, minus locked; see the package source in `vendor/`).
- Config `config/wallet.php`: max transaction 999,999.99; max credit 100,000; interest 5%; auto-approve on;
  pending timeout 60 min; supported currencies USD, EUR, GBP, JPY, CAD, AUD, CHF, CNY.
- `wallets/index` shows the balance and a paginated, filterable ledger. The "Charge" button does nothing yet.

### 6. Orders (planned, not built)
- `OrderStatusEnum`: CREATED -> PROCESSING -> SUCCEEDED | FAILED | PARTIAL_FAILED.
- `BambooOrderStatusEnum` mirrors Bamboo statuses: Created, Processed, Pending, Succeeded, Failed, PartialFailed.
- The checkout button in the cart flyout has no handler. The wallet select is a hard-coded "USDT" option.
- `orders/index` and `transactions/index` render static sample rows.
- Intended flow (to confirm in the checkout plan): validate `can_place_order` and `is_active`, lock funds,
  create the Order and OrderItems, place the Bamboo order, store encrypted card codes, then withdraw on
  success or unlock on failure, and empty the cart.

### 7. Users, admin, API
- Fortify login with rate limit 5/min per email+IP; 2FA challenge view; email verification is required for app routes.
- Admin pages (`/admin/users`, `/admin/users/{user}`, `/settings`, `/fees`, `/admin/orders`, `/admin/brands`)
  edit `fee_percentage`, `is_active`, `can_place_order`, `has_api`, and per-product fees.
- `developers/tokens`: users with `has_api` can create Sanctum tokens (name must be alphanumeric, stored uppercased)
  and delete their own tokens. `routes/api.php` is not registered in `bootstrap/app.php`, so no API route is live.
- `/thumbnail?url=&w=&h=&q=&fit=` is public. It downloads the URL, caches it under `storage/app/images/cache`,
  and returns WebP.

## Known defects (not fixed; each gets its own plan and PR)

| # | Severity | Location | Issue |
|---|---|---|---|
| 1 | High, security | `routes/web.php` admin group `middleware([])` | Any verified user can open `/admin/*` and change fees, flags, and API access. Fix plan: Laratrust `role:admin`. |
| 2 | High, security | `ThumbnailController` | Unauthenticated SSRF: fetches any URL server-side. |
| 3 | Fixed | `users.fee_percentage`, `product_fees.fee_percentage` | Were decimal(2,2) (max 0.99) while default is 5 and the admin range is up to 9.99. Widened to decimal(5,2). |
| 4 | Medium | whole app | `is_active` and `can_place_order` are never enforced. |
| 5 | Medium | `AppServiceProvider` | `CartService` and `FeeCalculatorService` are singletons capturing `auth()->user()` at first resolve: stale in queue workers / Octane, null when unauthenticated. |
| 6 | Medium | `ExchangeService::rates()` | Returns null before the first refresh but declares `array` (TypeError). The first page load after deploy fails. |
| 7 | Low | `orders` migration | `paid_at` and `completed_at` are NOT NULL; a CREATED order has neither. |
| 8 | Low | `FeeCalculatorService::currency()` | Writes an undeclared property (deprecated since PHP 8.2). |
| 9 | Low | `orders/index`, `transactions/index`, `admin/users/show` chart | Static mock data. |

## Open questions

| # | Question | Status |
|---|---|---|
| 1 | Does `exchange_currencies.rates[X]` mean X per 1 USD, or USD per 1 X? If X per USD, `exchange()` must divide, not multiply. | Unknown. Do not change until confirmed. |
| 2 | Checkout details: lock-then-withdraw vs. direct withdraw; partial failure refunds; which wallet currency. | To settle in the checkout plan. |
