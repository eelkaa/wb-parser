<?php

namespace App\Console\Commands;

use App\Models\Income;
use App\Models\Order;
use App\Models\ReportDetail;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\WbApiService;
use Illuminate\Console\Command;

class FetchWbData extends Command
{
    protected $signature = 'wb:fetch
                            {--dateFrom= : Дата выгрузки ОТ (YYYY-MM-DD), по умолчанию из .env}
                            {--dateTo=   : Дата выгрузки ДО (YYYY-MM-DD), по умолчанию из .env}
                            {--endpoint= : Конкретный эндпоинт: orders|sales|stocks|incomes|reportDetail}';

    protected $description = 'Стягивает данные из Wildberries API и сохраняет в БД';

    public function __construct(protected WbApiService $api)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dateFrom = $this->option('dateFrom') ?? config('wb.date_from');
        $dateTo   = $this->option('dateTo')   ?? config('wb.date_to');
        $only     = $this->option('endpoint');

        $this->info("Период: {$dateFrom} → {$dateTo}");

        $endpoints = [
            'orders'       => fn() => $this->syncOrders($dateFrom, $dateTo),
            'sales'        => fn() => $this->syncSales($dateFrom, $dateTo),
            'stocks'       => fn() => $this->syncStocks($dateFrom, $dateTo),
            'incomes'      => fn() => $this->syncIncomes($dateFrom, $dateTo),
            'reportDetail' => fn() => $this->syncReportDetail($dateFrom, $dateTo),
        ];

        if ($only) {
            if (!isset($endpoints[$only])) {
                $this->error("Неизвестный эндпоинт: {$only}. Доступные: " . implode(', ', array_keys($endpoints)));
                return self::FAILURE;
            }
            $endpoints[$only]();
        } else {
            foreach ($endpoints as $name => $fn) {
                $fn();
            }
        }

        $this->info('✅ Готово!');
        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function syncOrders(string $dateFrom, string $dateTo): void
    {
        $this->info('📦 Загрузка заказов...');
        $rows = $this->api->getOrders($dateFrom, $dateTo);
        $this->info("  Получено записей: " . count($rows));

        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => $this->mapOrder($r), $chunk);
            Order::upsert($data, ['order_id'], array_keys($data[0]));
        }
        $this->info('  Заказы сохранены.');
    }

    private function syncSales(string $dateFrom, string $dateTo): void
    {
        $this->info('💰 Загрузка продаж...');
        $rows = $this->api->getSales($dateFrom, $dateTo);
        $this->info("  Получено записей: " . count($rows));

        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => $this->mapSale($r), $chunk);
            Sale::upsert($data, ['sale_id'], array_keys($data[0]));
        }
        $this->info('  Продажи сохранены.');
    }

    private function syncStocks(string $dateFrom, string $dateTo): void
    {
        $this->info('🏪 Загрузка остатков...');
        $rows = $this->api->getStocks($dateFrom, $dateTo);
        $this->info("  Получено записей: " . count($rows));

        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => $this->mapStock($r), $chunk);
            Stock::upsert($data, ['id'], array_keys($data[0]));
        }
        $this->info('  Остатки сохранены.');
    }

    private function syncIncomes(string $dateFrom, string $dateTo): void
    {
        $this->info('📥 Загрузка поставок...');
        $rows = $this->api->getIncomes($dateFrom, $dateTo);
        $this->info("  Получено записей: " . count($rows));

        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => $this->mapIncome($r), $chunk);
            Income::upsert($data, ['income_id'], array_keys($data[0]));
        }
        $this->info('  Поставки сохранены.');
    }

    private function syncReportDetail(string $dateFrom, string $dateTo): void
    {
        $this->info('📊 Загрузка детального отчёта...');
        $rows = $this->api->getReportDetail($dateFrom, $dateTo);
        $this->info("  Получено записей: " . count($rows));

        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => $this->mapReportDetail($r), $chunk);
            ReportDetail::upsert($data, ['rrd_id'], array_keys($data[0]));
        }
        $this->info('  Детальный отчёт сохранён.');
    }

    // -------------------------------------------------------------------------
    // Маппинг полей API → колонки БД
    // -------------------------------------------------------------------------

    private function mapOrder(array $r): array
    {
        return [
            'order_id'         => $r['g_number']        ?? null,
            'date'             => $r['date']             ?? null,
            'last_change_date' => $r['lastChangeDate']   ?? null,
            'supplier_article' => $r['supplierArticle']  ?? null,
            'tech_size'        => $r['techSize']         ?? null,
            'barcode'          => $r['barcode']          ?? null,
            'total_price'      => $r['totalPrice']       ?? null,
            'discount_percent' => $r['discountPercent']  ?? null,
            'warehouse_name'   => $r['warehouseName']    ?? null,
            'oblast'           => $r['oblast']           ?? null,
            'income_id'        => $r['incomeID']         ?? null,
            'odid'             => $r['odid']             ?? null,
            'nm_id'            => $r['nmId']             ?? null,
            'subject'          => $r['subject']          ?? null,
            'category'         => $r['category']         ?? null,
            'brand'            => $r['brand']            ?? null,
            'is_cancel'        => $r['isCancel']         ?? false,
            'cancel_dt'        => $r['cancel_dt']        ?? null,
            'raw'              => json_encode($r),
        ];
    }

    private function mapSale(array $r): array
    {
        return [
            'sale_id'          => $r['saleID']           ?? null,
            'date'             => $r['date']             ?? null,
            'last_change_date' => $r['lastChangeDate']   ?? null,
            'supplier_article' => $r['supplierArticle']  ?? null,
            'tech_size'        => $r['techSize']         ?? null,
            'barcode'          => $r['barcode']          ?? null,
            'total_price'      => $r['totalPrice']       ?? null,
            'discount_percent' => $r['discountPercent']  ?? null,
            'is_supply'        => $r['isSupply']         ?? null,
            'is_realization'   => $r['isRealization']    ?? null,
            'warehouse_name'   => $r['warehouseName']    ?? null,
            'oblast'           => $r['oblast']           ?? null,
            'income_id'        => $r['incomeID']         ?? null,
            'odid'             => $r['odid']             ?? null,
            'nm_id'            => $r['nmId']             ?? null,
            'subject'          => $r['subject']          ?? null,
            'category'         => $r['category']         ?? null,
            'brand'            => $r['brand']            ?? null,
            'for_pay'          => $r['forPay']           ?? null,
            'finished_price'   => $r['finishedPrice']    ?? null,
            'price_with_disc'  => $r['priceWithDisc']    ?? null,
            'raw'              => json_encode($r),
        ];
    }

    private function mapStock(array $r): array
    {
        return [
            'last_change_date'       => $r['lastChangeDate']      ?? null,
            'supplier_article'       => $r['supplierArticle']     ?? null,
            'tech_size'              => $r['techSize']            ?? null,
            'barcode'                => $r['barcode']             ?? null,
            'quantity'               => $r['quantity']            ?? null,
            'is_supply'              => $r['isSupply']            ?? null,
            'is_realization'         => $r['isRealization']       ?? null,
            'quantity_full'          => $r['quantityFull']        ?? null,
            'quantity_not_in_orders' => $r['quantityNotInOrders'] ?? null,
            'warehouse_name'         => $r['warehouseName']       ?? null,
            'in_way_to_client'       => $r['inWayToClient']       ?? null,
            'in_way_from_client'     => $r['inWayFromClient']     ?? null,
            'nm_id'                  => $r['nmId']                ?? null,
            'subject'                => $r['subject']             ?? null,
            'category'               => $r['category']            ?? null,
            'brand'                  => $r['brand']               ?? null,
            'sc_code'                => $r['SCCode']              ?? null,
            'price'                  => $r['Price']               ?? null,
            'discount'               => $r['Discount']            ?? null,
            'raw'                    => json_encode($r),
        ];
    }

    private function mapIncome(array $r): array
    {
        return [
            'income_id'        => $r['incomeId']        ?? null,
            'number'           => $r['number']          ?? null,
            'date'             => $r['date']            ?? null,
            'last_change_date' => $r['lastChangeDate']  ?? null,
            'supplier_article' => $r['supplierArticle'] ?? null,
            'tech_size'        => $r['techSize']        ?? null,
            'barcode'          => $r['barcode']         ?? null,
            'quantity'         => $r['quantity']        ?? null,
            'total_price'      => $r['totalPrice']      ?? null,
            'date_close'       => $r['dateClose']       ?? null,
            'warehouse_name'   => $r['warehouseName']   ?? null,
            'nm_id'            => $r['nmId']            ?? null,
            'status'           => $r['status']          ?? null,
            'raw'              => json_encode($r),
        ];
    }

    private function mapReportDetail(array $r): array
    {
        return [
            'rrd_id'                      => $r['rrdId']                   ?? null,
            'date_from'                   => $r['dateFrom']                ?? null,
            'date_to'                     => $r['dateTo']                  ?? null,
            'create_dt'                   => $r['createDt']                ?? null,
            'supplier_name'               => $r['supplierName']            ?? null,
            'doc_type_name'               => $r['docTypeName']             ?? null,
            'quantity_x'                  => $r['quantityX']               ?? null,
            'retail_price'                => $r['retailPrice']             ?? null,
            'retail_amount'               => $r['retailAmount']            ?? null,
            'sale_percent'                => $r['salePercent']             ?? null,
            'commission_percent'          => $r['commissionPercent']       ?? null,
            'supplier_oper_name'          => $r['supplierOperName']        ?? null,
            'order_dt'                    => $r['orderDt']                 ?? null,
            'sale_dt'                     => $r['saleDt']                  ?? null,
            'rr_dt'                       => $r['rrDt']                    ?? null,
            'shk_id'                      => $r['shkId']                   ?? null,
            'retail_price_withdisc_rub'   => $r['retailPriceWithdiscRub']  ?? null,
            'delivery_amount'             => $r['deliveryAmount']          ?? null,
            'return_amount'               => $r['returnAmount']            ?? null,
            'delivery_rub'                => $r['deliveryRub']             ?? null,
            'gi_box_type_name'            => $r['giBoxTypeName']           ?? null,
            'product_discount_for_report' => $r['productDiscountForReport'] ?? null,
            'supplier_promo'              => $r['supplierPromo']           ?? null,
            'ppvz_spp_prc'                => $r['ppvzSppPrc']              ?? null,
            'ppvz_kvw_prc_base'           => $r['ppvzKvwPrcBase']          ?? null,
            'ppvz_kvw_prc'                => $r['ppvzKvwPrc']              ?? null,
            'ppvz_sales_commission'       => $r['ppvzSalesCommission']     ?? null,
            'ppvz_for_pay'                => $r['ppvzForPay']              ?? null,
            'ppvz_reward'                 => $r['ppvzReward']              ?? null,
            'ppvz_vw'                     => $r['ppvzVw']                  ?? null,
            'ppvz_vw_nds'                 => $r['ppvzVwNds']               ?? null,
            'ppvz_office_name'            => $r['ppvzOfficeName']          ?? null,
            'ppvz_supplier_id'            => $r['ppvzSupplierId']          ?? null,
            'ppvz_supplier_name'          => $r['ppvzSupplierName']        ?? null,
            'ppvz_inn'                    => $r['ppvzInn']                 ?? null,
            'declaration_number'          => $r['declarationNumber']       ?? null,
            'bonus_type_name'             => $r['bonusTypeName']           ?? null,
            'sticker_id'                  => $r['stickerId']               ?? null,
            'site_country'                => $r['siteCountry']             ?? null,
            'penalty'                     => $r['penalty']                 ?? null,
            'additional_payment'          => $r['additionalPayment']       ?? null,
            'nm_id'                       => $r['nmId']                    ?? null,
            'brand_name'                  => $r['brandName']               ?? null,
            'subject_name'                => $r['subjectName']             ?? null,
            'raw'                         => json_encode($r),
        ];
    }
}