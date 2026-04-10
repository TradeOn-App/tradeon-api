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

        // Transações do mês
        $transactions = InternalTransaction::where('collaborator_id', $collaborator->id)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->get();

        // Valor Inicial = apenas transações do tipo initial_value
        $initialValue = (float) $transactions->where('type', 'initial_value')->sum('amount');

        // Aportes do mês
        $deposits = (float) $transactions->where('type', 'deposit')->sum('amount');

        // Detectar restart: se houve saque >= saldo do mês anterior + depósito no mesmo mês
        // Nesse caso o deposit é informativo (mesmo dinheiro do initial_value)
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear = $month === 1 ? $year - 1 : $year;
        $prevReport = InternalReport::where('collaborator_id', $collaborator->id)
            ->where('month', $prevMonth)
            ->where('year', $prevYear)
            ->first();

        $withdrawals = (float) $transactions->where('type', 'withdrawal')->sum('amount');
        $commissionWithdrawals = (float) $transactions->where('type', 'commission_withdrawal')->sum('amount');
        $clientWithdrawals = (float) $transactions->where('type', 'client_withdrawal')->sum('amount');
        $totalSaques = $withdrawals + $commissionWithdrawals + $clientWithdrawals;

        // Restart = saque total (>= saldo anterior) + novo depósito + initial_value no mesmo mês
        $isRestart = $prevReport
            && $totalSaques >= (float) $prevReport->next_month_initial
            && $deposits > 0
            && $initialValue > 0;

        // Capital operado: se é restart, deposit é informativo (já contido no initial_value)
        // Se não é restart, deposit é dinheiro novo adicionado ao initial_value
        $operatingCapital = $isRestart ? $initialValue : ($initialValue + $deposits);

        // Valor Atualizado = mais recente do tipo updated_value
        $latestUpdated = InternalTransaction::where('collaborator_id', $collaborator->id)
            ->where('type', 'updated_value')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->orderByDesc('created_at')
            ->first();
        $updatedValue = $latestUpdated ? (float) $latestUpdated->amount : 0;

        // Lucro do mês = Valor Atualizado - Capital Operado
        if ($updatedValue > 0) {
            $profit = $updatedValue - $operatingCapital;
        } else {
            $profit = 0;
        }

        $profitPercentage = $operatingCapital > 0 ? round(($profit / $operatingCapital) * 100, 4) : 0;

        // --- Débito acumulado ---
        $previousDebt = $prevReport ? (float) ($prevReport->accumulated_debt ?? 0) : 0;

        // Comissão = % do colaborador sobre (lucro - débito acumulado)
        $commissionRate = (float) ($collaborator->commission ?? 0);
        $accumulatedDebt = 0;
        $commissionValue = 0;

        if ($profit > 0) {
            $commissionBase = $profit - $previousDebt;
            if ($commissionBase > 0) {
                // Lucro cobriu o débito, cobrar comissão sobre o excedente
                $commissionValue = round(($commissionRate / 100) * $commissionBase, 8);
                $accumulatedDebt = 0; // Débito quitado
            } else {
                // Lucro não cobriu o débito todo, sem comissão
                $commissionValue = 0;
                $accumulatedDebt = abs($commissionBase); // Débito restante
            }
        } elseif ($profit < 0) {
            // Prejuízo: acumular como débito para próximos meses
            $commissionValue = 0;
            $accumulatedDebt = $previousDebt + abs($profit);
        } else {
            // Lucro zero: manter débito anterior
            $commissionValue = 0;
            $accumulatedDebt = $previousDebt;
        }

        // Se é restart, zerar o débito acumulado (é um recomeço)
        if ($isRestart) {
            $accumulatedDebt = 0;
            $previousDebt = 0;

            // Recalcular comissão sem débito anterior
            if ($profit > 0) {
                $commissionValue = round(($commissionRate / 100) * $profit, 8);
            }
        }

        // Próximo mês = Valor Atualizado - Comissão - Saques (do mês, excluindo saques do restart)
        $baseForNext = $updatedValue > 0 ? $updatedValue : $operatingCapital;
        if ($isRestart) {
            // Em restart, o saque é do capital anterior, não do novo
            // Só descontar saques que ocorreram DEPOIS do re-depósito (se houver)
            $nextMonthInitial = $baseForNext - $commissionValue;
        } else {
            $nextMonthInitial = $baseForNext - $commissionValue - $totalSaques;
        }

        $balance = $deposits - $withdrawals - $commissionWithdrawals - $clientWithdrawals;

        // Aportes acumulados líquidos (aportes - saques, para L. Total)
        $totalDepositsHistorical = (float) InternalTransaction::where('collaborator_id', $collaborator->id)
            ->whereIn('type', ['deposit', 'initial_value'])
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('transaction_date', '<', $year)
                  ->orWhere(fn ($sub) => $sub->whereYear('transaction_date', $year)->whereMonth('transaction_date', '<=', $month));
            })
            ->sum('amount');

        $totalWithdrawalsHistorical = (float) InternalTransaction::where('collaborator_id', $collaborator->id)
            ->whereIn('type', ['withdrawal', 'commission_withdrawal', 'client_withdrawal'])
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('transaction_date', '<', $year)
                  ->orWhere(fn ($sub) => $sub->whereYear('transaction_date', $year)->whereMonth('transaction_date', '<=', $month));
            })
            ->sum('amount');

        // Aportes líquidos = capital que realmente está investido
        $cumulativeDeposits = max($totalDepositsHistorical - $totalWithdrawalsHistorical, 0);

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
                'accumulated_debt' => $accumulatedDebt,
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

    public function pdf(InternalReport $internalReport)
    {
        $collaborator = $internalReport->collaborator;
        $monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $transactions = InternalTransaction::where('collaborator_id', $collaborator->id)
            ->whereMonth('transaction_date', $internalReport->month)
            ->whereYear('transaction_date', $internalReport->year)
            ->orderBy('created_at')
            ->get();

        $nextMonth = $internalReport->month === 12 ? 1 : $internalReport->month + 1;
        $nextYear = $internalReport->month === 12 ? $internalReport->year + 1 : $internalReport->year;
        $nextPeriod = ($monthNames[$nextMonth] ?? $nextMonth) . ' / ' . $nextYear;

        $pdf = Pdf::loadView('pdf.internal-report', [
            'report' => $internalReport,
            'collaborator' => $collaborator,
            'transactions' => $transactions,
            'monthName' => $monthNames[$internalReport->month] ?? $internalReport->month,
            'year' => $internalReport->year,
            'nextPeriod' => $nextPeriod,
        ]);

        $filename = "Relatorio Interno {$collaborator->name} - {$internalReport->month}-{$internalReport->year}.pdf";

        return $pdf->download($filename);
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
