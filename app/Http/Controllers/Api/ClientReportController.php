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
            ->get();

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

        $transactions = ClientTransaction::where('client_id', $user->client->id)
            ->whereHas('cashFlowTransaction', function ($q) use ($year, $month) {
                $q->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month);
            })
            ->with('cashFlowTransaction.currency')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'report' => $report,
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
