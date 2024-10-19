FROM php:8.1-cli-alpine

WORKDIR /app/controller

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apk add git autoconf build-base libpng-dev libxml2-dev tini &&\
    docker-php-ext-install mysqli gd sockets pcntl &&\
    pecl install channel://pecl.php.net/xmlrpc-1.0.0RC3 && docker-php-ext-enable xmlrpc &&\
    git clone -b base-docker https://github.com/Skorlok/eXpansion.git . &&\
    rm -r ./.git ./Dockerfile ./DockerfileDev ./README.md &&\
    wget https://getcomposer.org/download/latest-stable/composer.phar &&\
    php ./composer.phar require skorlok/expansion:dev-master &&\
    apk del git autoconf build-base


ENTRYPOINT ["/sbin/tini","--"]
CMD ["php","bootstrapper.php"]