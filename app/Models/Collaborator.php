<?php

namespace App\Models;

use App\Casts\EncryptedField;
use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    protected $fillable = [
        'name',
        'cpf',
        'wallet',
        'commission',
        'fixed',
        'commission_rule_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'commission' => 'decimal:4',
            'fixed' => 'decimal:8',
            'cpf' => EncryptedField::class,
            'wallet' => EncryptedField::class,
        ];
    }

    public function internalTransactions()
    {
        return $this->hasMany(\App\Models\InternalTransaction::class);
    }

    public function commissionRule()
    {
        return $this->belongsTo(CommissionRule::class);
    }

    public function commissionTransactions()
    {
        return $this->hasMany(CommissionTransaction::class);
    }
}
