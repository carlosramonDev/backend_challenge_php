FROM php:8.0
COPY . . 
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_mysql mysqli pdo_sqlite sqlite3 \
    && apt-get install -y wget \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"
    && composer install
EXPOSE 8000
CMD php -S localhost:8000 -t public



