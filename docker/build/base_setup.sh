#!/bin/bash

# base_setup.sh — installs runtime packages, creates the symfony user (UID 1000),
# and grants nginx the capability to bind privileged ports without root.
# Runs as root during the 'base' Docker stage.

set -eu

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get upgrade -y
apt-get install -y --no-install-recommends \
    tzdata \
    ca-certificates \
    curl \
    jq \
    netcat-openbsd \
    nginx-light \
    php8.5-apcu \
    php8.5-bcmath \
    php8.5-cli \
    php8.5-curl \
    php8.5-fpm \
    php8.5-gd \
    php8.5-imagick \
    php8.5-intl \
    php8.5-mbstring \
    php8.5-mysql \
    php8.5-pcov \
    php8.5-uuid \
    php8.5-xml \
    php8.5-zip \
    supervisor \
    unzip

# --- symfony user (UID/GID 1000) ---
# Official ubuntu:24.04 images ship a built-in 'ubuntu' user/group at UID/GID 1000;
# rename it when present, otherwise create symfony explicitly.
if getent group ubuntu >/dev/null 2>&1; then
    groupmod -n symfony ubuntu
else
    groupadd -g 1000 symfony
fi

if getent passwd ubuntu >/dev/null 2>&1; then
    usermod -l symfony -d /app -s /bin/bash ubuntu
else
    useradd -u 1000 -g symfony -d /app -s /bin/bash -m symfony
fi

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
