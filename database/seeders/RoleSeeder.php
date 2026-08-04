<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Models\User;

class RoleSeeder extends Seeder
{
     /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'developer']);
        // Customer Service
        Role::create(['name' => 'csr-manager']);
        Role::create(['name' => 'csr-team-lead']);
        Role::create(['name' => 'csr']);
        // Designers
        Role::create(['name' => 'designer-manager']);
        Role::create(['name' => 'designer-team-lead']);
        Role::create(['name' => 'designer']);



        // Admin
        $admin = Role::findByName('admin');
        $admin->givePermissionTo('create');
        $admin->givePermissionTo('read');
        $admin->givePermissionTo('update');
        $admin->givePermissionTo('delete');

        // give extra permission to user with admin role

        // $user = User::where('id', 1)->first();
        // $user->givePermissionTo('extra-permission');

        // Developer
        $developer = Role::findByName('developer');
        $developer->givePermissionTo('create');
        $developer->givePermissionTo('read');
        $developer->givePermissionTo('update');
        $developer->givePermissionTo('delete');

        // Customer Service
        $csrManager = Role::findByName('csr-manager');
        $csrManager->givePermissionTo('create');
        $csrManager->givePermissionTo('read');
        $csrManager->givePermissionTo('update');

        // Designer
        $designerManager = Role::findByName('designer-manager');
        $designerManager->givePermissionTo('create');
        $designerManager->givePermissionTo('read');
        $designerManager->givePermissionTo('update');

    }
}
