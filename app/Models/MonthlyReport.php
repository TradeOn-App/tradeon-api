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
        'summary',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'total_deposits' => 'decimal:8',
            'total_withdrawals' => 'decimal:8',
            'profitability_percent' => 'decimal:4',
            'summary' => 'array',
            'generated_at' => 'datetime',
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
