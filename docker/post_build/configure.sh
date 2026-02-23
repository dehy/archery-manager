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

mkdir -p /app/{.yarn,node_modules,public/build}
chown -R symfony: /app/{node_modules,public/build}

apt update
apt install -y build-essential libcairo2-dev libpango1.0-dev libjpeg-dev libgif-dev librsvg2-dev

if [ "${BUILD_TARGET}" = "prod" ]; then
    # Production build: install composer deps without dev, run yarn build
    ${GOSU} /usr/local/bin/composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader
    ${GOSU} /usr/local/bin/composer dump-autoload --no-dev --classmap-authoritative
    ${GOSU} /usr/local/bin/composer clear-cache --no-interaction
    
    # Yarn install requires FONTAWESOME_NPM_AUTH_TOKEN in production
    # Read from BuildKit secret mount (never stored as an ARG/ENV to avoid leaking in image history)
    FONTAWESOME_SECRET_FILE="/run/secrets/fontawesome_npm_auth_token"
    if [ ! -r "${FONTAWESOME_SECRET_FILE}" ]; then
        echo "ERROR: BuildKit secret 'fontawesome_npm_auth_token' is required for production builds"
        exit 1
    fi
    export FONTAWESOME_NPM_AUTH_TOKEN=$(cat "${FONTAWESOME_SECRET_FILE}")
    ${GOSU} /usr/bin/yarn install --immutable
    ${GOSU} /usr/bin/yarn build
    ${GOSU} rm -rf /app/{node_modules,.bash*,.cache,.config,.local,.npm,.yarn,.yarnrc}
else
    # Development build: install composer deps with dev, skip yarn (run on host)
    ${GOSU} /usr/local/bin/composer install --prefer-dist --no-interaction
    echo "Development build: skipping yarn install/build (run 'yarn install' and 'yarn dev' on host)"
fi

apt purge -y build-essential libcairo2-dev libpango1.0-dev libjpeg-dev libgif-dev librsvg2-dev

${GOSU} bin/console assets:install public --symlink --relative

echo "export COMPOSER_MEMORY_LIMIT=-1" >> /app/.bashrc