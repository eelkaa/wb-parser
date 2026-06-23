<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = ['company_id', 'name'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function reportDetails(): HasMany
    {
        return $this->hasMany(ReportDetail::class);
    }

    public function dataRecords(): HasMany
    {
        return $this->hasMany(DataRecord::class);
    }

    /**
     * Получить токен для конкретного сервиса и типа.
     */
    public function getToken(int $apiServiceId, int $tokenTypeId): ?Token
    {
        return $this->tokens()
            ->where('api_service_id', $apiServiceId)
            ->where('token_type_id', $tokenTypeId)
            ->first();
    }
}
