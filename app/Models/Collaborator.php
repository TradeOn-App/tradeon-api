<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    protected $fillable = [
        'name',
        'cpf',
        'wallet',
        'commission_rule_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function commissionRule()
    {
        return $this->belongsTo(CommissionRule::class);
    }

    public function commissionTransactions()
    {
        return $this->hasMany(CommissionTransaction::class);
    }
}
