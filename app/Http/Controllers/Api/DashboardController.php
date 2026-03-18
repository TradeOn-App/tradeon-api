<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashFlowTransaction;
use App\Models\Client;
use App\Models\ClientTransaction;
use App\Models\CommissionTransaction;
use App\Models\MonthlyReport;
use App\Models\P2pOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function clientMetrics(Request $request)
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        $transactions = ClientTransaction::where('client_id', $client->id)->get();

        $totalDeposits = $transactions->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = $transactions->where('type', 'withdrawal')->sum('amount');
        $balance = $totalDeposits - $totalWithdrawals;
        $transactionCount = $transactions->count();

        $reports = MonthlyReport::where('client_id', $client->id)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $chartData = $reports->map(function ($r) {
            return [
                'period' => str_pad($r->month, 2, '0', STR_PAD_LEFT) . '/' . $r->year,
                'deposits' => (float) $r->total_deposits,
                'withdrawals' => (float) $r->total_withdrawals,
                'profitability' => (float) $r->profitability_percent,
                'net' => (float) ($r->total_deposits - $r->total_withdrawals),
            ];
        });

        return response()->json([
            'cards' => [
                'total_deposits' => (float) $totalDeposits,
                'total_withdrawals' => (float) $totalWithdrawals,
                'balance' => (float) $balance,
                'transaction_count' => $transactionCount,
            ],
            'chart' => $chartData,
        ]);
    }

    public function adminMetrics(Request $request)
    {
        $totalClients = Client::where('is_active', true)->count();

        $totalDeposits = ClientTransaction::where('type', 'deposit')->sum('amount');
        $totalWithdrawals = ClientTransaction::where('type', 'withdrawal')->sum('amount');

        $totalP2p = P2pOperation::sum('amount');
        $p2pCount = P2pOperation::count();

        $totalCommissions = CommissionTransaction::sum('amount');

        $cashFlowEntries = CashFlowTransaction::where('type', 'entry')->sum('amount');
        $cashFlowExits = CashFlowTransaction::where('type', 'exit')->sum('amount');

        $monthlyData = DB::table('monthly_reports')
            ->select(
                'year',
                'month',
                DB::raw('SUM(total_deposits) as deposits'),
                DB::raw('SUM(total_withdrawals) as withdrawals'),
                DB::raw('AVG(profitability_percent) as avg_profitability')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($r) {
                return [
                    'period' => str_pad($r->month, 2, '0', STR_PAD_LEFT) . '/' . $r->year,
                    'deposits' => (float) $r->deposits,
                    'withdrawals' => (float) $r->withdrawals,
                    'avg_profitability' => round((float) $r->avg_profitability, 2),
                    'net' => (float) $r->deposits - (float) $r->withdrawals,
                ];
            });

        $p2pMonthly = DB::table('p2p_operations')
            ->select(
                DB::raw("EXTRACT(YEAR FROM operation_date) as year"),
                DB::raw("EXTRACT(MONTH FROM operation_date) as month"),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($r) {
                return [
                    'period' => str_pad((int) $r->month, 2, '0', STR_PAD_LEFT) . '/' . (int) $r->year,
                    'total' => (float) $r->total,
                    'count' => (int) $r->count,
                ];
            });

        return response()->json([
            'cards' => [
                'total_clients' => $totalClients,
                'total_deposits' => (float) $totalDeposits,
                'total_withdrawals' => (float) $totalWithdrawals,
                'balance' => (float) ($totalDeposits - $totalWithdrawals),
                'total_p2p' => (float) $totalP2p,
                'p2p_count' => $p2pCount,
                'total_commissions' => (float) $totalCommissions,
                'cash_flow_entries' => (float) $cashFlowEntries,
                'cash_flow_exits' => (float) $cashFlowExits,
            ],
            'chart' => $monthlyData,
            'p2p_chart' => $p2pMonthly,
        ]);
    }
}
