<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashFlowTransaction;
use App\Models\ClientTransaction;
use Illuminate\Http\Request;

class ClientTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = ClientTransaction::with(['client', 'cashFlowTransaction.currency']);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $query->orderByDesc('created_at')->paginate($request->input('per_page', 15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:deposit,withdrawal,allocation',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'network_id' => 'required|exists:networks,id',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);

        $cfType = $request->type === 'deposit' ? 'entry' : 'exit';

        $cf = CashFlowTransaction::create([
            'type' => $cfType,
            'currency_id' => $request->currency_id,
            'network_id' => $request->network_id,
            'amount' => $request->amount,
            'amount_usdt_equivalent' => $request->input('amount_usdt_equivalent', 0),
            'quotation_at_transaction' => $request->input('quotation', 0),
            'wallet_origin' => $request->input('wallet_origin', ''),
            'wallet_destination' => $request->input('wallet_destination', ''),
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            'created_by' => $request->user()->id,
        ]);

        $ct = ClientTransaction::create([
            'client_id' => $request->client_id,
            'cash_flow_transaction_id' => $cf->id,
            'type' => $request->type,
            'amount' => $request->amount,
        ]);

        return response()->json($ct->load(['client', 'cashFlowTransaction.currency']), 201);
    }

    public function show(ClientTransaction $clientTransaction)
    {
        return $clientTransaction->load(['client', 'cashFlowTransaction.currency']);
    }

    public function update(Request $request, ClientTransaction $clientTransaction)
    {
        $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        if ($request->filled('amount')) {
            $clientTransaction->update(['amount' => $request->amount]);
            $clientTransaction->cashFlowTransaction->update(['amount' => $request->amount]);
        }

        if ($request->has('notes')) {
            $clientTransaction->update(['notes' => $request->notes]);
        }

        return $clientTransaction->load(['client', 'cashFlowTransaction.currency']);
    }

    public function destroy(ClientTransaction $clientTransaction)
    {
        $clientTransaction->cashFlowTransaction->delete();
        $clientTransaction->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
