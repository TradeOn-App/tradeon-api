<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\InternalTransaction;
use Illuminate\Http\Request;

class InternalTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = InternalTransaction::with(['collaborator', 'currency', 'network']);

        if ($request->filled('collaborator_id')) {
            $query->where('collaborator_id', $request->collaborator_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $query->orderByDesc('transaction_date')->paginate($request->input('per_page', 50));
    }

    public function store(Request $request)
    {
        $request->validate([
            'collaborator_id' => 'required|exists:collaborators,id',
            'type' => 'required|in:deposit,withdrawal,commission_withdrawal,client_withdrawal,initial_value,updated_value',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'network_id' => 'nullable|exists:networks,id',
            'transaction_date' => 'required|date',
            'quotation_at_transaction' => 'nullable|numeric',
            'wallet_destination' => 'nullable|string|max:255',
            'tx_hash' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $transaction = InternalTransaction::create([
            ...$request->only([
                'collaborator_id', 'type', 'amount', 'currency_id', 'network_id',
                'transaction_date', 'quotation_at_transaction', 'wallet_destination',
                'tx_hash', 'description',
            ]),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($transaction->load(['collaborator', 'currency', 'network']), 201);
    }

    public function show(InternalTransaction $internalTransaction)
    {
        return $internalTransaction->load(['collaborator', 'currency', 'network']);
    }

    public function update(Request $request, InternalTransaction $internalTransaction)
    {
        $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'type' => 'sometimes|in:deposit,withdrawal,commission_withdrawal,client_withdrawal,initial_value,updated_value',
            'quotation_at_transaction' => 'nullable|numeric',
            'description' => 'nullable|string',
            'wallet_destination' => 'nullable|string|max:255',
            'tx_hash' => 'nullable|string|max:255',
        ]);

        $internalTransaction->update($request->only('amount', 'type', 'quotation_at_transaction', 'description', 'wallet_destination', 'tx_hash'));

        return $internalTransaction->load(['collaborator', 'currency', 'network']);
    }

    public function destroy(InternalTransaction $internalTransaction)
    {
        $internalTransaction->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
