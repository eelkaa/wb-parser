<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends Model
{
    protected $fillable = [
        'account_id',
        'api_service_id',
        'token_type_id',
        'token_value',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function apiService(): BelongsTo
    {
        return $this->belongsTo(ApiService::class);
    }

    public function tokenType(): BelongsTo
    {
        return $this->belongsTo(TokenType::class);
    }
}
