<?php

namespace Database\Seeders;

use App\Models\ApiClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $clientId = env('MACHINE_CLIENT_ID');
        $clientSecret = env('MACHINE_CLIENT_SECRET');

        if (! $clientId || ! $clientSecret) {
            return;
        }

        ApiClient::updateOrCreate(
            ['client_id' => $clientId],
            [
                'name' => 'MyDuitGW Machine Client',
                'client_secret' => Hash::make($clientSecret),
                'is_active' => true,
            ]
        );
    }
}
