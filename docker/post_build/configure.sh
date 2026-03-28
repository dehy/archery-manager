#!/bin/bash

set -eux

# Default to prod if BUILD_TARGET is not set
export APP_ENV=${BUILD_TARGET:-prod}
GOSU="/usr/sbin/gosu symfony"

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
    ${GOSU} /usr/local/bin/composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader
    ${GOSU} /usr/local/bin/composer dump-autoload --no-dev --classmap-authoritative
    ${GOSU} /usr/local/bin/composer clear-cache --no-interaction

    ${GOSU} /usr/bin/npm ci
    ${GOSU} /usr/bin/npm run build
    ${GOSU} rm -rf /app/{node_modules,.bash*,.cache,.config,.local,.npm}
else
    # Development build: install composer deps with dev, skip npm (run on host)
    ${GOSU} /usr/local/bin/composer install --prefer-dist --no-interaction
    echo "Development build: skipping npm install/build (run 'npm ci' and 'npm run dev' on host)"
fi

apt purge -y build-essential libcairo2-dev libpango1.0-dev libjpeg-dev libgif-dev librsvg2-dev
apt autoremove -y --purge

${GOSU} bin/console assets:install public --symlink --relative

echo "export COMPOSER_MEMORY_LIMIT=-1" >> /app/.bashrc