#!/bin/bash

set -eux

export APP_ENV=prod
GOSU="/usr/sbin/gosu symfony"

cd /app

echo ${VCS_REF:-""} > vcs_ref
echo ${BUILD_DATE:-""} > build_date
echo ${VERSION:-""} > version

# Make the PHP-FPM php.ini same as fpm
ln -sf /etc/php/8.1/fpm/php.ini /etc/php/8.1/cli/php.ini

mkdir -p /app/{.yarn,node_modules,public/build}
chown -R symfony: /app/{node_modules,public/build}

${GOSU} /usr/local/bin/composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader
${GOSU} /usr/local/bin/composer dump-autoload --no-dev --classmap-authoritative
${GOSU} /usr/local/bin/composer clear-cache --no-interaction
${GOSU} /usr/bin/yarn install --immutable
${GOSU} /usr/bin/yarn build
${GOSU} rm -rf /app/{node_modules,.bash*,.cache,.config,.local,.npm,.yarn,.yarnrc}

${GOSU} bin/console assets:install public --symlink --relative

echo "export COMPOSER_MEMORY_LIMIT=-1" >> /app/.bashrc