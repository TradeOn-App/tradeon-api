<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionTransaction;
use Illuminate\Http\Request;

class CommissionTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = CommissionTransaction::with(['collaborator', 'commissionRule', 'currency', 'cashFlowTransaction']);

        if ($request->filled('collaborator_id')) {
            $query->where('collaborator_id', $request->collaborator_id);
        }

        return $query->orderByDesc('paid_at')->paginate($request->input('per_page', 15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cash_flow_transaction_id' => 'required|exists:cash_flow_transactions,id',
            'commission_rule_id' => 'required|exists:commission_rules,id',
            'collaborator_id' => 'required|exists:collaborators,id',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $ct = CommissionTransaction::create($request->only(
            'cash_flow_transaction_id', 'commission_rule_id', 'collaborator_id',
            'amount', 'currency_id', 'paid_at', 'notes'
        ));

        return response()->json($ct->load(['collaborator', 'commissionRule', 'currency']), 201);
    }

    public function show(CommissionTransaction $commissionTransaction)
    {
        return $commissionTransaction->load(['collaborator', 'commissionRule', 'currency', 'cashFlowTransaction']);
    }

    public function update(Request $request, CommissionTransaction $commissionTransaction)
    {
        $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $commissionTransaction->update($request->only('amount', 'paid_at', 'notes'));

        return $commissionTransaction->load(['collaborator', 'commissionRule', 'currency']);
    }

    public function destroy(CommissionTransaction $commissionTransaction)
    {
        $commissionTransaction->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
