<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'account_id',
        'order_id', 'date', 'last_change_date', 'supplier_article',
        'tech_size', 'barcode', 'total_price', 'discount_percent',
        'warehouse_name', 'oblast', 'income_id', 'odid', 'nm_id',
        'subject', 'category', 'brand', 'is_cancel', 'cancel_dt', 'raw',
    ];

    protected $casts = [
        'is_cancel'   => 'boolean',
        'total_price' => 'float',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Получить дату последней записи для аккаунта.
     */
    public static function getLastDate(int $accountId): ?string
    {
        return static::where('account_id', $accountId)->max('date');
    }
}
