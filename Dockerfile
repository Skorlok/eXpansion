FROM alpine:3.19 AS base

WORKDIR /xmlrpc

RUN apk add php81-dev build-base autoconf libxml2-dev

RUN wget -qO- https://github.com/php/pecl-networking-xmlrpc/archive/refs/heads/master.tar.gz | tar -xz --strip-components=1 &&\
    /usr/bin/phpize81 &&\
    ./configure --with-php-config=/usr/bin/php-config81 &&\
    make -j16 &&\
    make install

FROM alpine:3.19

WORKDIR /app/controller

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apk add php81 php81-mysqli php81-gd php81-mbstring php81-openssl php81-sqlite3 php81-curl php81-phar php81-xml php81-simplexml php81-dom php81-zlib php81-pcntl php81-sockets

COPY --from=base /usr/lib/php81/modules/xmlrpc.so /usr/lib/php81/modules/xmlrpc.so
RUN apk add libxml2-dev && echo extension=xmlrpc > /etc/php81/conf.d/00_xmlrpc.ini


RUN wget -qO- https://github.com/Skorlok/eXpansion/archive/refs/heads/base-docker.tar.gz | tar xz --strip-components=1
RUN wget https://getcomposer.org/download/latest-stable/composer.phar
RUN php81 ./composer.phar require skorlok/expansion:dev-master
RUN apk add tini


ENTRYPOINT ["/sbin/tini","--"]
CMD ["php81","bootstrapper.php"]
