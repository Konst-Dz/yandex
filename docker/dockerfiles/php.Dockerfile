FROM php:8.4-fpm-alpine

ARG USER_ID=1000
ARG GROUP_ID=1000

WORKDIR /var/www/app

RUN addgroup -g ${GROUP_ID} -S app \
    && adduser -u ${USER_ID} -G app -S -D -h /var/www/app app

RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install xdebug-3.4.2 \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS linux-headers

# PostgreSQL (основная БД проекта) + pcntl/bcmath для воркеров очередей
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo_pgsql pcntl bcmath opcache

# Redis-клиент phpredis: QUEUE_CONNECTION/CACHE_STORE=redis (дефолтный client в Laravel)
RUN apk add --no-cache --virtual .redis-build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .redis-build-deps

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN apk add --no-cache git unzip fcgi

RUN sed -i 's/^user = .*/user = app/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^group = .*/group = app/' /usr/local/etc/php-fpm.d/www.conf \
    && echo "ping.path = /ping" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "ping.response = pong" >> /usr/local/etc/php-fpm.d/www.conf

COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
