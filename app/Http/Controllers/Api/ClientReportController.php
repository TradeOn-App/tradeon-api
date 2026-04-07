<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientTransaction;
use App\Models\MonthlyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ClientReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->client) {
            return response()->json(['message' => 'Cliente nao encontrado'], 404);
        }

        $reports = MonthlyReport::where('client_id', $user->client->id)
            ->whereNotNull('published_at')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($r) {
                return array_merge($r->toArray(), [
                    'initial_value' => $r->initial_value_brl,
                    'updated_value' => $r->updated_value_brl,
                    'real_gain' => $r->real_gain_brl,
                    'commission_value' => $r->commission_value_brl,
                    'profit_value' => $r->profit_value_brl,
                    'next_month_initial' => $r->next_month_initial_brl,
                    'total_deposits' => $r->total_deposits_brl,
                    'total_withdrawals' => $r->total_withdrawals_brl,
                ]);
            });

        return response()->json($reports);
    }

    public function show(Request $request, int $year, int $month)
    {
        $user = $request->user();
        if (! $user->client) {
            return response()->json(['message' => 'Cliente nao encontrado'], 404);
        }

        $report = MonthlyReport::where('client_id', $user->client->id)
            ->where('year', $year)
            ->where('month', $month)
            ->whereNotNull('published_at')
            ->first();

        if (! $report) {
            return response()->json(['message' => 'Relatório não encontrado'], 404);
        }

        // Retornar valores em BRL para o cliente
        $reportData = array_merge($report->toArray(), [
            'initial_value' => $report->initial_value_brl,
            'updated_value' => $report->updated_value_brl,
            'real_gain' => $report->real_gain_brl,
            'commission_value' => $report->commission_value_brl,
            'profit_value' => $report->profit_value_brl,
            'next_month_initial' => $report->next_month_initial_brl,
            'total_deposits' => $report->total_deposits_brl,
            'total_withdrawals' => $report->total_withdrawals_brl,
        ]);

        $transactions = ClientTransaction::where('client_id', $user->client->id)
            ->whereHas('cashFlowTransaction', function ($q) use ($year, $month) {
                $q->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month);
            })
            ->with('cashFlowTransaction.currency')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'report' => $reportData,
            'transactions' => $transactions,
        ]);
    }

    public function pdf(Request $request, int $year, int $month)
    {
        $user = $request->user();
        if (! $user->client) {
            return response()->json(['message' => 'Cliente nao encontrado'], 404);
        }

        $client = $user->client;

        $report = MonthlyReport::where('client_id', $client->id)
            ->where('year', $year)
            ->where('month', $month)
            ->whereNotNull('published_at')
            ->first();

        if (! $report) {
            return response()->json(['message' => 'Relatório não encontrado'], 404);
        }

        $transactions = ClientTransaction::where('client_id', $client->id)
            ->whereHas('cashFlowTransaction', function ($q) use ($year, $month) {
                $q->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month);
            })
            ->with('cashFlowTransaction.currency')
            ->orderBy('created_at')
            ->get();

        $monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $nextMonth = $month == 12 ? 1 : $month + 1;
        $nextYear = $month == 12 ? $year + 1 : $year;
        $nextPeriod = ($monthNames[$nextMonth] ?? $nextMonth) . ' / ' . $nextYear;

        $pdf = Pdf::loadView('pdf.report', [
            'report' => $report,
            'client' => $client,
            'transactions' => $transactions,
            'monthName' => $monthNames[$month] ?? $month,
            'year' => $year,
            'nextPeriod' => $nextPeriod,
        ]);

        $filename = "Relatorio {$client->full_name} - {$month}-{$year}.pdf";

        return $pdf->download($filename);
    }
}
