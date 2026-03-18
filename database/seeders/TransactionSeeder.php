<?php

namespace Database\Seeders;

use App\Models\CashFlowTransaction;
use App\Models\Client;
use App\Models\ClientTransaction;
use App\Models\Collaborator;
use App\Models\CommissionRule;
use App\Models\CommissionTransaction;
use App\Models\Currency;
use App\Models\MonthlyReport;
use App\Models\Network;
use App\Models\P2pOperation;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@lucastrade.local')->first();
        $brl = Currency::where('code', 'BRL')->first();
        $usdt = Currency::where('code', 'USDT')->first();
        $trc20 = Network::where('slug', 'trc20')->first();
        $erc20 = Network::where('slug', 'erc20')->first();
        $clients = Client::all();
        $collaborators = Collaborator::all();

        $months = [
            ['year' => 2025, 'month' => 9],
            ['year' => 2025, 'month' => 10],
            ['year' => 2025, 'month' => 11],
            ['year' => 2025, 'month' => 12],
            ['year' => 2026, 'month' => 1],
            ['year' => 2026, 'month' => 2],
        ];

        foreach ($months as $period) {
            $y = $period['year'];
            $m = str_pad($period['month'], 2, '0', STR_PAD_LEFT);

            foreach ($clients as $idx => $client) {
                $depositAmount = rand(5000, 30000);
                $withdrawAmount = rand(1000, (int)($depositAmount * 0.3));
                $usdtEquiv = round($depositAmount / 5.8, 8);

                $cfDeposit = CashFlowTransaction::create([
                    'type' => 'entry',
                    'currency_id' => $brl->id,
                    'network_id' => $trc20->id,
                    'amount' => $depositAmount,
                    'amount_usdt_equivalent' => $usdtEquiv,
                    'quotation_at_transaction' => 5.80,
                    'wallet_origin' => 'WALLET_CLIENT_' . ($idx + 1),
                    'wallet_destination' => 'WALLET_MASTER',
                    'tx_hash' => 'TX' . strtoupper(substr(md5("{$y}{$m}dep{$idx}"), 0, 20)),
                    'description' => "Aporte {$m}/{$y} - {$client->full_name}",
                    'transaction_date' => "{$y}-{$m}-05",
                    'created_by' => $admin->id,
                ]);

                ClientTransaction::create([
                    'client_id' => $client->id,
                    'cash_flow_transaction_id' => $cfDeposit->id,
                    'type' => 'deposit',
                    'amount' => $depositAmount,
                ]);

                $cfWithdraw = CashFlowTransaction::create([
                    'type' => 'exit',
                    'currency_id' => $brl->id,
                    'network_id' => $trc20->id,
                    'amount' => $withdrawAmount,
                    'amount_usdt_equivalent' => round($withdrawAmount / 5.8, 8),
                    'quotation_at_transaction' => 5.80,
                    'wallet_origin' => 'WALLET_MASTER',
                    'wallet_destination' => 'WALLET_CLIENT_' . ($idx + 1),
                    'tx_hash' => 'TX' . strtoupper(substr(md5("{$y}{$m}wit{$idx}"), 0, 20)),
                    'description' => "Saque {$m}/{$y} - {$client->full_name}",
                    'transaction_date' => "{$y}-{$m}-20",
                    'created_by' => $admin->id,
                ]);

                ClientTransaction::create([
                    'client_id' => $client->id,
                    'cash_flow_transaction_id' => $cfWithdraw->id,
                    'type' => 'withdrawal',
                    'amount' => $withdrawAmount,
                ]);

                $profitability = round(rand(200, 800) / 100, 4);

                MonthlyReport::updateOrCreate(
                    ['client_id' => $client->id, 'month' => $period['month'], 'year' => $y],
                    [
                        'total_deposits' => $depositAmount,
                        'total_withdrawals' => $withdrawAmount,
                        'profitability_percent' => $profitability,
                        'summary' => [
                            'net' => $depositAmount - $withdrawAmount,
                            'transactions_count' => 2,
                        ],
                        'generated_at' => "{$y}-{$m}-28 23:59:59",
                    ]
                );
            }

            $p2pAmounts = [rand(1000, 8000), rand(2000, 12000)];
            $p2pNames = ['João Silva', 'Maria Oliveira'];
            $networks = [$trc20, $erc20];

            foreach ($p2pAmounts as $pIdx => $p2pAmount) {
                $cfP2p = CashFlowTransaction::create([
                    'type' => 'exit',
                    'currency_id' => $usdt->id,
                    'network_id' => $networks[$pIdx]->id,
                    'amount' => $p2pAmount,
                    'amount_usdt_equivalent' => $p2pAmount,
                    'quotation_at_transaction' => 1.00,
                    'wallet_origin' => 'WALLET_MASTER',
                    'wallet_destination' => 'WALLET_P2P_' . ($pIdx + 1),
                    'tx_hash' => 'TXP2P' . strtoupper(substr(md5("{$y}{$m}p2p{$pIdx}"), 0, 16)),
                    'description' => "P2P {$m}/{$y} - {$p2pNames[$pIdx]}",
                    'transaction_date' => "{$y}-{$m}-" . str_pad(10 + $pIdx * 5, 2, '0', STR_PAD_LEFT),
                    'created_by' => $admin->id,
                ]);

                P2pOperation::create([
                    'cash_flow_transaction_id' => $cfP2p->id,
                    'currency_id' => $usdt->id,
                    'amount' => $p2pAmount,
                    'to_whom' => $p2pNames[$pIdx],
                    'reason' => 'Venda P2P USDT',
                    'operation_date' => "{$y}-{$m}-" . str_pad(10 + $pIdx * 5, 2, '0', STR_PAD_LEFT),
                    'reference' => 'REF-P2P-' . strtoupper(substr(md5("{$y}{$m}ref{$pIdx}"), 0, 8)),
                    'created_by' => $admin->id,
                ]);

                $collaborator = $collaborators[$pIdx % $collaborators->count()];
                $rule = $collaborator->commissionRule;

                if ($rule) {
                    $commissionAmount = $rule->type === 'percentage'
                        ? round($p2pAmount * $rule->value / 100, 8)
                        : $rule->value;

                    CommissionTransaction::create([
                        'cash_flow_transaction_id' => $cfP2p->id,
                        'commission_rule_id' => $rule->id,
                        'collaborator_id' => $collaborator->id,
                        'amount' => $commissionAmount,
                        'currency_id' => $usdt->id,
                        'paid_at' => "{$y}-{$m}-28",
                        'notes' => "Comissão P2P - {$collaborator->name}",
                    ]);
                }
            }
        }
    }
}
