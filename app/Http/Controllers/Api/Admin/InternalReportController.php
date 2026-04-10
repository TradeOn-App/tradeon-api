<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collaborator;
use App\Models\InternalReport;
use App\Models\InternalTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InternalReportController extends Controller
{
    public function index(Request $request)
    {
        $query = InternalReport::with('collaborator');

        if ($request->filled('collaborator_id')) {
            $query->where('collaborator_id', $request->collaborator_id);
        }

        return $query->orderByDesc('year')->orderByDesc('month')->paginate($request->input('per_page', 50));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'collaborator_id' => 'required|exists:collaborators,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
        ]);

        $collaborator = Collaborator::findOrFail($request->collaborator_id);
        $month = (int) $request->month;
        $year = (int) $request->year;

        $transactions = InternalTransaction::where('collaborator_id', $collaborator->id)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->get();

        // Valor Inicial = apenas transações do tipo initial_value (SEM somar aportes)
        $initialValue = (float) $transactions->where('type', 'initial_value')->sum('amount');

        // Aportes (deposits) separados
        $deposits = (float) $transactions->where('type', 'deposit')->sum('amount');

        // Capital operado no mês = Valor Inicial + Aportes
        $operatingCapital = $initialValue + $deposits;

        // Valor Atualizado = mais recente do tipo updated_value
        $latestUpdated = InternalTransaction::where('collaborator_id', $collaborator->id)
            ->where('type', 'updated_value')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->orderByDesc('created_at')
            ->first();
        $updatedValue = $latestUpdated ? (float) $latestUpdated->amount : 0;

        $withdrawals = (float) $transactions->where('type', 'withdrawal')->sum('amount');
        $commissionWithdrawals = (float) $transactions->where('type', 'commission_withdrawal')->sum('amount');
        $clientWithdrawals = (float) $transactions->where('type', 'client_withdrawal')->sum('amount');
        $totalSaques = $withdrawals + $commissionWithdrawals + $clientWithdrawals;

        // Lucro do mês = Valor Atualizado - Capital Operado
        // Se não há updated_value, lucro = 0 (mês sem fechamento)
        if ($updatedValue > 0) {
            $profit = $updatedValue - $operatingCapital;
        } else {
            // Sem valor atualizado: considerar que o capital ficou intacto
            $profit = 0;
        }

        $profitPercentage = $operatingCapital > 0 ? round(($profit / $operatingCapital) * 100, 4) : 0;

        // Comissão = % do colaborador sobre o lucro (só se lucro positivo)
        $commissionRate = (float) ($collaborator->commission ?? 0);
        $commissionValue = $profit > 0 ? round(($commissionRate / 100) * $profit, 8) : 0;

        // Valor inicial mês subsequente:
        // Se há updated_value: Atualizado - saques
        // Se não há updated_value: Capital Operado - saques
        $baseForNext = $updatedValue > 0 ? $updatedValue : $operatingCapital;
        $nextMonthInitial = max($baseForNext - $totalSaques, 0);

        // Se o lucro foi negativo (prejuízo), descontar do próximo mês
        if ($profit < 0) {
            $nextMonthInitial = $baseForNext - $totalSaques;
            // O valor pode ficar negativo se houve prejuízo real
        }

        $balance = $deposits - $withdrawals - $commissionWithdrawals - $clientWithdrawals;

        // Aportes acumulados históricos (todos os deposits + initial_values até este mês)
        $cumulativeDeposits = (float) InternalTransaction::where('collaborator_id', $collaborator->id)
            ->whereIn('type', ['deposit', 'initial_value'])
            ->where(function ($q) use ($year, $month) {
                $q->where(function ($sub) use ($year, $month) {
                    $sub->whereYear('transaction_date', '<', $year);
                })->orWhere(function ($sub) use ($year, $month) {
                    $sub->whereYear('transaction_date', $year)
                        ->whereMonth('transaction_date', '<=', $month);
                });
            })
            ->sum('amount');

        $nextMonth = $month === 12 ? 1 : $month + 1;
        $nextYear = $month === 12 ? $year + 1 : $year;

        $report = InternalReport::updateOrCreate(
            [
                'collaborator_id' => $collaborator->id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'total_deposits' => $deposits,
                'cumulative_deposits' => $cumulativeDeposits,
                'total_withdrawals' => $withdrawals,
                'total_commission_withdrawals' => $commissionWithdrawals,
                'balance' => $balance,
                'initial_value' => $initialValue,
                'updated_value' => $updatedValue,
                'profit' => $profit,
                'profit_percentage' => $profitPercentage,
                'commission_rate' => $commissionRate,
                'commission_value' => $commissionValue,
                'next_month_initial' => $nextMonthInitial,
                'summary' => [
                    'transactions_count' => $transactions->count(),
                    'next_month' => sprintf('%02d/%d', $nextMonth, $nextYear),
                ],
                'generated_at' => now(),
            ]
        );

        return response()->json($report->load('collaborator'));
    }

    public function destroy(InternalReport $internalReport)
    {
        $internalReport->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function batchPdf(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'exists:internal_reports,id',
        ]);

        $reports = InternalReport::with('collaborator')->whereIn('id', $request->ids)->orderByDesc('year')->orderByDesc('month')->get();
        $monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        // Pré-carregar todas as transações de uma vez (evita N+1)
        $collaboratorIds = $reports->pluck('collaborator_id')->unique();
        $allTransactions = InternalTransaction::whereIn('collaborator_id', $collaboratorIds)
            ->where(function ($q) use ($reports) {
                foreach ($reports as $report) {
                    $q->orWhere(function ($w) use ($report) {
                        $w->where('collaborator_id', $report->collaborator_id)
                          ->whereMonth('transaction_date', $report->month)
                          ->whereYear('transaction_date', $report->year);
                    });
                }
            })
            ->orderBy('created_at')
            ->get();

        $pages = [];
        foreach ($reports as $report) {
            $transactions = $allTransactions->filter(function ($t) use ($report) {
                return $t->collaborator_id === $report->collaborator_id
                    && $t->transaction_date->month === $report->month
                    && $t->transaction_date->year === $report->year;
            })->values();

            $nextMonth = $report->month === 12 ? 1 : $report->month + 1;
            $nextYear = $report->month === 12 ? $report->year + 1 : $report->year;

            $pages[] = [
                'report' => $report,
                'collaborator' => $report->collaborator,
                'transactions' => $transactions,
                'monthName' => $monthNames[$report->month] ?? $report->month,
                'year' => $report->year,
                'nextPeriod' => ($monthNames[$nextMonth] ?? $nextMonth) . ' / ' . $nextYear,
            ];
        }

        $totalUpdatedValue = $reports->sum(fn ($r) => (float) $r->updated_value);
        $avgProfitPct = $reports->count() > 0 ? $reports->avg('profit_percentage') : 0;

        $pdf = Pdf::loadView('pdf.internal-report-batch', [
            'pages' => $pages,
            'totalUpdatedValue' => $totalUpdatedValue,
            'avgProfitPct' => round($avgProfitPct, 2),
        ]);

        return $pdf->download('Relatorios Internos Selecionados.pdf');
    }
}
