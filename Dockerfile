FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev cron supervisor \
    && docker-php-ext-install pdo_mysql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Копируем конфиги
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/crontab /etc/cron.d/wb-cron
RUN chmod 0644 /etc/cron.d/wb-cron && crontab /etc/cron.d/wb-cron

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
