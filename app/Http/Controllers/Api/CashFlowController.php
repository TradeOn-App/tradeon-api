<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashFlowTransaction;
use Illuminate\Http\Request;

class CashFlowController extends Controller
{
    public function index(Request $request)
    {
        $query = CashFlowTransaction::with(['currency', 'network'])
            ->orderBy('transaction_date', 'desc');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('from')) {
            $query->where('transaction_date', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('transaction_date', '<=', $request->to);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:entry,exit',
            'currency_id' => 'required|exists:currencies,id',
            'network_id' => 'nullable|exists:networks,id',
            'amount' => 'required|numeric|min:0',
            'amount_usdt_equivalent' => 'nullable|numeric|min:0',
            'quotation_at_transaction' => 'nullable|numeric|min:0',
            'wallet_origin' => 'nullable|string|max:255',
            'wallet_destination' => 'nullable|string|max:255',
            'tx_hash' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);

        $validated['created_by'] = $request->user()->id;
        $transaction = CashFlowTransaction::create($validated);

        return response()->json($transaction->load(['currency', 'network']), 201);
    }

    public function show(CashFlowTransaction $cash_flow)
    {
        $cash_flow->load(['currency', 'network', 'p2pOperation', 'commissionTransactions']);
        return response()->json($cash_flow);
    }

    public function update(Request $request, CashFlowTransaction $cash_flow)
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:entry,exit',
            'currency_id' => 'sometimes|exists:currencies,id',
            'network_id' => 'nullable|exists:networks,id',
            'amount' => 'sometimes|numeric|min:0',
            'amount_usdt_equivalent' => 'nullable|numeric|min:0',
            'quotation_at_transaction' => 'nullable|numeric|min:0',
            'wallet_origin' => 'nullable|string|max:255',
            'wallet_destination' => 'nullable|string|max:255',
            'tx_hash' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'transaction_date' => 'sometimes|date',
        ]);

        $cash_flow->update($validated);
        return response()->json($cash_flow->fresh(['currency', 'network']));
    }

    public function destroy(CashFlowTransaction $cash_flow)
    {
        $cash_flow->delete();
        return response()->json(null, 204);
    }
}
