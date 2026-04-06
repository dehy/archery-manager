#!/bin/bash

set -eux

# Default to prod if BUILD_TARGET is not set
export APP_ENV=${BUILD_TARGET:-prod}
SETPRIV=(setpriv --reuid=symfony --regid=symfony --init-groups --)
export HOME=/app  # ensure symfony user's home is used for npm/composer caches

cd /app

echo ${VCS_REF:-""} > vcs_ref
echo ${BUILD_DATE:-""} > build_date
echo ${VERSION:-""} > version

# Make the PHP-FPM php.ini same as fpm
ln -sf /etc/php/8.4/fpm/php.ini /etc/php/8.4/cli/php.ini

mkdir -p /app/{node_modules,public/build,var/log,var/cache}
chown -R symfony: /app/{node_modules,public/build,var}

apt update
apt install -y build-essential libcairo2-dev libpango1.0-dev libjpeg-dev libgif-dev librsvg2-dev

if [ "${BUILD_TARGET}" = "prod" ]; then
    # Production build: install composer deps without dev, run npm build
    "${SETPRIV[@]}" /usr/local/bin/composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader
    "${SETPRIV[@]}" /usr/local/bin/composer dump-autoload --no-dev --classmap-authoritative
    "${SETPRIV[@]}" /usr/local/bin/composer clear-cache --no-interaction

    "${SETPRIV[@]}" /usr/bin/npm ci
    "${SETPRIV[@]}" /usr/bin/npm run build
    "${SETPRIV[@]}" rm -rf /app/{node_modules,.bash*,.cache,.config,.local,.npm}
else
    # Development build: install composer deps with dev, skip npm install/build
    "${SETPRIV[@]}" /usr/local/bin/composer install --prefer-dist --no-interaction
    echo "Development build: skipping npm install/build (run 'make deps' or 'docker compose exec -u symfony -w /app app npm ci && docker compose exec -u symfony -w /app app npm run dev')"
fi

apt purge -y build-essential libcairo2-dev libpango1.0-dev libjpeg-dev libgif-dev librsvg2-dev
apt autoremove -y --purge

"${SETPRIV[@]}" bin/console assets:install public --symlink --relative

echo "export COMPOSER_MEMORY_LIMIT=-1" >> /app/.bashrc