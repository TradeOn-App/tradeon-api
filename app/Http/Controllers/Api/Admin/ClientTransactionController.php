<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashFlowTransaction;
use App\Models\ClientTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:deposit,withdrawal,updated_value,contribution',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'network_id' => 'required|exists:networks,id',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date',
            'initial_debit' => 'nullable|numeric|min:0',
            'reference_month' => 'nullable|integer|between:1,12',
            'reference_year' => 'nullable|integer|min:2020',
            'receipt' => 'nullable|image|max:5120',
        ]);

        $cfType = in_array($request->type, ['deposit', 'updated_value', 'contribution']) ? 'entry' : 'exit';

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

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        $ct = ClientTransaction::create([
            'client_id' => $request->client_id,
            'cash_flow_transaction_id' => $cf->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'initial_debit' => $request->input('initial_debit', 0),
            'reference_month' => $request->input('reference_month'),
            'reference_year' => $request->input('reference_year'),
            'receipt_path' => $receiptPath,
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
            'initial_debit' => 'nullable|numeric|min:0',
            'reference_month' => 'nullable|integer|between:1,12',
            'reference_year' => 'nullable|integer|min:2020',
            'description' => 'nullable|string',
            'quotation' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $ctFields = [];
        $cfFields = [];

        if ($request->filled('amount')) {
            $ctFields['amount'] = $request->amount;
            $cfFields['amount'] = $request->amount;
        }
        if ($request->has('initial_debit')) $ctFields['initial_debit'] = $request->input('initial_debit', 0);
        if ($request->has('reference_month')) $ctFields['reference_month'] = $request->reference_month;
        if ($request->has('reference_year')) $ctFields['reference_year'] = $request->reference_year;
        if ($request->has('notes')) $ctFields['notes'] = $request->notes;
        if ($request->has('description')) $cfFields['description'] = $request->description;
        if ($request->has('quotation')) $cfFields['quotation_at_transaction'] = $request->quotation;

        if (!empty($ctFields)) $clientTransaction->update($ctFields);
        if (!empty($cfFields)) $clientTransaction->cashFlowTransaction->update($cfFields);

        return $clientTransaction->load(['client', 'cashFlowTransaction.currency']);
    }

    public function destroy(ClientTransaction $clientTransaction)
    {
        $clientTransaction->cashFlowTransaction->delete();
        $clientTransaction->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
