<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\DataRecord;
use App\Models\Income;
use App\Models\Order;
use App\Models\ReportDetail;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\WbApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchWbData extends Command
{
    protected $signature = 'wb:fetch
                            {--account=  : ID аккаунта (обязателен при вызове из wb:fetch:all)}
                            {--dateFrom= : Дата ОТ (YYYY-MM-DD); по умолчанию — авто (свежие данные)}
                            {--dateTo=   : Дата ДО (YYYY-MM-DD); по умолчанию — сегодня}
                            {--endpoint= : Конкретный эндпоинт: orders|sales|stocks|incomes|reportDetail}';

    protected $description = 'Выгружает данные WB API и сохраняет в нормализованные таблицы + data_records';

    protected ?Account $account = null;
    protected WbApiService $api;

    public function __construct(WbApiService $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    public function handle(): int
    {
        // Загрузка аккаунта
        $accountId = $this->option('account');
        if ($accountId) {
            $this->account = Account::find($accountId);
            if (!$this->account) {
                $this->error("Аккаунт с ID={$accountId} не найден.");
                return self::FAILURE;
            }
            // Инициализируем API-сервис под конкретный аккаунт
            $this->api = $this->api->forAccount($this->account);
        }

        $dateTo   = $this->option('dateTo') ?? config('wb.date_to', date('Y-m-d'));
        $only     = $this->option('endpoint');
        $label    = $this->account ? "Аккаунт #{$this->account->id} ({$this->account->name})" : 'Без аккаунта';

        $this->info("[{$label}] Дата ДО: {$dateTo}");

        $endpoints = [
            'orders'       => fn($df, $dt) => $this->syncOrders($df, $dt),
            'sales'        => fn($df, $dt) => $this->syncSales($df, $dt),
            'stocks'       => fn($df, $dt) => $this->syncStocks($df, $dt),
            'incomes'      => fn($df, $dt) => $this->syncIncomes($df, $dt),
            'reportDetail' => fn($df, $dt) => $this->syncReportDetail($df, $dt),
        ];

        if ($only) {
            if (!isset($endpoints[$only])) {
                $this->error("Неизвестный эндпоинт: {$only}. Доступные: " . implode(', ', array_keys($endpoints)));
                return self::FAILURE;
            }
            $dateFrom = $this->resolveDateFrom($only);
            $endpoints[$only]($dateFrom, $dateTo);
        } else {
            foreach ($endpoints as $name => $fn) {
                $dateFrom = $this->resolveDateFrom($name);
                $fn($dateFrom, $dateTo);
            }
        }

        $this->info("[{$label}] ✅ Готово!");
        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Вычисление dateFrom: берём свежие данные или из опций / .env
    // -------------------------------------------------------------------------

    private function resolveDateFrom(string $endpoint): string
    {
        // Если передано явно — используем
        if ($this->option('dateFrom')) {
            return $this->option('dateFrom');
        }

        // Если есть аккаунт — пробуем взять дату последней записи из data_records
        if ($this->account) {
            $lastDate = DataRecord::getLastDate($this->account->id, $endpoint);
            if ($lastDate) {
                // +1 день, чтобы не задваивать последний день
                $dateFrom = Carbon::parse($lastDate)->addDay()->format('Y-m-d');
                $this->info("  [{$endpoint}] Свежие данные с: {$dateFrom} (последняя запись: {$lastDate})");
                return $dateFrom;
            }
        }

        // Иначе — дата из .env
        $dateFrom = config('wb.date_from', '2024-01-01');
        $this->info("  [{$endpoint}] Начало с: {$dateFrom} (из .env)");
        return $dateFrom;
    }

    // -------------------------------------------------------------------------
    // Sync-методы
    // -------------------------------------------------------------------------

    private function syncOrders(string $dateFrom, string $dateTo): void
    {
        $this->info('📦 Загрузка заказов...');
        $rows = $this->api->getOrders($dateFrom, $dateTo);
        $this->info("  Получено записей: " . count($rows));

        if (empty($rows)) {
            return;
        }

        // Сохраняем в нормализованную таблицу
        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => $this->mapOrder($r), $chunk);
            Order::upsert($data, ['order_id'], array_keys($data[0]));
        }

        // Сохраняем в data_records (JSON)
        $this->saveDataRecords('orders', $dateFrom, $rows);
        $this->info('  ✓ Заказы сохранены.');
    }

    private function syncSales(string $dateFrom, string $dateTo): void
    {
        $this->info('💰 Загрузка продаж...');
        $rows = $this->api->getSales($dateFrom, $dateTo);
        $this->info("  Получено записей: " . count($rows));

        if (empty($rows)) {
            return;
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => $this->mapSale($r), $chunk);
            Sale::upsert($data, ['sale_id'], array_keys($data[0]));
        }

        $this->saveDataRecords('sales', $dateFrom, $rows);
        $this->info('  ✓ Продажи сохранены.');
    }

    private function syncStocks(string $dateFrom, string $dateTo): void
    {
        $this->info('🏪 Загрузка остатков...');
        $rows = $this->api->getStocks($dateFrom, $dateTo);
        $this->info("  Получено записей: " . count($rows));

        if (empty($rows)) {
            return;
        }

        // Stocks не имеют уникального ключа — делаем insert с заменой по аккаунту+дате
        if ($this->account) {
            Stock::where('account_id', $this->account->id)->delete();
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => $this->mapStock($r), $chunk);
            Stock::insert($data);
        }

        $this->saveDataRecords('stocks', $dateFrom, $rows);
        $this->info('  ✓ Остатки сохранены.');
    }

    private function syncIncomes(string $dateFrom, string $dateTo): void
    {
        $this->info('📬 Загрузка поставок...');
        $rows = $this->api->getIncomes($dateFrom, $dateTo);
        $this->info("  Получено записей: " . count($rows));

        if (empty($rows)) {
            return;
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => $this->mapIncome($r), $chunk);
            Income::upsert($data, ['income_id'], array_keys($data[0]));
        }

        $this->saveDataRecords('incomes', $dateFrom, $rows);
        $this->info('  ✓ Поставки сохранены.');
    }

    private function syncReportDetail(string $dateFrom, string $dateTo): void
    {
        $this->info('📊 Загрузка детализации отчёта...');
        $rows = $this->api->getReportDetail($dateFrom, $dateTo);
        $this->info("  Получено записей: " . count($rows));

        if (empty($rows)) {
            return;
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => $this->mapReportDetail($r), $chunk);
            ReportDetail::upsert($data, ['rrd_id'], array_keys($data[0]));
        }

        $this->saveDataRecords('reportDetail', $dateFrom, $rows);
        $this->info('  ✓ Детализация отчёта сохранена.');
    }

    // -------------------------------------------------------------------------
    // Сохранение в data_records (JSON)
    // -------------------------------------------------------------------------

    private function saveDataRecords(string $endpoint, string $dateFrom, array $rows): void
    {
        if (!$this->account || empty($rows)) {
            return;
        }

        // Удаляем старые data_records за этот период для данного аккаунта+эндпоинта
        DataRecord::where('account_id', $this->account->id)
            ->where('endpoint', $endpoint)
            ->where('record_date', '>=', $dateFrom)
            ->delete();

        // Группируем по дате и сохраняем
        $grouped = collect($rows)->groupBy(fn($r) => $this->extractDate($endpoint, $r));

        foreach ($grouped as $date => $items) {
            if (!$date) {
                $date = $dateFrom;
            }
            DataRecord::create([
                'account_id'  => $this->account->id,
                'endpoint'    => $endpoint,
                'record_date' => $date,
                'payload'     => $items->values()->toArray(),
            ]);
        }

        $this->line("  [data_records] Сохранено " . $grouped->count() . " групп по датам");
    }

    /**
     * Извлекает дату из записи в зависимости от эндпоинта.
     */
    private function extractDate(string $endpoint, array $row): ?string
    {
        $field = match ($endpoint) {
            'orders'       => $row['date']      ?? null,
            'sales'        => $row['date']      ?? null,
            'stocks'       => $row['lastChangeDate'] ?? null,
            'incomes'      => $row['date']      ?? null,
            'reportDetail' => $row['dateFrom']  ?? null,
            default        => null,
        };

        if (!$field) {
            return null;
        }

        try {
            return Carbon::parse($field)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Mapping-методы (API-поля → БД-поля)
    // -------------------------------------------------------------------------

    private function mapOrder(array $r): array
    {
        return [
            'account_id'        => $this->account?->id,
            'order_id'          => $r['orderId']          ?? null,
            'date'              => $r['date']              ?? null,
            'last_change_date'  => $r['lastChangeDate']   ?? null,
            'supplier_article'  => $r['supplierArticle']  ?? null,
            'tech_size'         => $r['techSize']          ?? null,
            'barcode'           => $r['barcode']           ?? null,
            'total_price'       => $r['totalPrice']        ?? 0,
            'discount_percent'  => $r['discountPercent']   ?? 0,
            'warehouse_name'    => $r['warehouseName']     ?? null,
            'oblast'            => $r['oblast']            ?? null,
            'income_id'         => $r['incomeID']          ?? null,
            'odid'              => $r['odid']              ?? null,
            'nm_id'             => $r['nmId']              ?? null,
            'subject'           => $r['subject']           ?? null,
            'category'          => $r['category']          ?? null,
            'brand'             => $r['brand']             ?? null,
            'is_cancel'         => $r['isCancel']          ?? false,
            'cancel_dt'         => $r['cancel_dt']         ?? null,
            'raw'               => json_encode($r),
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }

    private function mapSale(array $r): array
    {
        return [
            'account_id'        => $this->account?->id,
            'sale_id'           => $r['saleID']           ?? null,
            'date'              => $r['date']              ?? null,
            'last_change_date'  => $r['lastChangeDate']   ?? null,
            'supplier_article'  => $r['supplierArticle']  ?? null,
            'tech_size'         => $r['techSize']          ?? null,
            'barcode'           => $r['barcode']           ?? null,
            'total_price'       => $r['totalPrice']        ?? 0,
            'discount_percent'  => $r['discountPercent']   ?? 0,
            'is_supply'         => $r['isSupply']          ?? false,
            'is_realization'    => $r['isRealization']     ?? false,
            'warehouse_name'    => $r['warehouseName']     ?? null,
            'oblast'            => $r['oblast']            ?? null,
            'income_id'         => $r['incomeID']          ?? null,
            'odid'              => $r['odid']              ?? null,
            'nm_id'             => $r['nmId']              ?? null,
            'subject'           => $r['subject']           ?? null,
            'category'          => $r['category']          ?? null,
            'brand'             => $r['brand']             ?? null,
            'for_pay'           => $r['forPay']            ?? 0,
            'finished_price'    => $r['finishedPrice']     ?? 0,
            'price_with_disc'   => $r['priceWithDisc']     ?? 0,
            'raw'               => json_encode($r),
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }

    private function mapStock(array $r): array
    {
        return [
            'account_id'             => $this->account?->id,
            'last_change_date'       => $r['lastChangeDate']      ?? null,
            'supplier_article'       => $r['supplierArticle']     ?? null,
            'tech_size'              => $r['techSize']             ?? null,
            'barcode'                => $r['barcode']              ?? null,
            'quantity'               => $r['quantity']             ?? 0,
            'is_supply'              => $r['isSupply']             ?? false,
            'is_realization'         => $r['isRealization']        ?? false,
            'quantity_full'          => $r['quantityFull']         ?? 0,
            'quantity_not_in_orders' => $r['quantityNotInOrders']  ?? 0,
            'warehouse_name'         => $r['warehouseName']        ?? null,
            'in_way_to_client'       => $r['inWayToClient']        ?? 0,
            'in_way_from_client'     => $r['inWayFromClient']      ?? 0,
            'nm_id'                  => $r['nmId']                 ?? null,
            'subject'                => $r['subject']              ?? null,
            'category'               => $r['category']             ?? null,
            'brand'                  => $r['brand']                ?? null,
            'sc_code'                => $r['SCCode']               ?? null,
            'price'                  => $r['Price']                ?? 0,
            'discount'               => $r['Discount']             ?? 0,
            'raw'                    => json_encode($r),
            'created_at'             => now(),
            'updated_at'             => now(),
        ];
    }

    private function mapIncome(array $r): array
    {
        return [
            'account_id'       => $this->account?->id,
            'income_id'        => $r['incomeId']        ?? null,
            'number'           => $r['number']           ?? null,
            'date'             => $r['date']             ?? null,
            'last_change_date' => $r['lastChangeDate']  ?? null,
            'supplier_article' => $r['supplierArticle'] ?? null,
            'tech_size'        => $r['techSize']         ?? null,
            'barcode'          => $r['barcode']          ?? null,
            'quantity'         => $r['quantity']         ?? 0,
            'total_price'      => $r['totalPrice']       ?? 0,
            'date_close'       => $r['dateClose']        ?? null,
            'warehouse_name'   => $r['warehouseName']    ?? null,
            'nm_id'            => $r['nmId']             ?? null,
            'status'           => $r['status']           ?? null,
            'raw'              => json_encode($r),
            'created_at'       => now(),
            'updated_at'       => now(),
        ];
    }

    private function mapReportDetail(array $r): array
    {
        return [
            'account_id'                   => $this->account?->id,
            'rrd_id'                       => $r['rrdId']                      ?? null,
            'date_from'                    => $r['dateFrom']                   ?? null,
            'date_to'                      => $r['dateTo']                     ?? null,
            'create_dt'                    => $r['createDt']                   ?? null,
            'supplier_name'                => $r['supplierName']               ?? null,
            'doc_type_name'                => $r['docTypeName']                ?? null,
            'quantity_x'                   => $r['quantityX']                  ?? 0,
            'retail_price'                 => $r['retailPrice']                ?? 0,
            'retail_amount'                => $r['retailAmount']               ?? 0,
            'sale_percent'                 => $r['salePercent']                ?? 0,
            'commission_percent'           => $r['commissionPercent']          ?? 0,
            'supplier_oper_name'           => $r['supplierOperName']           ?? null,
            'order_dt'                     => $r['orderDt']                    ?? null,
            'sale_dt'                      => $r['saleDt']                     ?? null,
            'rr_dt'                        => $r['rrDt']                       ?? null,
            'shk_id'                       => $r['shkId']                      ?? null,
            'retail_price_withdisc_rub'    => $r['retailPriceWithdiscRub']     ?? 0,
            'delivery_amount'              => $r['deliveryAmount']             ?? 0,
            'return_amount'                => $r['returnAmount']               ?? 0,
            'delivery_rub'                 => $r['deliveryRub']                ?? 0,
            'gi_box_type_name'             => $r['giBoxTypeName']              ?? null,
            'product_discount_for_report'  => $r['productDiscountForReport']   ?? 0,
            'supplier_promo'               => $r['supplierPromo']              ?? 0,
            'ppvz_spp_prc'                 => $r['ppvzSppPrc']                 ?? 0,
            'ppvz_kvw_prc_base'            => $r['ppvzKvwPrcBase']             ?? 0,
            'ppvz_kvw_prc'                 => $r['ppvzKvwPrc']                 ?? 0,
            'ppvz_sales_commission'        => $r['ppvzSalesCommission']        ?? 0,
            'ppvz_for_pay'                 => $r['ppvzForPay']                 ?? 0,
            'ppvz_reward'                  => $r['ppvzReward']                 ?? 0,
            'ppvz_vw'                      => $r['ppvzVw']                     ?? 0,
            'ppvz_vw_nds'                  => $r['ppvzVwNds']                  ?? 0,
            'ppvz_office_name'             => $r['ppvzOfficeName']             ?? null,
            'ppvz_supplier_id'             => $r['ppvzSupplierId']             ?? null,
            'ppvz_supplier_name'           => $r['ppvzSupplierName']           ?? null,
            'ppvz_inn'                     => $r['ppvzInn']                    ?? null,
            'declaration_number'           => $r['declarationNumber']          ?? null,
            'bonus_type_name'              => $r['bonusTypeName']              ?? null,
            'sticker_id'                   => $r['stickerId']                  ?? null,
            'site_country'                 => $r['siteCountry']                ?? null,
            'penalty'                      => $r['penalty']                    ?? 0,
            'additional_payment'           => $r['additionalPayment']          ?? 0,
            'nm_id'                        => $r['nmId']                       ?? null,
            'brand_name'                   => $r['brandName']                  ?? null,
            'subject_name'                 => $r['subjectName']                ?? null,
            'raw'                          => json_encode($r),
            'created_at'                   => now(),
            'updated_at'                   => now(),
        ];
    }
}