FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        unzip \
        zip \
        libzip-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        default-mysql-client \
        pkg-config \
    && docker-php-ext-install pdo pdo_mysql mbstring curl zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/forest-os.ini
COPY docker/entrypoint.sh /usr/local/bin/forest-entrypoint

RUN chmod +x /usr/local/bin/forest-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["forest-entrypoint"]
CMD ["apache2-foreground"]
