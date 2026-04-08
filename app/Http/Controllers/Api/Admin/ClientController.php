<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with('user');

        if ($request->filled('search')) {
            $s = $request->search;
            // full_name não é criptografado, busca normal
            // document é criptografado, não pode usar LIKE no banco
            $query->where('full_name', 'ilike', "%{$s}%");
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderBy('full_name')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:12',
            'document' => 'required|string|max:20',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'commission' => 'nullable|numeric|min:0|max:100',
        ]);

        $user = User::create([
            'name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'client',
            'must_change_password' => true,
        ]);

        $client = Client::create([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'document' => $request->document,
            'phone' => $request->phone,
            'access_password' => $request->password,
            'notes' => $request->notes,
            'commission' => $request->commission,
            'is_active' => true,
        ]);

        return response()->json($client->load('user'), 201);
    }

    public function show(Client $client)
    {
        return $client->load('user');
    }

    public function update(Request $request, Client $client)
    {
        $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'document' => 'sometimes|string|max:20',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'commission' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean',
            'email' => 'sometimes|email|unique:users,email,' . $client->user_id,
            'password' => 'sometimes|string|min:12',
        ]);

        $client->update($request->only('full_name', 'document', 'phone', 'notes', 'commission', 'is_active'));

        if ($request->filled('full_name')) {
            $client->user->update(['name' => $request->full_name]);
        }

        if ($request->filled('email')) {
            $client->user->update(['email' => $request->email]);
        }

        if ($request->filled('password')) {
            $client->user->update([
                'password' => Hash::make($request->password),
                'must_change_password' => true,
            ]);
            $client->update(['access_password' => $request->password]);
        }

        return $client->load('user');
    }

    public function destroy(Client $client)
    {
        $client->user->delete();
        $client->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
