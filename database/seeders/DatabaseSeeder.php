<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use StatesTableSeeder;
use CitiesTableSeeder;
use Illuminate\Database\Seeder;



class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            GroupHasPermissionSeeder::class,
            // GroupSeeder::class,
            // PermissionSeeder::class,
            // RoleSeeder::class,
            // UserSeeder::class,
            // CountrySeeder::class,
            // StateSeeder::class,
            // CitySeeder::class,
        ]);
    }
}
