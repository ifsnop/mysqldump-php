ARG PHP_VERSION

FROM php:${PHP_VERSION}-alpine

RUN apk --update add --no-cache \
    bash mysql-client \
    && rm -rf /var/cache/apk/*

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /app

CMD ["tail", "-f", "/dev/null"]
