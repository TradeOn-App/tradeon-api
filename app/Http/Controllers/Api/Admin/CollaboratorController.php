<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collaborator;
use App\Models\CommissionTransaction;
use Illuminate\Http\Request;

class CollaboratorController extends Controller
{
    public function index(Request $request)
    {
        $query = Collaborator::with('commissionRule');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'ilike', "%{$s}%")
                  ->orWhere('cpf', 'ilike', "%{$s}%");
            });
        }

        return $query->orderBy('name')->paginate($request->input('per_page', 15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|max:14|unique:collaborators,cpf',
            'wallet' => 'nullable|string|max:255',
            'commission_rule_id' => 'nullable|exists:commission_rules,id',
        ]);

        $collaborator = Collaborator::create($request->only('name', 'cpf', 'wallet', 'commission_rule_id'));

        return response()->json($collaborator->load('commissionRule'), 201);
    }

    public function show(Collaborator $collaborator)
    {
        return $collaborator->load('commissionRule');
    }

    public function update(Request $request, Collaborator $collaborator)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'cpf' => 'sometimes|string|max:14|unique:collaborators,cpf,' . $collaborator->id,
            'wallet' => 'nullable|string|max:255',
            'commission_rule_id' => 'nullable|exists:commission_rules,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $collaborator->update($request->only('name', 'cpf', 'wallet', 'commission_rule_id', 'is_active'));

        return $collaborator->load('commissionRule');
    }

    public function destroy(Collaborator $collaborator)
    {
        $collaborator->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function report(Request $request, Collaborator $collaborator)
    {
        $query = CommissionTransaction::where('collaborator_id', $collaborator->id)
            ->with(['currency', 'cashFlowTransaction', 'commissionRule']);

        if ($request->filled('from')) {
            $query->whereHas('cashFlowTransaction', function ($q) use ($request) {
                $q->where('transaction_date', '>=', $request->from);
            });
        }
        if ($request->filled('to')) {
            $query->whereHas('cashFlowTransaction', function ($q) use ($request) {
                $q->where('transaction_date', '<=', $request->to);
            });
        }

        $transactions = $query->orderByDesc('created_at')->get();

        $totalEntries = $transactions->filter(fn ($t) => $t->cashFlowTransaction?->type === 'entry')->sum('amount');
        $totalExits = $transactions->filter(fn ($t) => $t->cashFlowTransaction?->type === 'exit')->sum('amount');
        $totalCommission = $transactions->sum('amount');
        $profit = $totalEntries - $totalExits;
        $profitPercent = $totalEntries > 0 ? round(($profit / $totalEntries) * 100, 2) : 0;

        return response()->json([
            'collaborator' => $collaborator->load('commissionRule'),
            'summary' => [
                'total_entries' => (float) $totalEntries,
                'total_exits' => (float) $totalExits,
                'total_commission' => (float) $totalCommission,
                'profit' => (float) $profit,
                'profit_percent' => $profitPercent,
                'transaction_count' => $transactions->count(),
            ],
            'transactions' => $transactions,
        ]);
    }
}
