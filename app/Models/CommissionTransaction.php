<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionTransaction extends Model
{
    protected $fillable = [
        'cash_flow_transaction_id',
        'commission_rule_id',
        'collaborator_id',
        'amount',
        'currency_id',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'paid_at' => 'date',
        ];
    }

    public function cashFlowTransaction(): BelongsTo
    {
        return $this->belongsTo(CashFlowTransaction::class);
    }

    public function commissionRule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }
}
