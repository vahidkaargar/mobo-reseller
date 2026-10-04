#!/usr/bin/env bash
# Prepares Claude Code cloud sessions so tests, Pint, and Laravel Boost work.
# Expects the runtime from .claude/cloud-setup.sh (PHP 8.5 + ext-mongodb, MongoDB 8.0 in /opt/mongo).
# Non-fatal: a failed step prints a warning and the session still starts.
set -uo pipefail

[ "${CLAUDE_CODE_REMOTE:-}" = "true" ] || exit 0
cd "${CLAUDE_PROJECT_DIR:-$(pwd)}" || exit 0

export COMPOSER_ALLOW_SUPERUSER=1
warn() { echo "session-start: $*" >&2; }

# MongoDB server (processes started by the setup script do not survive into the session).
if [ -x /opt/mongo/bin/mongod ] && ! pgrep -x mongod >/dev/null; then
  mkdir -p /opt/mongo-data
  /opt/mongo/bin/mongod --dbpath /opt/mongo-data --bind_ip 127.0.0.1 --port 27017 \
    --fork --logpath /opt/mongo-data/mongod.log >/dev/null || warn "mongod failed to start"
fi

# Flux Pro credentials come from environment secrets; auth.json is gitignored.
if [ ! -f auth.json ] && [ -n "${FLUX_USERNAME:-}" ] && [ -n "${FLUX_LICENSE_KEY:-}" ]; then
  composer config http-basic.composer.fluxui.dev "$FLUX_USERNAME" "$FLUX_LICENSE_KEY" >/dev/null 2>&1
fi

if [ ! -d vendor ]; then
  if ! composer install --no-interaction --prefer-dist --no-progress -q --ignore-platform-req=ext-imagick; then
    # Fallback when composer.fluxui.dev is unreachable: install every locked package except
    # livewire/flux-pro from untracked copies of composer.json/lock. Pages using Pro-only
    # components (charts, date picker) will not render, but services and most tests run.
    warn "full composer install failed; installing without livewire/flux-pro"
    python3 - <<'EOF'
import json
c = json.load(open('composer.json'))
c['require'].pop('livewire/flux-pro', None)
c.pop('repositories', None)
json.dump(c, open('composer.local.json', 'w'), indent=4)
lock = json.load(open('composer.lock'))
lock['packages'] = [p for p in lock['packages'] if p['name'] != 'livewire/flux-pro']
json.dump(lock, open('composer.local.lock', 'w'), indent=4)
EOF
    grep -qx 'composer.local.json' .git/info/exclude 2>/dev/null \
      || printf 'composer.local.json\ncomposer.local.lock\n' >> .git/info/exclude
    COMPOSER=composer.local.json composer install --no-interaction --prefer-dist --no-progress -q \
      --ignore-platform-req=ext-imagick --ignore-platform-req=php \
      || warn "composer install without flux-pro failed"
  fi
fi

if [ ! -d node_modules ]; then
  npm install --no-audit --no-fund --silent || warn "npm install failed"
  git checkout -- package-lock.json yarn.lock 2>/dev/null || true
fi

if [ -d vendor ] && [ ! -f .env ]; then
  cp .env.example .env && php artisan key:generate --ansi -q
fi

exit 0
