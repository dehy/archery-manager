#!/bin/bash

# builder_setup.sh — installs build tools (Node.js, Composer, native canvas deps).
# Runs as root during the 'builder' and 'dev' Docker stages.
# These tools are NOT present in the 'base' runtime stage.
# Node.js 22 LTS is provided by the Ubuntu 26.04 native repo (no external PPA needed).

COMPOSER_VERSION=2.9.7

set -eu

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y --no-install-recommends \
    git \
    nodejs \
    build-essential \
    libcairo2-dev \
    libpango1.0-dev \
    libjpeg-dev \
    libgif-dev \
    librsvg2-dev

# --- Composer (verified checksum) ---
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    >&2 echo 'ERROR: Invalid Composer installer checksum'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer --version=${COMPOSER_VERSION}
rm composer-setup.php

rm -rf /var/lib/apt/lists/* /tmp/* /root/.npm
