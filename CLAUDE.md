# mobo-reseller

B2B gift-card reseller portal. Resellers buy Bamboo gift cards at a per-user marked-up price and pay
from an internal wallet. Laravel 12 + Livewire/Volt + Flux Pro, SQL (MySQL in production) plus MongoDB.

Full domain write-up, data model, flows, known defects, and open questions: @docs/business-logic.md

## Rules
- @.claude/rules/workflow.md : plan before any change (hard rule), output modes, delivery format.
- @.claude/rules/engineering.md : standards, research order, definition of done.
- `.claude/rules/laravel.md` : Laravel conventions for this repo (loaded when you touch PHP/Blade files).

## Skills (`.claude/skills/`)
`caveman` (chat voice, default on, full), `absolute-mode`, `stop-slop` (persisted prose).
Provenance and pinned commits: `.claude/skills/SOURCES.md`.

## Commands
| Task | Command |
|---|---|
| Install | `composer install && npm install` (needs Flux Pro credentials in `auth.json`) |
| Dev server | `composer run dev` (serve + queue + pail + vite) |
| Tests | `php artisan test` or `vendor/bin/pest --filter=...` |
| Format | `vendor/bin/pint` (CI: `vendor/bin/pint` on PHP 8.4) |
| Catalog sync | `php artisan bamboo:fetch-catalog` |

Required env beyond `.env.example`: `MONGODB_URI`, `MONGODB_DATABASE`, Bamboo SDK credentials.
Never commit `.env` or `auth.json` (both gitignored).

## Map
- `app/Services/` pricing (`FeeCalculatorService`), cart (`CartService`), FX (`ExchangeService`), catalog, suppliers.
- `app/Enums/` statuses and `SuppliersEnum` (supplier -> catalog strategy).
- `app/Console/Commands/FetchBambooCatalog.php` catalog import and product normalization.
- `resources/views/livewire/` Volt pages: `orders/`, `wallets/`, `admin/`, `developers/`, `settings/`, `auth/`.
- `routes/web.php` all app routes; `routes/api.php` exists but is not registered.
