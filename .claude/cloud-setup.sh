#!/usr/bin/env bash
# Paste into: Claude Code cloud environment -> Edit -> Setup script.
# Needs these hosts in Network access -> Allowed domains (keep the default package-manager list):
#   ppa.launchpadcontent.net, keyserver.ubuntu.com, pecl.php.net, composer.fluxui.dev
# Needs environment secrets: FLUX_USERNAME, FLUX_LICENSE_KEY
# Why: composer.lock pins Symfony 8 (PHP >= 8.4) and mongodb/mongodb 2.x (ext-mongodb ^2.1);
#      Ubuntu 24.04 ships PHP 8.3 and ext-mongodb 1.15.
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -y -q software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update -q
apt-get install -y -q php8.4-cli php8.4-dev php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip \
  php8.4-sqlite3 php8.4-mysql php8.4-bcmath php8.4-intl php8.4-imagick php-pear
update-alternatives --set php /usr/bin/php8.4

pecl install -f mongodb
echo "extension=mongodb.so" > /etc/php/8.4/mods-available/mongodb.ini
phpenmod -v 8.4 mongodb

php -v && php -m | grep -E 'mongodb|imagick'
