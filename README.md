# wb-parser

Laravel-проект для выгрузки данных из Wildberries API и сохранения в MySQL.
Поддерживает **мультиаккаунтность**, хранит данные как в нормализованных таблицах,
так и в JSON (`data_records`). Разворачивается через **Docker Compose**.

---

## Быстрый старт (Docker)

```bash
git clone https://github.com/eelkaa/wb-parser.git
cd wb-parser
cp .env.example .env
# Отредактируй .env: добавь WB_KEY и при необходимости WB_HOST
docker-compose up --build -d
docker exec php_app php artisan migrate
```

---

## Конфигурация (.env)

| Переменная      | Описание                          | Значение по умолчанию               |
|-----------------|-----------------------------------|-------------------------------------|
| `DB_HOST`       | Хост MySQL (внутри Docker)        | `mysql`                             |
| `DB_PORT`       | Порт MySQL (внутри контейнера)    | `3306`                              |
| `DB_DATABASE`   | Имя базы данных                   | `wb_parser`                         |
| `DB_USERNAME`   | Пользователь MySQL                | `wb_user`                           |
| `DB_PASSWORD`   | Пароль MySQL                      | `wb_pass`                           |
| `WB_HOST`       | Хост WB API                       | `statistics-api.wildberries.ru`     |
| `WB_KEY`        | API-ключ (или хранится в `tokens`)| *(пусто)*                           |
| `WB_DATE_FROM`  | Начало периода выгрузки           | `2024-01-01`                        |
| `WB_LIMIT`      | Записей на страницу (макс. 500)   | `500`                               |
| `WB_RETRY_TIMES`| Число попыток при 429             | `3`                                 |
| `WB_RETRY_SLEEP`| Пауза перед 1-й повторной попыткой| `5` (сек, далее ×3: 5→15→45)       |

> MySQL доступен снаружи контейнера на порту **3307**.

---

## Структура базы данных

### Управляющие таблицы

```
companies       — компании
accounts        — аккаунты (у компании может быть несколько)
api_services    — API-сервисы (например: Wildberries)
token_types     — типы токенов (например: Statistics)
tokens          — токены аккаунтов (UNIQUE: account+service+type)
```

### Таблицы данных WB

```
orders          — заказы
sales           — продажи и возвраты
stocks          — остатки товаров
incomes         — поставки
report_details  — детализация финансового отчёта
data_records    — все данные в JSON параллельно (для аналитики)
```

---

## Консольные команды

### Управление сущностями

```bash
# Добавить компанию
php artisan entity:company --name="ООО Ромашка"

# Добавить аккаунт к компании
php artisan entity:account --company=1 --name="Основной"

# Добавить API-сервис
php artisan entity:api-service --name="Wildberries" --description="Статистика WB"

# Добавить тип токена
php artisan entity:token-type --name="Statistics" --description="x-statisticss-token"

# Добавить токен для аккаунта
php artisan entity:token --account=1 --service=1 --type=1 --value="ВАШ_ТОКЕН"
```

> Все команды работают в **интерактивном режиме** — если не передать опции, они спросят.

### Выгрузка данных

```bash
# Выгрузить данные для ВСЕХ аккаунтов (запускается по cron)
php artisan wb:fetch:all

# Выгрузить для конкретного аккаунта
php artisan wb:fetch --account=1

# Выгрузить за конкретный период
php artisan wb:fetch --account=1 --dateFrom=2024-03-01 --dateTo=2024-03-31

# Выгрузить только один эндпоинт
php artisan wb:fetch --account=1 --endpoint=orders
```

### Автоматическое расписание

Cron запускает `wb:fetch:all` **дважды в день** — в **06:00** и **18:00**.
Реализовано через `schedule:work` в supervisord и системный crontab.

---

## API-эндпоинты WB

| Эндпоинт       | URL                   | Описание                          |
|----------------|-----------------------|-----------------------------------|
| `orders`       | `/api/orders`         | Заказы                            |
| `sales`        | `/api/sales`          | Продажи и возвраты                |
| `stocks`       | `/api/stocks`         | Остатки товаров                   |
| `incomes`      | `/api/incomes`        | Поставки                          |
| `reportDetail` | `/api/reportDetail`   | Детализация финансового отчёта    |

---

## Работа с Docker

```bash
# Запуск
docker-compose up -d

# Остановка
docker-compose down

# Логи PHP-контейнера
docker logs -f php_app

# Подключение к MySQL (снаружи контейнера)
mysql -h 127.0.0.1 -P 3307 -u wb_user -pwb_pass wb_parser

# Выполнить миграции
docker exec php_app php artisan migrate

# Статус миграций
docker exec php_app php artisan migrate:status

# Ручной запуск выгрузки
docker exec php_app php artisan wb:fetch:all
```

---

## Мультиаккаунтность

1. Создать компанию: `entity:company`
2. Создать аккаунт: `entity:account`
3. Создать сервис WB: `entity:api-service --name="Wildberries"`
4. Создать тип токена: `entity:token-type --name="Statistics"`
5. Добавить токен: `entity:token`
6. Запустить выгрузку: `wb:fetch:all`

Данные каждого аккаунта хранятся отдельно (поле `account_id`) — данные из разных аккаунтов не перезаписывают друг друга.

---

## Обработка ошибок

- **429 Too Many Requests** — автоматические повторные попытки (3 раза, паузы 5→15→45 сек)
- Все ошибки выводятся в консоль и записываются в `storage/logs/laravel.log`
- Лог cron-выгрузки: `storage/logs/wb-fetch.log`
