# Business logic: mobo-reseller

A B2B gift-card reseller portal. Resellers buy digital gift cards sourced from the Bamboo
supplier (through the `mobo.gifts` proxy and the `vahidkaargar/bamboo-card-portal` SDK).
Each reseller sees prices marked up by a per-user fee. Resellers pay from an internal wallet.
Admins manage users, fees, and the brand catalog.

Status as of 2026-10-04: catalog, pricing, cart, wallets, API tokens, admin fee management, and checkout
(order creation, wallet reservation, Bamboo placement and polling) are built. Bamboo fulfilment is untested against the real API.

## Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12 (`laravel/framework` v12.56), PHP ^8.2 (CI uses 8.4) |
| UI | Livewire 3 + Volt class-based single-file components, Flux + Flux Pro 2.x, Tailwind v4, Vite |
| Auth | Fortify (login, 2FA, password reset, email verification), Sanctum (API tokens) |
| Authorization | Laratrust 8 (`roles`, `permissions` tables). Role `admin` guards `/admin/*` |
| Money | `vahidkaargar/laravel-wallet` ^0.4 (trait `HasWallets` on `User`) |
| Supplier SDK | `vahidkaargar/bamboo-card-portal` ^1.0 (`bamboo()` helper) |
| Other | `mongodb/laravel-mongodb` ^5.4, `intervention/image` ^3 (Imagick), `pragmarx/countries` |
| Tests / lint | Pest 3, Pint |

## Data model

### SQL (default connection; production is MySQL, `.env.example` uses sqlite)

| Table | Purpose | Notes |
|---|---|---|
| `users` | Resellers and admins | Extra columns: `can_place_order` (bool, 0), `fee_percentage` decimal(2,2) default 5, `has_api` (bool, 0), `is_active` (bool, 1), Fortify 2FA columns |
| `wallets` | One row per user wallet | `slug` unique per user, `currency`, `balance`, `credit`, `locked` decimal(15,2), `is_active` |
| `wallet_transactions` | Ledger | ULID id; `type` deposit/withdraw/lock/unlock/credit_grant/credit_revoke/credit_repay/interest_charge; `status` pending/approved/rejected/reversed; `reference`, `meta` json |
| `orders` | Customer order | id starts at 1000; `user_id`, `wallet_id`, `status` (`OrderStatusEnum`), `purchase_amount`, `sale_amount`, `paid_at`, `completed_at` (both NOT NULL today) |
| `order_items` | Line items | `supplier` (`SuppliersEnum`), `relation` json (supplier ids), `quantity`, `purchase_amount`, `profit_percentage`, `sale_amount`, `cards` (`encrypted:array`, the card codes) |
| `product_fees` | Per-user, per-product fee override | unique (`user_id`, `supplier_name`, `supplier_id`); `fee_percentage` decimal(2,2) |
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
  `exchange()` multiplies the amount by `rates[fromCurrency]`; same-currency amounts pass through, a missing or zero
  rate throws `RuntimeException` (never a silent 0 price). The direction of the rate is unconfirmed (see Open questions).
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

### 6. Orders and checkout
`app/Services/CheckoutService.php`, `app/Jobs/PlaceSupplierOrder.php`, `app/Jobs/SyncSupplierOrder.php`

- `OrderStatusEnum`: CREATED -> PROCESSING -> SUCCEEDED | FAILED | PARTIAL_FAILED.
- `BambooOrderStatusEnum` mirrors Bamboo statuses: Created, Processed, Pending, Succeeded, Failed, PartialFailed.
- **Checkout** (cart flyout, "Checkout" button, active USD wallets only):
  1. Refuses inactive users, users without `can_place_order`, an empty cart, non-integer face values,
     a missing/inactive/non-USD wallet. One checkout per user at a time (cache lock).
  2. Prices every line with `PricingService` (same formula as the cart): `purchase_amount` = supplier cost in USD,
     `sale_amount` = cost + fee in USD, rounded to cents; `profit_percentage` = the fee used.
  3. In one DB transaction: creates the Order (CREATED) and OrderItems (`relation` = product_id, face_value,
     face_currency) and **locks** `sale_amount` in the wallet (reference `order:{id}`). Then empties the cart and
     queues `PlaceSupplierOrder` after commit.
- **PlaceSupplierOrder** (runs once): needs `BAMBOO_ACCOUNT_ID`. Marks PROCESSING, calls Bamboo
  `orders/checkout` with RequestId `mobo-order-{id}`, then queues `SyncSupplierOrder`. If it fails before the
  order is marked PROCESSING, nothing reached Bamboo, so `failed()` unlocks the funds and marks FAILED.
- **SyncSupplierOrder** (every 30 s, up to 40 attempts): `GET orders/{requestId}`.
  - `Succeeded` and every line has at least `quantity` cards: store cards (encrypted), unlock + withdraw, SUCCEEDED.
  - `Failed`: unlock, FAILED.
  - `PartialFailed`, or `Succeeded` with missing cards: store what arrived, keep funds locked, PARTIAL_FAILED.
  - Anything else: poll again; after 40 attempts the order stays PROCESSING with funds locked.
  - Orders left locked are logged to the `bamboo` channel and listed on `/admin/orders` ("Needs attention only").
    `OrderResolutionService` lets an admin **Release** (unlock, FAILED) or **Charge** (unlock + withdraw the full
    sale amount, SUCCEEDED) a PROCESSING or PARTIAL_FAILED order; both are logged with the admin id. There is no
    partial charge: for a partial delivery the admin picks one of the two and settles the difference outside the app.
- Assumed Bamboo response shape (unverified, no sample available): `{status, items: [{productId, cards: [...]}]}`.
  A wrong guess cannot move money: it leaves the order PROCESSING or PARTIAL_FAILED with funds locked.
- Lock-then-charge means the purchase must fit in `balance - locked`. Wallet **credit is not usable** for
  orders, because the wallet package validates locks against balance only.
- `orders/index` lists the user's orders with status and date-range filters.
- `/admin/orders` lists every order with search (order id, customer name/email), status filter and the resolution actions above.
- `/admin/users/{user}` shows real totals (sales, orders, cards sold, USD balance) and orders per day for the last 15 days.

### 7. Users, admin, API
- Fortify login with rate limit 5/min per email+IP; 2FA challenge view; email verification is required for app routes.
- Admin pages (`/admin/users`, `/admin/users/{user}`, `/settings`, `/fees`, `/admin/orders`, `/admin/brands`)
  edit `fee_percentage`, `is_active`, `can_place_order`, `has_api`, and per-product fees.
- Admin access: Laratrust role `admin` (created by migration `insert_admin_role`). Grant with
  `php artisan app:grant-admin-role you@example.com` (`--revoke` to remove). The sidebar Admin group renders only for admins.
- `developers/tokens`: users with `has_api` can create Sanctum tokens (name must be alphanumeric, stored uppercased)
  and delete their own tokens. `routes/api.php` is not registered in `bootstrap/app.php`, so no API route is live.
- `/thumbnail?url=&w=&h=&q=&fit=` is public. It downloads the URL, caches it under `storage/app/images/cache`,
  and returns WebP.

## Known defects (not fixed; each gets its own plan and PR)

| # | Severity | Location | Issue |
|---|---|---|---|
| 1 | Fixed | `routes/web.php` admin group | Was `middleware([])`: any verified user could open `/admin/*`. Now `role:admin` (Laratrust), persistent on Livewire updates. |
| 2 | High, security | `ThumbnailController` | Unauthenticated SSRF: fetches any URL server-side. |
| 3 | High, data | `users.fee_percentage`, `product_fees.fee_percentage` decimal(2,2) | Max storable value is 0.99. Default 5 and admin range up to 9.99 overflow on MySQL strict mode. |
| 4 | Medium | whole app | `is_active` and `can_place_order` are never enforced. |
| 5 | Fixed | `AppServiceProvider` | `CartService` and `FeeCalculatorService` were singletons capturing `auth()->user()`; now `scoped()` (per request) and they throw a clear `RuntimeException` when unauthenticated. |
| 6 | Fixed | `ExchangeService::rates()`, `exchange()` | `rates()` returned null before the first refresh (TypeError) and `exchange()` priced at 0 when a rate was missing. Now `rates()` returns `[]`, same-currency conversions skip the lookup, and a missing rate throws. |
| 7 | Fixed | `orders` migration | `paid_at` and `completed_at` were NOT NULL; now nullable. |
| 8 | Fixed | `FeeCalculatorService::currency()` | Unused method writing an undeclared property; removed. Constructors no longer `return $this`. `SupplierApiFactory::create()` is static, matching its only caller. |
| 9 | Fixed | `orders/index`, `admin/users/show`, `admin/orders/index` | Static mock data replaced with real orders, stats and a 15-day chart; unrouted `transactions/*` and `wallets/create` views deleted; the missing `admin/orders/index` page now exists. |

## Open questions

| # | Question | Status |
|---|---|---|
| 1 | Does `exchange_currencies.rates[X]` mean X per 1 USD, or USD per 1 X? If X per USD, `exchange()` must divide, not multiply. | Unknown. Do not change until confirmed. |
| 2 | Should wallet credit fund orders? Lock-then-charge cannot use credit (package validates locks against balance). | Built as lock-then-charge; credit not usable. |
| 3 | Bamboo order response shape (`status`, `items[].productId`, `items[].cards`) and whether `GET orders/{id}` takes the RequestId. | Unverified. Test against the Bamboo sandbox before enabling. |
| 4 | Should a partial delivery allow a partial charge? | Built as Release or Charge-in-full on `/admin/orders`; partial charge not supported. |
