#!/bin/bash

# base_setup.sh — installs runtime packages, creates the symfony user (UID 1000),
# and grants nginx the capability to bind privileged ports without root.
# Runs as root during the 'base' Docker stage.

set -eu

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y --no-install-recommends ca-certificates gnupg2

# --- APT sources ---
mkdir -p /etc/apt/keyrings
install -o root -g root -m 644 /docker/build/ondrej.gpg /etc/apt/keyrings/ondrej.gpg
echo 'deb [signed-by=/etc/apt/keyrings/ondrej.gpg] https://ppa.launchpadcontent.net/ondrej/nginx/ubuntu noble main' \
    | tee /etc/apt/sources.list.d/nginx.list
echo 'deb [signed-by=/etc/apt/keyrings/ondrej.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main' \
    | tee /etc/apt/sources.list.d/php.list

apt-get update
apt-get upgrade -y
apt-get install -y --no-install-recommends \
    tzdata \
    ca-certificates \
    curl \
    git \
    jq \
    netcat-openbsd \
    nginx-light \
    php8.4-apcu \
    php8.4-bcmath \
    php8.4-cli \
    php8.4-curl \
    php8.4-fpm \
    php8.4-gd \
    php8.4-imagick \
    php8.4-intl \
    php8.4-mbstring \
    php8.4-mysqlnd \
    php8.4-pcov \
    php8.4-uuid \
    php8.4-xml \
    php8.4-zip \
    supervisor \
    unzip

# --- symfony user (UID/GID 1000) ---
# Ubuntu 24.04 ships a built-in 'ubuntu' user/group at UID/GID 1000; rename it.
groupmod -n symfony ubuntu
usermod -l symfony -d /app -s /bin/bash ubuntu

# --- App directory skeleton (only var/ writable by symfony) ---
mkdir -p /app/var/cache /app/var/log /app/var/sessions
chown -R symfony:symfony /app/var

# /var/lib/php/sessions must be writable by symfony (used as session.save_path)
mkdir -p /var/lib/php/sessions
chown symfony:symfony /var/lib/php/sessions

# --- Allow nginx to bind port 80 without root ---
apt-get install -y --no-install-recommends libcap2-bin
setcap cap_net_bind_service=+ep /usr/sbin/nginx
apt-get purge -y libcap2-bin

apt-get -y autoremove
apt-get clean
rm -rf /var/lib/apt/lists/* /usr/share/man/* /tmp/*
