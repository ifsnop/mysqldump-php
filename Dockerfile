FROM php:8.1-alpine

RUN apk --update add --no-cache \
    bash mysql-client \
    && rm -rf /var/cache/apk/*

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /app

CMD ["tail", "-f", "/dev/null"]
