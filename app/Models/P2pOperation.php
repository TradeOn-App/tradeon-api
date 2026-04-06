<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class P2pOperation extends Model
{
    protected $table = 'p2p_operations';

    protected $fillable = [
        'cash_flow_transaction_id',
        'currency_id',
        'amount',
        'to_whom',
        'reason',
        'operation_date',
        'reference',
        'wallet_from',
        'wallet_to',
        'dollar_quotation',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'dollar_quotation' => 'decimal:8',
            'operation_date' => 'date',
        ];
    }

    public function cashFlowTransaction(): BelongsTo
    {
        return $this->belongsTo(CashFlowTransaction::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
