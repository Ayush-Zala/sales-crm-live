<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a user with the admin role
        User::create([
            'name' => 'Admin',
            'email' => "admin@patterns247.net",
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'reporting_authority_id' => 1,
            'remember_token' => Str::random(10),
        ])->assignRole('admin');

        // Create a user with the developer role
        User::create([
            'name' => 'Developer',
            'email' => "dev@patterns247.net",
            'email_verified_at' => now(),
            'reporting_authority_id' => 1,
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ])->assignRole('developer');

        $user = User::where('name', 'Admin')->first();
        $user->givePermissionTo('extra-permission');

    }
}
