<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class WbApiService
{
    protected Client $client;
    protected string $host;
    protected string $key;

    public function __construct()
    {
        $this->host = config('wb.host');
        $this->key  = config('wb.key');

        $this->client = new Client([
            'base_uri' => "http://{$this->host}",
            'timeout'  => 30,
        ]);
    }

    /**
     * Базовый запрос к API.
     */
    protected function get(string $endpoint, array $params = []): array
    {
        $params['key'] = $this->key;

        try {
            $response = $this->client->get("/api/{$endpoint}", [
                'query' => $params,
            ]);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            return $data ?? [];
        } catch (GuzzleException $e) {
            Log::error("WB API error [{$endpoint}]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Перебирает все страницы и возвращает плоский массив записей.
     */
    protected function fetchAll(string $endpoint, array $params = []): array
    {
        $limit   = config('wb.limit', 500);
        $page    = 1;
        $all     = [];

        do {
            $params['page']  = $page;
            $params['limit'] = $limit;

            $data = $this->get($endpoint, $params);

            // API может вернуть массив напрямую или обернуть в ключ data/items
            $rows = $data['data'] ?? $data['items'] ?? (is_array($data) && isset($data[0]) ? $data : []);

            if (empty($rows)) {
                break;
            }

            $all  = array_merge($all, $rows);
            $page++;

            // Если вернули меньше лимита — это последняя страница
        } while (count($rows) === $limit);

        return $all;
    }

    // -------------------------------------------------------------------------
    // Публичные методы для каждого эндпоинта
    // -------------------------------------------------------------------------

    public function getOrders(string $dateFrom, string $dateTo): array
    {
        return $this->fetchAll('orders', [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }

    public function getSales(string $dateFrom, string $dateTo): array
    {
        return $this->fetchAll('sales', [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }

    public function getStocks(string $dateFrom, string $dateTo): array
    {
        return $this->fetchAll('stocks', [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }

    public function getIncomes(string $dateFrom, string $dateTo): array
    {
        return $this->fetchAll('incomes', [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }

    public function getReportDetail(string $dateFrom, string $dateTo): array
    {
        return $this->fetchAll('reportDetail', [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }
}
