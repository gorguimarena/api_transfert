<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::where('email', 'john@example.com')->exists()) {
            return;
        }

        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password'),
                'type' => 'client',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password'),
                'type' => 'client',
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'password' => Hash::make('password'),
                'type' => 'client',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);

            $client = new Client();
            $client->id = (string) Str::uuid();
            $client->user_id = $user->id;
            $client->nom = explode(' ', $userData['name'])[1] ?? '';
            $client->prenom = explode(' ', $userData['name'])[0] ?? '';
            $client->nci = rand(1000000000000, 9999999999999);
            $client->adresse = 'Dakar, Sénégal';
            $client->code_verification = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $client->code_utilise = false;
            $client->save();
        }

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'type' => 'admin',
        ]);
    }
}
