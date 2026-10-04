# Engineering rules

## Roles
Apply the perspective the task needs: architecture (CTO), backend, frontend, full-stack, UI/UX.
For non-trivial decisions: name 2-3 options with a one-line tradeoff each, pick one, state why.

## Standards
- SOLID and patterns (Repository, Strategy, Factory) only where they earn their keep. No speculative abstraction.
- Security defaults: validate all inputs, parameterized queries, least privilege, explicit error handling, never emit secrets.
- Match existing conventions, libraries, and style before introducing anything new. Pin new dependency versions; flag suspicious packages.

## Research (only when the task needs external facts)
Triggers: version-specific behavior, breaking changes, unfamiliar library/API, conflicting documentation.
1. Library/framework/API questions: Laravel Boost `search-docs` MCP tool first (Laravel ecosystem), then the Context7 MCP
   server (`resolve-library-id` + `get-library-docs`, any library), then official docs.
   MongoDB schema or data questions: the read-only `mongodb` MCP server (`.mcp.json`), never ad-hoc shell queries.
2. Rank sources: official docs > the library's own source code (`vendor/`) > maintained repos > reputable engineering blogs. Prefer 2+ independent confirmations for load-bearing claims.
3. Thin evidence: give the best answer, label it "unverified, based on <source>", say what would confirm it. Never fabricate sources.

## Done means
- Gates pass on the changed code, in this order: `vendor/bin/pint` on changed files, `php -l`,
  `composer analyse` (Larastan; never add new errors to the baseline), `npx --yes jscpd@5.4.0`,
  `vendor/bin/pest tests/Unit/ArchTest.php`, then the Pest tests for the touched area.
  The pre-commit hook (`composer run hooks:install`) runs the same gates on staged files.
  Not available: Enlightn (no Laravel 12 support), archsniffer (no such package; use Pest `arch()`).
- Relevant Pest tests pass.
- Security impact stated (auth, money, card codes, PII).
- Sources cited when research was used.
- "Verified vs assumed" split stated.
