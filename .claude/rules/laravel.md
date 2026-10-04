---
paths:
  - "app/**/*.php"
  - "resources/views/**/*.blade.php"
  - "routes/**/*.php"
  - "database/**/*.php"
  - "config/**/*.php"
  - "tests/**/*.php"
---

# Laravel conventions for this repo

- Pages are Volt class-based single-file components in `resources/views/livewire/**`, routed with `Volt::route()`.
  New pages follow the same pattern. Put business rules in `app/Services/*`, not in Volt components.
- UI uses Flux / Flux Pro components (`<flux:*>`) and `Flux::toast()` for feedback. Tailwind v4.
- Two connections: SQL (default) for users, orders, wallets, fees, auth. MongoDB (`$connection = 'mongodb'`)
  for `bamboo_brands`, `cart`, `exchange_currencies`. Never join across them; resolve in PHP.
- Money: wallet operations go through the `vahidkaargar/laravel-wallet` API on `User`
  (`deposit`, `withdraw`, `lockFunds`, `unlockFunds`, `grantCredit`, ...). Never update `wallets` columns directly.
- Card codes (`order_items.cards`) are `encrypted:array`. Never log, dump, or return them outside the owner's order view.
- Suppliers: add a case to `SuppliersEnum` + a `CatalogInterface` implementation; add API/adapter via the factories.
- Enums live in `app/Enums` with `color()` for Flux badges.
- Tests: Pest (`tests/Feature`, `tests/Unit`). Run `php artisan test --filter=...` for the touched area.
- Format with `vendor/bin/pint` before committing (CI runs Pint on PHP 8.4).
- Use Laravel Boost MCP tools when available: `search-docs` before writing framework code,
  `database-schema` / `database-query` for schema facts, `tinker` for quick checks, `last-error` / `read-log-entries` for debugging.
