<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Group::create([
            'name' => 'Main Permissions',
            'description' => 'Main Permissions group'
        ]);
        Group::create([
            'name' => 'Client Permissions',
            'description' => 'Client Permission group'
        ]);
        Group::create([
            'name' => 'Company Permissions',
            'description' => 'Company Permission group'
        ]);
        Group::create([
            'name' => 'Lead Permissions',
            'description' => 'Lead Permission group'
        ]);
        Group::create([
            'name' => 'Show Permissions',
            'description' => 'Show Permission group'
        ]);
    }
}
