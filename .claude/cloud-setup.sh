#!/usr/bin/env bash
# Claude Code cloud environment setup script.
# Paste into: cloud environment menu -> Edit -> Setup script. Runs as root before the session starts.
#
# Why: composer.lock pins Symfony 8 (PHP >= 8.4) and mongodb/mongodb 2.x (ext-mongodb ^2.1).
# Ubuntu 24.04 ships PHP 8.3 and ext-mongodb 1.15, and the default network policy blocks
# ppa.launchpadcontent.net and pecl.php.net. This script only uses hosts the default policy allows:
#   pypi.org (py-rattler), conda.anaconda.org (PHP 8.5, MongoDB 8.0), github.com (mongo-php-driver source).
# Result: /usr/local/bin/php -> PHP 8.5 with ext-mongodb 2.1, and a local mongod on 127.0.0.1:27017.
set -euo pipefail

PHP_PREFIX=/opt/php85
MONGO_PREFIX=/opt/mongo
MONGO_DATA=/opt/mongo-data
MONGO_EXT_TAG=2.1.9

pip install -q py-rattler==0.27.0

python3 - "$PHP_PREFIX" "$MONGO_PREFIX" <<'EOF'
import asyncio, sys, rattler

async def env(spec, prefix):
    records = await rattler.solve(
        sources=["conda-forge"], specs=[spec], platforms=["linux-64", "noarch"],
        virtual_packages=rattler.VirtualPackage.detect(),
    )
    await rattler.install(records, target_prefix=prefix, show_progress=False)

asyncio.run(env("php 8.5.*", sys.argv[1]))
asyncio.run(env("mongodb 8.0.*", sys.argv[2]))
EOF

build_dir=$(mktemp -d)
git clone -q --depth 1 --branch "$MONGO_EXT_TAG" --recurse-submodules --shallow-submodules \
  https://github.com/mongodb/mongo-php-driver.git "$build_dir/mpd"
(
  cd "$build_dir/mpd"
  export PATH="$PHP_PREFIX/bin:$PATH"
  phpize >/dev/null
  ./configure --with-php-config="$PHP_PREFIX/bin/php-config" --with-mongodb-ssl=openssl >/dev/null
  make -j"$(nproc)" >/dev/null
  make install >/dev/null
)
rm -rf "$build_dir"

printf 'extension=mongodb\nmemory_limit=1G\n' > "$PHP_PREFIX/lib/php.ini"
ln -sf "$PHP_PREFIX/bin/php" /usr/local/bin/php

mkdir -p "$MONGO_DATA"
"$MONGO_PREFIX/bin/mongod" --dbpath "$MONGO_DATA" --bind_ip 127.0.0.1 --port 27017 \
  --fork --logpath "$MONGO_DATA/mongod.log"

php -v
php -r 'echo "ext-mongodb ", phpversion("mongodb"), PHP_EOL;'
