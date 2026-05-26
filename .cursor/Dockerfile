FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    php8.3-cli \
    php8.3-curl \
    php8.3-gd \
    php8.3-mbstring \
    php8.3-sqlite3 \
    php8.3-xml \
    php8.3-zip \
    sqlite3 \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php
