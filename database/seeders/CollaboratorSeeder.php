<?php

namespace Database\Seeders;

use App\Models\Collaborator;
use App\Models\CommissionRule;
use Illuminate\Database\Seeder;

class CollaboratorSeeder extends Seeder
{
    public function run(): void
    {
        $ruleStandard = CommissionRule::where('name', 'Comissão Padrão P2P')->first();
        $rulePremium = CommissionRule::where('name', 'Comissão Premium')->first();
        $ruleFixed = CommissionRule::where('name', 'Comissão Fixa por Transação')->first();

        $collaborators = [
            [
                'name' => 'André Martins',
                'cpf' => '111.222.333-44',
                'wallet' => 'TRX1a2b3c4d5e6f7g8h9i0jAndre',
                'commission_rule_id' => $ruleStandard?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Beatriz Costa',
                'cpf' => '555.666.777-88',
                'wallet' => 'TRX9z8y7x6w5v4u3t2s1rBeatriz',
                'commission_rule_id' => $rulePremium?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Diego Ferreira',
                'cpf' => '999.000.111-22',
                'wallet' => 'TRXq1w2e3r4t5y6u7i8o9pDiego',
                'commission_rule_id' => $ruleFixed?->id,
                'is_active' => true,
            ],
        ];

        foreach ($collaborators as $data) {
            Collaborator::firstOrCreate(
                ['cpf' => $data['cpf']],
                $data
            );
        }
    }
}
