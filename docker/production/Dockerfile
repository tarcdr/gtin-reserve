# syntax=docker/dockerfile:1.7

FROM node:20-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js jsconfig.json ./
RUN npm run build

FROM ubuntu:22.04 AS app

ARG WWWGROUP=1000
ARG WWWUSER=1000

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient_21_7
ENV PATH=/opt/oracle/instantclient_21_7:$PATH
ENV ORACLE_HOME=/opt/oracle/instantclient_21_7
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
ENV COMPOSER_MAX_PARALLEL_HTTP=1

WORKDIR /var/www/html

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg curl ca-certificates wget zip unzip git libaio1 libpng-dev \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.2-cli php8.2-fpm php8.2-dev \
       php8.2-gd php8.2-imagick php8.2-curl php8.2-pdo \
       php8.2-imap php8.2-mysql php8.2-mbstring php8.2-xml \
       php8.2-zip php8.2-bcmath php8.2-soap php8.2-intl \
       php8.2-readline php8.2-ldap php8.2-redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN mkdir -p /opt/oracle \
    && wget https://download.oracle.com/otn_software/linux/instantclient/217000/instantclient-basic-linux.x64-21.7.0.0.0dbru.zip \
    && wget https://download.oracle.com/otn_software/linux/instantclient/217000/instantclient-sdk-linux.x64-21.7.0.0.0dbru.zip \
    && unzip instantclient-basic-linux.x64-21.7.0.0.0dbru.zip \
    && unzip instantclient-sdk-linux.x64-21.7.0.0.0dbru.zip \
    && mv instantclient_21_7 /opt/oracle/instantclient_21_7 \
    && rm -f instantclient-basic-linux.x64-21.7.0.0.0dbru.zip instantclient-sdk-linux.x64-21.7.0.0.0dbru.zip

RUN ln -sf /opt/oracle/instantclient_21_7/sdk/include/*.h /usr/local/include/ \
    && ln -sf /opt/oracle/instantclient_21_7/*.so /usr/local/lib/ \
    && ln -sf /opt/oracle/instantclient_21_7/*.so.* /usr/local/lib/ \
    && echo /opt/oracle/instantclient_21_7 > /etc/ld.so.conf.d/oracle-instantclient.conf \
    && ldconfig \
    && printf "instantclient,/opt/oracle/instantclient_21_7\n" | pecl install https://pecl.php.net/get/oci8-3.3.0.tgz \
    && echo "extension=oci8.so" > /etc/php/8.2/cli/conf.d/20-oci8.ini \
    && echo "extension=oci8.so" > /etc/php/8.2/fpm/conf.d/20-oci8.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd --force -g ${WWWGROUP} www \
    && useradd -ms /bin/bash --no-user-group -g ${WWWGROUP} -u ${WWWUSER} www \
    && mkdir -p /run/php

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    --mount=type=secret,id=composer_auth,required=false \
    if [ -f /run/secrets/composer_auth ]; then export COMPOSER_AUTH="$(cat /run/secrets/composer_auth)"; fi \
    && git config --global advice.detachedHead false \
    && composer install --no-dev --no-interaction --prefer-source --optimize-autoloader --no-scripts --no-progress \
    && find vendor -type d -name .git -prune -exec rm -rf {} +

COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY docker/production/php.ini /etc/php/8.2/fpm/conf.d/99-production.ini
COPY docker/production/php.ini /etc/php/8.2/cli/conf.d/99-production.ini
COPY docker/production/entrypoint.sh /usr/local/bin/production-entrypoint

RUN composer dump-autoload --no-dev --optimize \
    && chmod +x /usr/local/bin/production-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www:www storage bootstrap/cache \
    && sed -i 's|^listen = .*|listen = 0.0.0.0:9000|' /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i 's|^user = .*|user = www|' /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i 's|^group = .*|group = www|' /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i 's|^listen.owner = .*|listen.owner = www|' /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i 's|^listen.group = .*|listen.group = www|' /etc/php/8.2/fpm/pool.d/www.conf

EXPOSE 9000

ENTRYPOINT ["production-entrypoint"]
CMD ["php-fpm8.2", "-F"]

FROM nginx:1.27-alpine AS nginx

WORKDIR /var/www/html

COPY docker/production/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public ./public
