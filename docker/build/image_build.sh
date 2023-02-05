#!/bin/bash

COMPOSER_VERSION=2.5.1

set -eu

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y --no-install-recommends ca-certificates gnupg2

echo 'deb http://ppa.launchpad.net/ondrej/nginx/ubuntu jammy main' | tee /etc/apt/sources.list.d/nginx.list
echo 'deb http://ppa.launchpad.net/ondrej/php/ubuntu jammy main' | tee /etc/apt/sources.list.d/php.list
apt-key --keyring /etc/apt/trusted.gpg.d/ondrej.gpg add /docker/build/E5267A6C.gpg

echo "deb https://deb.nodesource.com/node_18.x jammy main" | tee /etc/apt/sources.list.d/nodesource.list
echo "deb-src https://deb.nodesource.com/node_18.x jammy main" | tee -a /etc/apt/sources.list.d/nodesource.list
apt-key --keyring /etc/apt/trusted.gpg.d/nodesource.gpg add /docker/build/nodesource.gpg.key

apt-get update
apt-get upgrade -y
apt-get install -y --no-install-recommends \
    tzdata \
    ca-certificates \
    cron \
    curl \
    git \
    gosu \
    jq \
    netcat \
    nginx-light \
    nodejs \
    php8.1-apcu \
    php8.1-bcmath \
    php8.1-cli \
    php8.1-curl \
    php8.1-fpm \
    php8.1-gd \
    php8.1-imagick \
    php8.1-intl \
    php8.1-mbstring \
    php8.1-mysqlnd \
    php8.1-pcov \
    php8.1-uuid \
    php8.1-xml \
    php8.1-zip \
    supervisor \
    unzip \
    vim \
    wget

npm install -g yarn

# Composer
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer --version=${COMPOSER_VERSION}
rm composer-setup.php
## End Composer

mkdir /app
useradd -s /bin/bash -d /app symfony
chown symfony: /app

apt-get -y autoremove
apt-get clean
apt-get install -y --no-install-recommends -d php-xdebug # Download only
rm -rf /var/lib/apt/lists/*
rm -rf /usr/share/man/*
rm -rf /tmp/* /root/.npm
