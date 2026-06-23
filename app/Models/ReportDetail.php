<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportDetail extends Model
{
    protected $fillable = [
        'account_id',
        'rrd_id', 'date_from', 'date_to', 'create_dt', 'supplier_name',
        'doc_type_name', 'quantity_x', 'retail_price', 'retail_amount',
        'sale_percent', 'commission_percent', 'supplier_oper_name',
        'order_dt', 'sale_dt', 'rr_dt', 'shk_id',
        'retail_price_withdisc_rub', 'delivery_amount', 'return_amount',
        'delivery_rub', 'gi_box_type_name', 'product_discount_for_report',
        'supplier_promo', 'ppvz_spp_prc', 'ppvz_kvw_prc_base', 'ppvz_kvw_prc',
        'ppvz_sales_commission', 'ppvz_for_pay', 'ppvz_reward', 'ppvz_vw',
        'ppvz_vw_nds', 'ppvz_office_name', 'ppvz_supplier_id',
        'ppvz_supplier_name', 'ppvz_inn', 'declaration_number',
        'bonus_type_name', 'sticker_id', 'site_country', 'penalty',
        'additional_payment', 'nm_id', 'brand_name', 'subject_name', 'raw',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public static function getLastDate(int $accountId): ?string
    {
        return static::where('account_id', $accountId)->max('date_from');
    }
}
