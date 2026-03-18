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
        'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:8'];
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
