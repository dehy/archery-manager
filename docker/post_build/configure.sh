#!/bin/bash

# configure.sh — production asset compilation for the 'builder' Docker stage.
# Node.js, Composer, and native build deps are pre-installed at this point.
# All app commands run as the symfony user via setpriv; root is only used for
# directory setup and cleanup.

set -eux

SETPRIV=(setpriv --reuid=symfony --regid=symfony --init-groups --)
export HOME=/app APP_ENV=prod

cd /app

# Use the same PHP INI for CLI as for FPM
ln -sf /etc/php/8.4/fpm/php.ini /etc/php/8.4/cli/php.ini

# Ensure build output directories exist and are writable by symfony
# HOME=/app, so npm's cache lands at /app/.npm — pre-create it
mkdir -p /app/{vendor,node_modules,.npm,public/build,public/bundles,var/log,var/cache}
chown -R symfony:symfony /app/{vendor,node_modules,.npm,public/build,public/bundles,var}

# PHP: install production deps and optimise the autoloader
"${SETPRIV[@]}" /usr/local/bin/composer install \
    --prefer-dist --no-interaction --no-dev --optimize-autoloader
"${SETPRIV[@]}" /usr/local/bin/composer dump-autoload \
    --no-dev --classmap-authoritative
"${SETPRIV[@]}" /usr/local/bin/composer clear-cache --no-interaction

# JS: build frontend assets
"${SETPRIV[@]}" /usr/bin/npm ci
"${SETPRIV[@]}" /usr/bin/npm run build

# Symfony: install public bundle assets as relative symlinks
"${SETPRIV[@]}" bin/console assets:install public --symlink --relative

# Tidy up artefacts that must not ship in the runtime image
# node_modules is symfony-owned but /app (parent) is root-owned, so only root
# can unlink the directory entry — run without setpriv.
rm -rf /app/node_modules /root/.npm /root/.cache