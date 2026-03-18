<?php

namespace Database\Seeders;

use App\Models\CommissionRule;
use Illuminate\Database\Seeder;

class CommissionRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Comissão Padrão P2P',
                'applicable_to' => 'partner',
                'type' => 'percentage',
                'value' => 2.5000,
                'description' => 'Comissão de 2.5% sobre operações P2P',
                'is_active' => true,
                'valid_from' => '2025-01-01',
                'valid_until' => null,
            ],
            [
                'name' => 'Comissão Premium',
                'applicable_to' => 'partner',
                'type' => 'percentage',
                'value' => 1.5000,
                'description' => 'Comissão reduzida para colaboradores sênior',
                'is_active' => true,
                'valid_from' => '2025-01-01',
                'valid_until' => null,
            ],
            [
                'name' => 'Comissão Fixa por Transação',
                'applicable_to' => 'admin',
                'type' => 'fixed',
                'value' => 50.0000,
                'description' => 'Valor fixo de R$50 por transação processada',
                'is_active' => true,
                'valid_from' => '2025-01-01',
                'valid_until' => null,
            ],
        ];

        foreach ($rules as $rule) {
            CommissionRule::firstOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}
