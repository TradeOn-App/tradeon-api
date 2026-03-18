<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = ['code', 'name', 'symbol', 'is_crypto'];

    protected function casts(): array
    {
        return ['is_crypto' => 'boolean'];
    }

    public function cashFlowTransactions(): HasMany
    {
        return $this->hasMany(CashFlowTransaction::class, 'currency_id');
    }

    public function p2pOperations(): HasMany
    {
        return $this->hasMany(P2pOperation::class, 'currency_id');
    }
}
