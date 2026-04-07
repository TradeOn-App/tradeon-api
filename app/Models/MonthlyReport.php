<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReport extends Model
{
    protected $fillable = [
        'client_id',
        'month',
        'year',
        'total_deposits',
        'total_withdrawals',
        'profitability_percent',
        'initial_value',
        'updated_value',
        'real_gain',
        'gain_percentage',
        'commission_value',
        'profit_value',
        'next_month_initial',
        'commission_rate',
        'initial_debit',
        'initial_value_brl',
        'updated_value_brl',
        'real_gain_brl',
        'total_deposits_brl',
        'total_withdrawals_brl',
        'commission_value_brl',
        'profit_value_brl',
        'next_month_initial_brl',
        'summary',
        'generated_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'total_deposits' => 'decimal:8',
            'total_withdrawals' => 'decimal:8',
            'profitability_percent' => 'decimal:4',
            'initial_value' => 'decimal:8',
            'updated_value' => 'decimal:8',
            'real_gain' => 'decimal:8',
            'gain_percentage' => 'decimal:4',
            'commission_value' => 'decimal:8',
            'profit_value' => 'decimal:8',
            'next_month_initial' => 'decimal:8',
            'commission_rate' => 'decimal:4',
            'initial_debit' => 'decimal:8',
            'initial_value_brl' => 'decimal:8',
            'updated_value_brl' => 'decimal:8',
            'real_gain_brl' => 'decimal:8',
            'total_deposits_brl' => 'decimal:8',
            'total_withdrawals_brl' => 'decimal:8',
            'commission_value_brl' => 'decimal:8',
            'profit_value_brl' => 'decimal:8',
            'next_month_initial_brl' => 'decimal:8',
            'summary' => 'array',
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getPeriodAttribute(): string
    {
        return sprintf('%02d/%d', $this->month, $this->year);
    }
}
