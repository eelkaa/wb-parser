<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataRecord extends Model
{
    protected $fillable = [
        'account_id',
        'endpoint',
        'record_date',
        'payload',
    ];

    protected $casts = [
        'payload'     => 'array',
        'record_date' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Получить дату последней записи для аккаунта и эндпоинта.
     */
    public static function getLastDate(int $accountId, string $endpoint): ?string
    {
        $record = static::where('account_id', $accountId)
            ->where('endpoint', $endpoint)
            ->orderByDesc('record_date')
            ->first();

        return $record ? $record->record_date->format('Y-m-d') : null;
    }
}
