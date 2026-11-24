# syntax=docker/dockerfile:1

# Kinetra Admin: one image, run as the web app and as the scheduler
# (see docker-compose.yml). FrankenPHP serves PHP and static files and
# terminates TLS.
ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1.13-php8.5-bookworm

FROM ${FRANKENPHP_IMAGE} AS php
RUN install-php-extensions intl pcntl zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

# 1. PHP dependencies, without dev packages.
FROM php AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# 2. Front-end build. Needs PHP too: the Wayfinder plugin runs artisan to
#    generate the typed route helpers.
FROM php AS assets
COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY --from=vendor /app /app
RUN npm run build

# 3. Runtime.
FROM php
COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

# The base image grants frankenphp CAP_NET_BIND_SERVICE as a file capability,
# which fails to exec under cap_drop: ALL; docker-compose.yml lets
# unprivileged users bind 80 and 443 instead.
RUN chmod 755 /usr/local/bin/entrypoint \
    && useradd --uid 1000 --create-home app \
    && mkdir -p storage/demo/sandboxes storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R app:app storage bootstrap/cache /config/caddy /data/caddy \
    && setcap -r /usr/local/bin/frankenphp \
    && rm /usr/bin/composer

USER app
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr

EXPOSE 80 443 443/udp
ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
