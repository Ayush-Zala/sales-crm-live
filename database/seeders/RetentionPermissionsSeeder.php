<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RetentionPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Get or create the Retention Permissions group
        $retentionGroup = Group::firstOrCreate(['name' => 'Retention Permissions']);

        // 2. Remove "Can View Users" from Retention Permissions group
        $canViewUsers = Permission::where('name', 'Can View Users')->first();
        if ($canViewUsers && $retentionGroup) {
            DB::table('group_has_permissions')
                ->where('group_id', $retentionGroup->id)
                ->where('permission_id', $canViewUsers->id)
                ->delete();
        }

        // 3. Create or get "Can View Retention" permission
        $canViewRetention = Permission::firstOrCreate(['name' => 'Can View Retention', 'guard_name' => 'web']);
        
        // 4. Create or get "Can Edit Retention Assign User" permission
        $canAssignRetention = Permission::firstOrCreate(['name' => 'Can Edit Retention Assign User', 'guard_name' => 'web']);

        // 5. Map permissions to the Retention group
        $permissionsToMap = [$canViewRetention->id, $canAssignRetention->id];
        foreach ($permissionsToMap as $permissionId) {
            $exists = DB::table('group_has_permissions')
                ->where('group_id', $retentionGroup->id)
                ->where('permission_id', $permissionId)
                ->exists();
            if (!$exists) {
                DB::table('group_has_permissions')->insert([
                    'group_id' => $retentionGroup->id,
                    'permission_id' => $permissionId
                ]);
            }
        }

        // 6. Assign "Can View Retention" to roles
        $rolesToView = [
            'Admin',
            'Customer Service Representative Manager',
            'Customer Service Representative Team Lead',
            'Customer Service Representative',
        ];
        foreach ($rolesToView as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && !$role->hasPermissionTo('Can View Retention')) {
                $role->givePermissionTo('Can View Retention');
            }
        }

        // 7. Ensure "Can Edit Retention Assign User" is only on Admin and CSR Manager
        $allRoles = Role::all();
        foreach ($allRoles as $role) {
            if (in_array($role->name, ['Admin', 'Customer Service Representative Manager'])) {
                if (!$role->hasPermissionTo('Can Edit Retention Assign User')) {
                    $role->givePermissionTo('Can Edit Retention Assign User');
                }
            } else {
                if ($role->hasPermissionTo('Can Edit Retention Assign User')) {
                    $role->revokePermissionTo('Can Edit Retention Assign User');
                }
            }
        }
    }
}
