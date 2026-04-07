<?php

namespace App\Models;

use App\Casts\EncryptedField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalTransaction extends Model
{
    protected $fillable = [
        'collaborator_id',
        'type',
        'amount',
        'currency_id',
        'network_id',
        'transaction_date',
        'quotation_at_transaction',
        'wallet_destination',
        'tx_hash',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'quotation_at_transaction' => 'decimal:8',
            'transaction_date' => 'date',
            'wallet_destination' => EncryptedField::class,
            'tx_hash' => EncryptedField::class,
        ];
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
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
}
