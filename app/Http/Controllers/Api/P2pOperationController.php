<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\P2pOperation;
use Illuminate\Http\Request;

class P2pOperationController extends Controller
{
    public function index(Request $request)
    {
        $query = P2pOperation::with(['currency', 'cashFlowTransaction'])
            ->orderBy('operation_date', 'desc');

        if ($request->has('from')) {
            $query->where('operation_date', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('operation_date', '<=', $request->to);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cash_flow_transaction_id' => 'nullable|exists:cash_flow_transactions,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'to_whom' => 'required|string|max:255',
            'reason' => 'required|string',
            'operation_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'wallet_from' => 'nullable|string|max:255',
            'wallet_to' => 'nullable|string|max:255',
            'dollar_quotation' => 'nullable|numeric|min:0',
        ]);

        $validated['created_by'] = $request->user()->id;
        $operation = P2pOperation::create($validated);

        return response()->json($operation->load('currency'), 201);
    }

    public function show(P2pOperation $p2p_operation)
    {
        $p2p_operation->load(['currency', 'cashFlowTransaction']);
        return response()->json($p2p_operation);
    }

    public function update(Request $request, P2pOperation $p2p_operation)
    {
        $validated = $request->validate([
            'cash_flow_transaction_id' => 'nullable|exists:cash_flow_transactions,id',
            'currency_id' => 'sometimes|exists:currencies,id',
            'amount' => 'sometimes|numeric|min:0',
            'to_whom' => 'sometimes|string|max:255',
            'reason' => 'sometimes|string',
            'operation_date' => 'sometimes|date',
            'reference' => 'nullable|string|max:100',
            'wallet_from' => 'nullable|string|max:255',
            'wallet_to' => 'nullable|string|max:255',
            'dollar_quotation' => 'nullable|numeric|min:0',
        ]);

        $p2p_operation->update($validated);
        return response()->json($p2p_operation->fresh(['currency', 'cashFlowTransaction']));
    }

    public function destroy(P2pOperation $p2p_operation)
    {
        $p2p_operation->delete();
        return response()->json(null, 204);
    }
}
