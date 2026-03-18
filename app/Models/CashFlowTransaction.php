<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashFlowTransaction extends Model
{
    protected $table = 'cash_flow_transactions';

    protected $fillable = [
        'type',
        'currency_id',
        'network_id',
        'amount',
        'amount_usdt_equivalent',
        'quotation_at_transaction',
        'wallet_origin',
        'wallet_destination',
        'tx_hash',
        'description',
        'transaction_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'amount_usdt_equivalent' => 'decimal:8',
            'quotation_at_transaction' => 'decimal:8',
            'transaction_date' => 'date',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function p2pOperation(): HasOne
    {
        return $this->hasOne(P2pOperation::class);
    }

    public function commissionTransactions(): HasMany
    {
        return $this->hasMany(CommissionTransaction::class);
    }

    public function clientTransactions(): HasMany
    {
        return $this->hasMany(ClientTransaction::class);
    }

    public function isEntry(): bool
    {
        return $this->type === 'entry';
    }

    public function isExit(): bool
    {
        return $this->type === 'exit';
    }
}
