# Blockers and owner actions

Items that code changes cannot resolve. Each needs the repository owner. Last updated 2026-10-04 after merging the
first ten PRs into `main`.

| # | Blocker | What it blocks | Action |
|---|---|---|---|
| 1 | `FLUX_USERNAME` and `FLUX_LICENSE_KEY` resolve to empty in the GitHub `Testing` environment | Every CI run: `composer install` gets HTTP 401 downloading `livewire/flux-pro` | GitHub → Settings → Environments → `Testing` → add both secrets (or repository secrets), then re-run the workflows on `main` |
| 2 | Cloud sessions cannot reach `composer.fluxui.dev` | Flux Pro in Claude Code cloud sessions; the 17 tests that render Flux Pro components (`flux:chart`, `flux:date-picker`, listbox select) | Cloud environment → Edit → Network access → Custom → add `composer.fluxui.dev`; set `FLUX_USERNAME` / `FLUX_LICENSE_KEY` as environment secrets; paste `.claude/cloud-setup.sh` as the setup script |
| 3 | Bamboo order API response shape is unverified: `status`, `items[].productId`, `items[].cards`, and whether `GET orders/{id}` accepts our RequestId | Enabling checkout fulfilment (`SyncSupplierOrder`). A wrong assumption leaves orders PROCESSING or PARTIAL_FAILED with funds locked; nobody is charged | Place one test order against the Bamboo sandbox and compare the JSON with the parsing in `app/Jobs/SyncSupplierOrder.php` |
| 4 | `BAMBOO_ACCOUNT_ID` is not set | Every order fails immediately in `PlaceSupplierOrder` and its funds are released | Set it in `.env` (see `config/services.php`) |
| 5 | FX rate direction unknown: is `exchange_currencies.rates[X]` "X per 1 USD" or "USD per 1 X"? | Correct cart and checkout prices for non-USD products. `exchange()` multiplies by the rate | Confirm against `https://mobo.gifts/api/bamboo/exchange?base=usd`; if rates are X per USD, `exchange()` must divide |
| 6 | Wallet credit cannot fund orders: the wallet package validates locks against balance only | Credit-funded orders | Decide: keep lock-then-charge (current), or switch checkout to charge-now / refund-on-failure so credit applies |
| 7 | Production `users` schema unknown: the original migration declared `fee_percentage decimal(2,2) default 5`, which MySQL strict mode rejects at table creation | Safe deploy of migration `widen_fee_percentage_columns` | Run `SHOW CREATE TABLE users` on production before `php artisan migrate` |
| 8 | No user holds the `admin` role after deploy | Access to `/admin/*` (everyone gets 403) | After `php artisan migrate`: `php artisan app:grant-admin-role <owner email>` |
| 9 | Lint workflow runs Pint in fix mode, so style drift never fails CI | Style gate | Change `.github/workflows/lint.yml` to `vendor/bin/pint --test` (the repo is Pint-clean on `main`) |
| 10 | Merged branches `claude/*` still exist on the remote | Repository tidiness only | Delete them on GitHub once you no longer need the closed PRs' diffs |

## Decisions already taken (change if you disagree)

| Decision | Where |
|---|---|
| MongoDB MCP server is read-only | `.mcp.json` |
| Admin resolution of unsettled orders is Release or Charge-in-full; no partial charge | `OrderResolutionService`, `/admin/orders` |
| Checkout locks funds and charges only on confirmed delivery | `CheckoutService`, `SyncSupplierOrder` |
| A missing FX rate throws instead of pricing at 0 | `exchange()` helper |
| Larastan baseline keeps 13 pre-existing findings; new code must add none | `phpstan-baseline.neon` |
