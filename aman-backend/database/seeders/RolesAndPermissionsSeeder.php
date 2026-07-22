<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'customers.view', 'customers.manage',
            'captains.view', 'captains.approve', 'captains.manage',
            'trips.view',
            'pricing.manage',
            'cities.manage',
            'wallets.view', 'wallets.manage',
            'settings.manage',
            'admins.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name], ['module' => explode('.', $name)[0]]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin'], ['description' => 'صلاحيات كاملة على النظام']);
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        $operations = Role::firstOrCreate(['name' => 'operations'], ['description' => 'إدارة الرحلات والكباتن يوميًا']);
        $operations->permissions()->sync(Permission::whereIn('name', [
            'customers.view', 'captains.view', 'captains.approve', 'trips.view',
        ])->pluck('id'));

        $finance = Role::firstOrCreate(['name' => 'finance'], ['description' => 'إدارة الأسعار والمحافظ']);
        $finance->permissions()->sync(Permission::whereIn('name', [
            'pricing.manage', 'wallets.view', 'wallets.manage',
        ])->pluck('id'));

        // حساب أدمن افتراضي أول (يجب تغيير كلمة المرور فورًا في الإنتاج)
        $admin = Admin::firstOrCreate(
            ['email' => 'admin@aman.mr'],
            ['full_name' => 'Super Admin', 'password_hash' => Hash::make('ChangeMe123!'), 'is_active' => true],
        );
        $admin->roles()->syncWithoutDetaching([$superAdmin->id]);
    }
}
