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

        $transactions = $client->transactions()
            ->whereHas('cashFlowTransaction', function ($q) use ($request) {
                $q->whereMonth('transaction_date', $request->month)
                  ->whereYear('transaction_date', $request->year);
            })
            ->get();

        $deposits = $transactions->where('type', 'deposit')->sum('amount');
        $withdrawals = $transactions->where('type', 'withdrawal')->sum('amount');
        $net = $deposits - $withdrawals;
        $profitability = $deposits > 0 ? round(($net / $deposits) * 100, 4) : 0;

        $report = MonthlyReport::updateOrCreate(
            [
                'client_id' => $request->client_id,
                'month' => $request->month,
                'year' => $request->year,
            ],
            [
                'total_deposits' => $deposits,
                'total_withdrawals' => $withdrawals,
                'profitability_percent' => $profitability,
                'summary' => [
                    'net' => $net,
                    'transactions_count' => $transactions->count(),
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

        $monthNames = ['', 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $pdf = Pdf::loadView('pdf.report', [
            'report' => $report,
            'client' => $client,
            'transactions' => $transactions,
            'monthName' => $monthNames[$report->month] ?? $report->month,
            'year' => $report->year,
        ]);

        $filename = "relatorio-{$client->full_name}-{$report->month}-{$report->year}.pdf";

        return $pdf->download($filename);
    }
}
