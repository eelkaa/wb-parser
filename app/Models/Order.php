<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_id', 'date', 'last_change_date', 'supplier_article',
        'tech_size', 'barcode', 'total_price', 'discount_percent',
        'warehouse_name', 'oblast', 'income_id', 'odid', 'nm_id',
        'subject', 'category', 'brand', 'is_cancel', 'cancel_dt', 'raw',
    ];

    protected $casts = [
        'is_cancel'   => 'boolean',
        'total_price' => 'float',
    ];
}
