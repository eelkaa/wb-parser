<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ApiService;
use App\Models\Token;
use App\Models\TokenType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class WbApiService
{
    protected Client $client;
    protected string $host;
    protected string $key;
    protected ?int   $accountId = null;

    // Имена сервиса и типа токена в БД
    public const SERVICE_NAME    = 'Wildberries';
    public const TOKEN_TYPE_NAME = 'Statistics';

    public function __construct()
    {
        $this->host = config('wb.host');
        $this->key  = config('wb.key');
        $this->buildClient();
    }

    /**
     * Инициализировать сервис под конкретный аккаунт.
     * Токен берётся из таблицы tokens.
     */
    public function forAccount(Account $account): static
    {
        $clone = clone $this;
        $clone->accountId = $account->id;

        // Ищем токен в БД: сервис WB + тип Statistics
        $service   = ApiService::where('name', self::SERVICE_NAME)->first();
        $tokenType = TokenType::where('name', self::TOKEN_TYPE_NAME)->first();

        if ($service && $tokenType) {
            $token = Token::where('account_id', $account->id)
                ->where('api_service_id', $service->id)
                ->where('token_type_id', $tokenType->id)
                ->first();

            if ($token) {
                $clone->key = $token->token_value;
                $clone->debug("[Account #{$account->id}] Используется токен из БД (id={$token->id})");
            } else {
                $clone->debug("[Account #{$account->id}] Токен в БД не найден, используется ключ из .env");
            }
        }

        $clone->buildClient();
        return $clone;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    // -------------------------------------------------------------------------
    // Публичные методы выборки данных
    // -------------------------------------------------------------------------

    public function getOrders(string $dateFrom, string $dateTo): array
    {
        return $this->fetchAllPages('orders', $dateFrom, $dateTo);
    }

    public function getSales(string $dateFrom, string $dateTo): array
    {
        return $this->fetchAllPages('sales', $dateFrom, $dateTo);
    }

    public function getStocks(string $dateFrom, string $dateTo): array
    {
        return $this->fetchAllPages('stocks', $dateFrom, $dateTo);
    }

    public function getIncomes(string $dateFrom, string $dateTo): array
    {
        return $this->fetchAllPages('incomes', $dateFrom, $dateTo);
    }

    public function getReportDetail(string $dateFrom, string $dateTo): array
    {
        return $this->fetchAllPages('reportDetail', $dateFrom, $dateTo);
    }

    // -------------------------------------------------------------------------
    // Внутренние методы
    // -------------------------------------------------------------------------

    /**
     * Постраничная загрузка данных с API.
     */
    protected function fetchAllPages(string $endpoint, string $dateFrom, string $dateTo): array
    {
        $limit  = config('wb.limit', 500);
        $page   = 1;
        $result = [];

        $this->debug("▶ Запрос [{$endpoint}] period={$dateFrom}→{$dateTo} limit={$limit}");

        while (true) {
            $params = [
                'dateFrom' => $dateFrom,
                'dateTo'   => $dateTo,
                'page'     => $page,
                'limit'    => $limit,
            ];

            $rows = $this->get($endpoint, $params);

            if (empty($rows)) {
                $this->debug("  ✓ Страница {$page}: данных нет — завершаем");
                break;
            }

            $this->debug("  ✓ Страница {$page}: получено " . count($rows) . " записей");
            $result = array_merge($result, $rows);

            if (count($rows) < $limit) {
                break; // последняя страница
            }

            $page++;
        }

        $this->debug("  Итого [{$endpoint}]: " . count($result) . " записей");
        return $result;
    }

    /**
     * Базовый HTTP-запрос с обработкой 429 (Too Many Requests).
     */
    protected function get(string $endpoint, array $params = []): array
    {
        $params['key'] = $this->key;

        $retryTimes = config('wb.retry_times', 3);
        $retrySleep = config('wb.retry_sleep', 5);

        for ($attempt = 1; $attempt <= $retryTimes; $attempt++) {
            try {
                $this->debug("  → GET /api/{$endpoint} [попытка {$attempt}/{$retryTimes}]");

                $response = $this->client->get("/api/{$endpoint}", [
                    'query' => $params,
                ]);

                $body = (string) $response->getBody();
                $data = json_decode($body, true);

                if ($data === null) {
                    $this->warn("[{$endpoint}] Пустой или не-JSON ответ: " . substr($body, 0, 200));
                    return [];
                }

                return $data;

            } catch (ClientException $e) {
                $statusCode = $e->getResponse()->getStatusCode();

                if ($statusCode === 429) {
                    $delay = $retrySleep * (3 ** ($attempt - 1)); // 5, 15, 45 сек
                    $this->warn("[{$endpoint}] 429 Too Many Requests. Ждём {$delay}с (попытка {$attempt}/{$retryTimes})");
                    Log::warning("WB API 429 [{$endpoint}]: attempt {$attempt}, sleeping {$delay}s");

                    if ($attempt < $retryTimes) {
                        sleep($delay);
                        continue;
                    }

                    $this->error("[{$endpoint}] Превышен лимит попыток после 429. Пропускаем.");
                    Log::error("WB API 429 [{$endpoint}]: max retries exceeded");
                    return [];
                }

                $this->error("[{$endpoint}] HTTP {$statusCode}: " . $e->getMessage());
                Log::error("WB API ClientError [{$endpoint}] HTTP {$statusCode}: " . $e->getMessage());
                return [];

            } catch (GuzzleException $e) {
                $this->error("[{$endpoint}] Ошибка запроса: " . $e->getMessage());
                Log::error("WB API GuzzleError [{$endpoint}]: " . $e->getMessage());
                return [];
            }
        }

        return [];
    }

    /**
     * Пересобирает Guzzle-клиент (после смены host/key).
     */
    protected function buildClient(): void
    {
        $scheme = str_contains($this->host, ':') && !str_starts_with($this->host, 'http')
            ? 'http'
            : 'https';

        // Если хост уже содержит схему — используем как есть
        $baseUri = str_starts_with($this->host, 'http') ? $this->host : "{$scheme}://{$this->host}";

        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout'  => 60,
            'headers'  => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Вспомогательный debug-вывод в консоль
    // -------------------------------------------------------------------------

    protected function debug(string $message): void
    {
        $prefix = $this->accountId ? "[Account #{$this->accountId}] " : '';
        $line   = "[" . date('H:i:s') . "] [DEBUG] {$prefix}{$message}";
        echo $line . PHP_EOL;
        Log::debug($line);
    }

    protected function warn(string $message): void
    {
        $prefix = $this->accountId ? "[Account #{$this->accountId}] " : '';
        $line   = "[" . date('H:i:s') . "] [WARN]  {$prefix}{$message}";
        echo $line . PHP_EOL;
        Log::warning($line);
    }

    protected function error(string $message): void
    {
        $prefix = $this->accountId ? "[Account #{$this->accountId}] " : '';
        $line   = "[" . date('H:i:s') . "] [ERROR] {$prefix}{$message}";
        echo $line . PHP_EOL;
        Log::error($line);
    }
}
