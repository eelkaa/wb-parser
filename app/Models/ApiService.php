<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiService extends Model
{
    protected $fillable = ['name', 'description'];

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }
}
