FROM php:8.2-cli-alpine

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install pcov redis \
    && docker-php-ext-enable redis \
    && echo 'extension=pcov.so' > /usr/local/etc/php/conf.d/pcov.ini

RUN curl --insecure https://getcomposer.org/composer-stable.phar -o /usr/bin/composer && \
    chmod +x /usr/bin/composer

WORKDIR /opt/www

COPY ./composer.* /opt/www

RUN composer install --prefer-dist

COPY . /opt/www