<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'sale_id', 'date', 'last_change_date', 'supplier_article',
        'tech_size', 'barcode', 'total_price', 'discount_percent',
        'is_supply', 'is_realization', 'warehouse_name', 'oblast',
        'income_id', 'odid', 'nm_id', 'subject', 'category', 'brand',
        'for_pay', 'finished_price', 'price_with_disc', 'raw',
    ];

    protected $casts = [
        'total_price'    => 'float',
        'for_pay'        => 'float',
        'finished_price' => 'float',
        'price_with_disc'=> 'float',
    ];
}
