<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientTransaction;
use App\Models\MonthlyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = MonthlyReport::with('client');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        return $query->orderByDesc('year')->orderByDesc('month')->paginate($request->input('per_page', 15));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
        ]);

        $client = Client::findOrFail($request->client_id);
        $month = (int) $request->month;
        $year = (int) $request->year;

        // Transações do período com cashFlowTransaction para cotação (eager loaded para evitar N+1)
        $transactions = ClientTransaction::where('client_id', $client->id)
            ->whereHas('cashFlowTransaction', function ($q) use ($month, $year) {
                $q->whereMonth('transaction_date', $month)
                  ->whereYear('transaction_date', $year);
            })
            ->with('cashFlowTransaction.currency')
            ->get();

        // Valor Inicial (deposit) + Aportes (contribution) no período — em USDT
        $depositValue = (float) $transactions->where('type', 'deposit')->sum('amount');
        $contributions = (float) $transactions->where('type', 'contribution')->sum('amount');
        $initialValue = $depositValue + $contributions;

        // Valor Inicial em BRL (cada transação convertida pela cotação do dia)
        $initialValueBrl = $transactions->whereIn('type', ['deposit', 'contribution'])->sum(function ($t) {
            $quotation = (float) ($t->cashFlowTransaction->quotation_at_transaction ?? 1);
            return (float) $t->amount * $quotation;
        });

        // Saques no período — USDT e BRL
        $withdrawals = (float) $transactions->where('type', 'withdrawal')->sum('amount');
        $withdrawalsBrl = $transactions->where('type', 'withdrawal')->sum(function ($t) {
            $quotation = (float) ($t->cashFlowTransaction->quotation_at_transaction ?? 1);
            return (float) $t->amount * $quotation;
        });

        // Valor Atualizado = o mais recente que referencia este mês/ano
        $nextMonth = $month === 12 ? 1 : $month + 1;
        $nextYear = $month === 12 ? $year + 1 : $year;

        $latestUpdated = ClientTransaction::where('client_id', $client->id)
            ->where('type', 'updated_value')
            ->where('reference_month', $month)
            ->where('reference_year', $year)
            ->with('cashFlowTransaction')
            ->orderByDesc('created_at')
            ->first();

        $updatedValue = $latestUpdated ? (float) $latestUpdated->amount : 0;
        $updatedValueBrl = 0;
        if ($latestUpdated) {
            $quotation = (float) ($latestUpdated->cashFlowTransaction->quotation_at_transaction ?? 1);
            $updatedValueBrl = $updatedValue * $quotation;
        }

        // Débito inicial = soma dos initial_debit das transações deposit do período
        $initialDebit = (float) $transactions->where('type', 'deposit')->sum('initial_debit');

        // Cálculos em USDT
        $realGain = $updatedValue - $initialValue;
        $gainPercentage = $initialValue > 0 ? round(($realGain / $initialValue) * 100, 4) : 0;

        // Cálculos em BRL
        $realGainBrl = $updatedValueBrl - $initialValueBrl;

        // Comissão do cliente
        $commissionRate = (float) ($client->commission ?? 0);

        if ($realGain > 0) {
            $commissionBase = $realGain - $initialDebit;
            $commissionValue = $commissionBase > 0 ? round(($commissionRate / 100) * $commissionBase, 8) : 0;
            $profitValue = $realGain - $commissionValue;

            // BRL: comissão proporcional
            $commissionBaseBrl = $realGainBrl - ($initialDebit > 0 && $initialValue > 0 ? ($initialDebit / $initialValue) * $initialValueBrl : 0);
            $commissionValueBrl = $commissionBaseBrl > 0 ? round(($commissionRate / 100) * $commissionBaseBrl, 8) : 0;
            $profitValueBrl = $realGainBrl - $commissionValueBrl;
        } else {
            $commissionValue = 0;
            $profitValue = 0;
            $commissionValueBrl = 0;
            $profitValueBrl = 0;
        }

        // Valor inicial mês subsequente = Valor Atualizado - Comissão - Saques
        $nextMonthInitial = $updatedValue - $commissionValue - $withdrawals;
        $nextMonthInitialBrl = $updatedValueBrl - $commissionValueBrl - $withdrawalsBrl;

        $report = MonthlyReport::updateOrCreate(
            [
                'client_id' => $client->id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'total_deposits' => $initialValue,
                'total_withdrawals' => $withdrawals,
                'profitability_percent' => $gainPercentage,
                'initial_value' => $initialValue,
                'updated_value' => $updatedValue,
                'real_gain' => $realGain,
                'gain_percentage' => $gainPercentage,
                'commission_rate' => $commissionRate,
                'initial_debit' => $initialDebit,
                'commission_value' => $commissionValue,
                'profit_value' => $profitValue,
                'next_month_initial' => $nextMonthInitial,
                // Valores em BRL
                'initial_value_brl' => $initialValueBrl,
                'updated_value_brl' => $updatedValueBrl,
                'real_gain_brl' => $realGainBrl,
                'total_deposits_brl' => $initialValueBrl,
                'total_withdrawals_brl' => $withdrawalsBrl,
                'commission_value_brl' => $commissionValueBrl,
                'profit_value_brl' => $profitValueBrl,
                'next_month_initial_brl' => $nextMonthInitialBrl,
                'summary' => [
                    'transactions_count' => $transactions->count(),
                    'next_month' => sprintf('%02d/%d', $nextMonth, $nextYear),
                ],
                'generated_at' => now(),
            ]
        );

        return response()->json($report->load('client'));
    }

    public function show(MonthlyReport $report)
    {
        return $report->load('client');
    }

    public function publish(MonthlyReport $report)
    {
        $report->update(['published_at' => now()]);

        return response()->json($report->load('client'));
    }

    public function destroy(MonthlyReport $report)
    {
        $report->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function pdf(MonthlyReport $report)
    {
        $client = $report->client;

        $transactions = ClientTransaction::where('client_id', $client->id)
            ->whereHas('cashFlowTransaction', function ($q) use ($report) {
                $q->whereYear('transaction_date', $report->year)
                    ->whereMonth('transaction_date', $report->month);
            })
            ->with('cashFlowTransaction.currency')
            ->orderBy('created_at')
            ->get();

        $monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $nextMonth = $report->month === 12 ? 1 : $report->month + 1;
        $nextYear = $report->month === 12 ? $report->year + 1 : $report->year;
        $nextPeriod = ($monthNames[$nextMonth] ?? $nextMonth) . ' / ' . $nextYear;

        $pdf = Pdf::loadView('pdf.report', [
            'report' => $report,
            'client' => $client,
            'transactions' => $transactions,
            'monthName' => $monthNames[$report->month] ?? $report->month,
            'year' => $report->year,
            'nextPeriod' => $nextPeriod,
        ]);

        $filename = "Relatorio {$client->full_name} - {$report->month}-{$report->year}.pdf";

        return $pdf->download($filename);
    }

    public function batchPdf(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'exists:monthly_reports,id',
        ]);

        $reports = MonthlyReport::with('client')->whereIn('id', $request->ids)->orderByDesc('year')->orderByDesc('month')->get();
        $monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        // Pré-carregar todas as transações de todos os reports de uma vez (evita N+1)
        $clientIds = $reports->pluck('client_id')->unique();
        $periods = $reports->map(fn ($r) => ['client_id' => $r->client_id, 'month' => $r->month, 'year' => $r->year]);

        $allTransactions = ClientTransaction::whereIn('client_id', $clientIds)
            ->whereHas('cashFlowTransaction', function ($q) use ($reports) {
                $q->where(function ($sub) use ($reports) {
                    foreach ($reports as $report) {
                        $sub->orWhere(function ($w) use ($report) {
                            $w->whereYear('transaction_date', $report->year)
                              ->whereMonth('transaction_date', $report->month);
                        });
                    }
                });
            })
            ->with('cashFlowTransaction.currency')
            ->orderBy('created_at')
            ->get();

        $pages = [];
        foreach ($reports as $report) {
            $client = $report->client;

            $transactions = $allTransactions->filter(function ($t) use ($report) {
                $cfDate = $t->cashFlowTransaction->transaction_date;
                return $t->client_id === $report->client_id
                    && $cfDate->year === $report->year
                    && $cfDate->month === $report->month;
            })->values();

            $nextMonth = $report->month === 12 ? 1 : $report->month + 1;
            $nextYear = $report->month === 12 ? $report->year + 1 : $report->year;

            $pages[] = [
                'report' => $report,
                'client' => $client,
                'transactions' => $transactions,
                'monthName' => $monthNames[$report->month] ?? $report->month,
                'year' => $report->year,
                'nextPeriod' => ($monthNames[$nextMonth] ?? $nextMonth) . ' / ' . $nextYear,
            ];
        }

        $pdf = Pdf::loadView('pdf.report-batch', ['pages' => $pages]);

        return $pdf->download('Relatorios Selecionados.pdf');
    }
}
