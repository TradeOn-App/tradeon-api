<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRule extends Model
{
    protected $fillable = [
        'name',
        'applicable_to',
        'type',
        'value',
        'description',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function commissionTransactions(): HasMany
    {
        return $this->hasMany(CommissionTransaction::class, 'commission_rule_id');
    }

    public function isPercentage(): bool
    {
        return $this->type === 'percentage';
    }
}
