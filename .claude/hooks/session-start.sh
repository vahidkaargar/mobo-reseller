#!/usr/bin/env bash
# Installs PHP/JS dependencies in Claude Code cloud sessions so tests, Pint, and Laravel Boost work.
# Non-fatal: a failed step prints a warning and the session still starts.
set -uo pipefail

[ "${CLAUDE_CODE_REMOTE:-}" = "true" ] || exit 0
cd "${CLAUDE_PROJECT_DIR:-$(pwd)}" || exit 0

export COMPOSER_ALLOW_SUPERUSER=1

# Flux Pro credentials come from environment secrets; auth.json is gitignored.
if [ ! -f auth.json ] && [ -n "${FLUX_USERNAME:-}" ] && [ -n "${FLUX_LICENSE_KEY:-}" ]; then
  composer config http-basic.composer.fluxui.dev "$FLUX_USERNAME" "$FLUX_LICENSE_KEY" >/dev/null 2>&1
fi

if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist --no-progress -q \
    || echo "session-start: composer install failed (is composer.fluxui.dev allowed and are Flux credentials set?)" >&2
fi

if [ ! -d node_modules ]; then
  npm install --no-audit --no-fund --silent || echo "session-start: npm install failed" >&2
fi

if [ -d vendor ] && [ ! -f .env ]; then
  cp .env.example .env && php artisan key:generate --ansi -q
fi

exit 0
