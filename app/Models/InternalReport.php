<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalReport extends Model
{
    protected $fillable = [
        'collaborator_id',
        'month',
        'year',
        'total_deposits',
        'cumulative_deposits',
        'total_withdrawals',
        'total_commission_withdrawals',
        'balance',
        'initial_value',
        'updated_value',
        'profit',
        'profit_percentage',
        'commission_rate',
        'commission_value',
        'accumulated_debt',
        'next_month_initial',
        'summary',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'total_deposits' => 'decimal:8',
            'cumulative_deposits' => 'decimal:8',
            'total_withdrawals' => 'decimal:8',
            'total_commission_withdrawals' => 'decimal:8',
            'balance' => 'decimal:8',
            'initial_value' => 'decimal:8',
            'updated_value' => 'decimal:8',
            'profit' => 'decimal:8',
            'profit_percentage' => 'decimal:4',
            'commission_rate' => 'decimal:4',
            'commission_value' => 'decimal:8',
            'accumulated_debt' => 'decimal:8',
            'next_month_initial' => 'decimal:8',
            'summary' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }
}
