<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Network;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Currency::upsert([
            ['code' => 'BRL', 'name' => 'Real Brasileiro', 'symbol' => 'R$', 'is_crypto' => false],
            ['code' => 'USD', 'name' => 'Dólar Americano', 'symbol' => '$', 'is_crypto' => false],
            ['code' => 'USDT', 'name' => 'Tether', 'symbol' => 'USDT', 'is_crypto' => true],
        ], ['code'], ['name', 'symbol', 'is_crypto']);

        Network::upsert([
            ['name' => 'Ethereum (ERC-20)', 'slug' => 'erc20', 'is_active' => true],
            ['name' => 'Tron (TRC-20)', 'slug' => 'trc20', 'is_active' => true],
        ], ['slug'], ['name', 'is_active']);

        $adminEmail = env('ADMIN_EMAIL', 'admin@lucastrade.local');
        $adminPassword = env('ADMIN_PASSWORD');

        if ($adminPassword) {
            User::firstOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => env('ADMIN_NAME', 'Admin'),
                    'password' => Hash::make($adminPassword),
                    'role' => 'admin',
                    'must_change_password' => true,
                ]
            );
        }
    }
}
