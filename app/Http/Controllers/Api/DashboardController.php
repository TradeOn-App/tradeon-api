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
        $clientId = $request->get('client_id');
        $from = $request->get('from');
        $to = $request->get('to');

        // Client transactions query with optional filters
        $ctQuery = ClientTransaction::query();
        if ($clientId) {
            $ctQuery->where('client_id', $clientId);
        }
        if ($from || $to) {
            $ctQuery->whereHas('cashFlowTransaction', function ($q) use ($from, $to) {
                if ($from) $q->where('transaction_date', '>=', $from);
                if ($to) $q->where('transaction_date', '<=', $to);
            });
        }

        $totalDeposits = (clone $ctQuery)->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = (clone $ctQuery)->where('type', 'withdrawal')->sum('amount');

        // Clients count
        $totalClients = Client::where('is_active', true)->count();

        // P2P with date filter
        $p2pQuery = P2pOperation::query();
        if ($from) $p2pQuery->where('operation_date', '>=', $from);
        if ($to) $p2pQuery->where('operation_date', '<=', $to);
        $totalP2p = (clone $p2pQuery)->sum('amount');
        $p2pCount = (clone $p2pQuery)->count();

        // Commissions with date filter
        $commQuery = CommissionTransaction::query();
        if ($from || $to) {
            $commQuery->whereHas('cashFlowTransaction', function ($q) use ($from, $to) {
                if ($from) $q->where('transaction_date', '>=', $from);
                if ($to) $q->where('transaction_date', '<=', $to);
            });
        }
        $totalCommissions = $commQuery->sum('amount');

        // Cash flow with date filter
        $cfEntryQuery = CashFlowTransaction::where('type', 'entry');
        $cfExitQuery = CashFlowTransaction::where('type', 'exit');
        if ($from) {
            $cfEntryQuery->where('transaction_date', '>=', $from);
            $cfExitQuery->where('transaction_date', '>=', $from);
        }
        if ($to) {
            $cfEntryQuery->where('transaction_date', '<=', $to);
            $cfExitQuery->where('transaction_date', '<=', $to);
        }
        $cashFlowEntries = $cfEntryQuery->sum('amount');
        $cashFlowExits = $cfExitQuery->sum('amount');

        // Profit: from monthly reports
        $reportQuery = MonthlyReport::query();
        if ($clientId) {
            $reportQuery->where('client_id', $clientId);
        }
        if ($from) {
            $fromDate = \Carbon\Carbon::parse($from);
            $reportQuery->where(function ($q) use ($fromDate) {
                $q->where('year', '>', $fromDate->year)
                  ->orWhere(function ($q2) use ($fromDate) {
                      $q2->where('year', $fromDate->year)->where('month', '>=', $fromDate->month);
                  });
            });
        }
        if ($to) {
            $toDate = \Carbon\Carbon::parse($to);
            $reportQuery->where(function ($q) use ($toDate) {
                $q->where('year', '<', $toDate->year)
                  ->orWhere(function ($q2) use ($toDate) {
                      $q2->where('year', $toDate->year)->where('month', '<=', $toDate->month);
                  });
            });
        }
        $profitReports = $reportQuery->get();
        $totalProfit = $profitReports->sum(function ($r) {
            return (float) $r->total_deposits - (float) $r->total_withdrawals;
        });
        $avgProfitability = $profitReports->count() > 0
            ? round($profitReports->avg('profitability_percent'), 2)
            : 0;

        // Chart data - monthly reports
        $monthlyQuery = DB::table('monthly_reports')
            ->select(
                'year',
                'month',
                DB::raw('SUM(total_deposits) as deposits'),
                DB::raw('SUM(total_withdrawals) as withdrawals'),
                DB::raw('AVG(profitability_percent) as avg_profitability')
            );
        if ($clientId) {
            $monthlyQuery->where('client_id', $clientId);
        }
        $monthlyData = $monthlyQuery
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

        // P2P chart
        $p2pChartQuery = DB::table('p2p_operations')
            ->select(
                DB::raw("EXTRACT(YEAR FROM operation_date) as year"),
                DB::raw("EXTRACT(MONTH FROM operation_date) as month"),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            );
        if ($from) $p2pChartQuery->where('operation_date', '>=', $from);
        if ($to) $p2pChartQuery->where('operation_date', '<=', $to);
        $p2pMonthly = $p2pChartQuery
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
                'total_profit' => (float) $totalProfit,
                'avg_profitability' => $avgProfitability,
            ],
            'chart' => $monthlyData,
            'p2p_chart' => $p2pMonthly,
        ]);
    }
}
