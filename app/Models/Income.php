<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    protected $fillable = [
        'account_id',
        'income_id', 'number', 'date', 'last_change_date',
        'supplier_article', 'tech_size', 'barcode', 'quantity',
        'total_price', 'date_close', 'warehouse_name', 'nm_id', 'status', 'raw',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public static function getLastDate(int $accountId): ?string
    {
        return static::where('account_id', $accountId)->max('date');
    }
}
