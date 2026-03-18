<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionRule;
use Illuminate\Http\Request;

class CommissionRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = CommissionRule::query();

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query->orderBy('name')->paginate($request->input('per_page', 15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'applicable_to' => 'required|in:admin,partner',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $rule = CommissionRule::create($request->only('name', 'applicable_to', 'type', 'value', 'description', 'valid_from', 'valid_until'));

        return response()->json($rule, 201);
    }

    public function show(CommissionRule $commissionRule)
    {
        return $commissionRule;
    }

    public function update(Request $request, CommissionRule $commissionRule)
    {
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'applicable_to' => 'sometimes|in:admin,partner',
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
        ]);

        $commissionRule->update($request->only('name', 'applicable_to', 'type', 'value', 'description', 'is_active', 'valid_from', 'valid_until'));

        return $commissionRule;
    }

    public function destroy(CommissionRule $commissionRule)
    {
        $commissionRule->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
