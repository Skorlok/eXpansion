FROM php:8.1-cli-alpine

RUN mkdir -p /app/controller
WORKDIR /app/controller

COPY . /app/controller

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apk add autoconf build-base libpng-dev libxml2-dev tini &&\
    docker-php-ext-install mysqli gd sockets pcntl &&\
    pecl install channel://pecl.php.net/xmlrpc-1.0.0RC3 && docker-php-ext-enable xmlrpc &&\
    wget https://getcomposer.org/download/latest-stable/composer.phar &&\
    php ./composer.phar require skorlok/expansion:dev-master &&\
    apk del autoconf build-base


ENTRYPOINT ["/sbin/tini","--"]
CMD ["php","bootstrapper.php"]
