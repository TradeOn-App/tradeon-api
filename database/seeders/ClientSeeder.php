<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'email' => 'carlos@cliente.com',
                'name' => 'Carlos Mendes',
                'full_name' => 'Carlos Eduardo Mendes',
                'document' => '123.456.789-00',
                'phone' => '(11) 99999-1111',
            ],
            [
                'email' => 'fernanda@cliente.com',
                'name' => 'Fernanda Lima',
                'full_name' => 'Fernanda Souza Lima',
                'document' => '987.654.321-00',
                'phone' => '(21) 98888-2222',
            ],
            [
                'email' => 'rodrigo@cliente.com',
                'name' => 'Rodrigo Alves',
                'full_name' => 'Rodrigo Pereira Alves',
                'document' => '456.789.123-00',
                'phone' => '(31) 97777-3333',
            ],
        ];

        foreach ($clients as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('senha123'),
                    'role' => 'client',
                ]
            );

            Client::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $data['full_name'],
                    'document' => $data['document'],
                    'phone' => $data['phone'],
                    'is_active' => true,
                ]
            );
        }
    }
}
