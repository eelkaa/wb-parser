# wb-parser

Laravel-проект для выгрузки данных из Wildberries API и сохранения в MySQL.

\---

## Быстрый старт

```bash
git clone https://github.com/YOUR\_NAME/wb-parser.git
cd wb-parser
composer install
cp .env.example .env
php artisan key:generate
```

Заполни `.env` (см. раздел «Конфигурация»), затем:

```bash
php artisan migrate
php artisan wb:fetch
```

\---

## Конфигурация (.env)

|Переменная|Описание|Значение по умолчанию|
|-|-|-|
|`DB\_HOST`|Хост MySQL|`127.0.0.1`|
|`DB\_PORT`|Порт MySQL|`3306`|
|`DB\_DATABASE`|Имя базы данных|`wb\_parser`|
|`DB\_USERNAME`|Пользователь MySQL|`root`|
|`DB\_PASSWORD`|Пароль MySQL|*(пусто)*|
|`WB\_HOST`|Хост WB API|`109.73.206.144:6969`|
|`WB\_KEY`|API-ключ|`E6kUTYrYwZq2tN4QEtyzsbEBk3ie`|
|`WB\_DATE\_FROM`|Начало периода выгрузки|`2024-01-01`|
|`WB\_DATE\_TO`|Конец периода выгрузки|текущая дата|
|`WB\_LIMIT`|Записей на страницу (макс. 500)|`500`|

\---

## Команда выгрузки

### Выгрузить все данные

```bash
php artisan wb:fetch
```

### Выгрузить за конкретный период

```bash
php artisan wb:fetch --dateFrom=2024-03-01 --dateTo=2024-03-31
```

### Выгрузить только один эндпоинт

```bash
php artisan wb:fetch --endpoint=orders
php artisan wb:fetch --endpoint=sales
php artisan wb:fetch --endpoint=stocks
php artisan wb:fetch --endpoint=incomes
php artisan wb:fetch --endpoint=reportDetail
```

\---

## API-эндпоинты

|Эндпоинт|URL|Описание|
|-|-|-|
|`orders`|`/api/orders`|Заказы|
|`sales`|`/api/sales`|Продажи и возвраты|
|`stocks`|`/api/stocks`|Остатки товаров|
|`incomes`|`/api/incomes`|Поставки|
|`reportDetail`|`/api/reportDetail`|Детализация финансового отчёта|

Все эндпоинты принимают параметры: `dateFrom`, `dateTo`, `page`, `limit`, `key`.

\---

## Таблицы базы данных

|Таблица|Уникальный ключ|Основные поля|
|-|-|-|
|`orders`|`order\_id`|date, supplier\_article, nm\_id, total\_price, is\_cancel, brand|
|`sales`|`sale\_id`|date, nm\_id, total\_price, for\_pay, brand, warehouse\_name|
|`stocks`|`nm\_id + warehouse\_name`|quantity, quantity\_full, in\_way\_to\_client, price, discount|
|`incomes`|`income\_id`|date, quantity, total\_price, status, warehouse\_name|
|`report\_details`|`rrd\_id`|doc\_type\_name, retail\_amount, ppvz\_for\_pay, penalty|

Каждая таблица содержит колонку `raw` (JSON) — полная исходная запись из API.  
Схема SQL: `database/schema.sql`.

\---

## Доступы к БД (free-tier, PlanetScale / Railway / FreeSQLdatabase)

> ⚠️ Заполни после развёртывания на хостинге

```
Host:     yamanote.proxy.rlwy.net

Port:     57959

Database: railway

User:     root

Password: sTuvshSoBczQJXgZvQWTBojIPpWKNdiv

```

**Бесплатные хостинги для MySQL:**

* [Railway.app](https://railway.app) — 500 МБ бесплатно, подключение через стандартный DSN
* [PlanetScale](https://planetscale.com) — 5 ГБ бесплатно, MySQL-совместимый
* [FreeSQLdatabase.com](https://www.freesqldatabase.com) — просто и бесплатно

\---

## Планировщик (автоматическая синхронизация)

Добавь в crontab на сервере:

```cron
\* \* \* \* \* cd /path/to/wb-parser \&\& php artisan schedule:run >> /dev/null 2>\&1
```

По умолчанию команда `wb:fetch` запускается **каждый час**.  
Изменить расписание — `app/Console/Kernel.php`, метод `schedule()`.

\---

## Структура проекта

```
app/
  Console/
    Commands/FetchWbData.php   # Artisan-команда выгрузки
    Kernel.php                 # Регистрация команд и расписание
  Models/
    Order.php
    Sale.php
    Stock.php
    Income.php
    ReportDetail.php
  Services/
    WbApiService.php           # HTTP-клиент для WB API (пагинация, retry)
config/
  wb.php                       # Настройки подключения к API
database/
  migrations/                  # Laravel-миграции
  schema.sql                   # Готовый SQL-дамп (альтернатива migrate)
```

\---

## Развёртывание на Railway (пример)

1. Зарегистрируйся на [railway.app](https://railway.app)
2. New Project → Deploy from GitHub → выбери репозиторий
3. Добавь MySQL-сервис: + New → Database → MySQL
4. Скопируй `DATABASE\_URL` из переменных MySQL-сервиса
5. Добавь переменные окружения (`WB\_HOST`, `WB\_KEY`, даты)
6. В разделе Deploy → Start Command: `php artisan migrate --force \&\& php artisan wb:fetch`

