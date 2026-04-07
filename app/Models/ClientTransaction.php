<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientTransaction extends Model
{
    protected $fillable = [
        'client_id',
        'cash_flow_transaction_id',
        'type',
        'amount',
        'initial_debit',
        'reference_month',
        'reference_year',
        'notes',
        'receipt_path',
    ];

    protected $appends = ['receipt_url'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'initial_debit' => 'decimal:8',
        ];
    }

    public function getReceiptUrlAttribute(): ?string
    {
        if (!$this->receipt_path) return null;
        return url('storage/' . $this->receipt_path);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function cashFlowTransaction(): BelongsTo
    {
        return $this->belongsTo(CashFlowTransaction::class);
    }
}
