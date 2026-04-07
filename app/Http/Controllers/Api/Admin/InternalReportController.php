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

        // Valor Inicial = initial_value + deposit (aportes)
        $initialValues = (float) $transactions->where('type', 'initial_value')->sum('amount');
        $deposits = (float) $transactions->where('type', 'deposit')->sum('amount');
        $initialValue = $initialValues + $deposits;

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

        // Lucro = Valor Atualizado - Valor Inicial
        $profit = $updatedValue - $initialValue;
        $profitPercentage = $initialValue > 0 ? round(($profit / $initialValue) * 100, 4) : 0;

        // Comissão = % do colaborador sobre o lucro
        $commissionRate = (float) ($collaborator->commission ?? 0);
        $commissionValue = $profit > 0 ? round(($commissionRate / 100) * $profit, 8) : 0;

        // Valor inicial mês subsequente = Valor Atualizado - Saque Cliente - Saque Comissão - Retirada
        $nextMonthInitial = $updatedValue - $clientWithdrawals - $commissionWithdrawals - $withdrawals;

        $balance = $deposits - $withdrawals - $commissionWithdrawals - $clientWithdrawals;

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
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:internal_reports,id',
        ]);

        $reports = InternalReport::with('collaborator')->whereIn('id', $request->ids)->orderByDesc('year')->orderByDesc('month')->get();
        $monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $pages = [];
        foreach ($reports as $report) {
            $transactions = InternalTransaction::where('collaborator_id', $report->collaborator_id)
                ->whereMonth('transaction_date', $report->month)
                ->whereYear('transaction_date', $report->year)
                ->orderBy('created_at')
                ->get();

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

        // Totais
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
