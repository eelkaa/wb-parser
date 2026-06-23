FROM php:8.3-cli

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    cron \
    supervisor \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Копируем composer-файлы и устанавливаем зависимости
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Копируем весь проект
COPY . .

# Настройка прав на storage и bootstrap/cache
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions \
        storage/framework/testing storage/framework/views bootstrap/cache \
        /var/log/supervisor \
    && chmod -R 777 storage bootstrap/cache

# Копируем crontab
COPY docker/crontab /etc/cron.d/wb-parser
RUN chmod 0644 /etc/cron.d/wb-parser \
    && touch /var/log/cron.log

# Копируем конфиг supervisord
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
