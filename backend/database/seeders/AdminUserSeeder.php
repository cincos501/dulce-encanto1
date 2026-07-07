<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'Administrador')->first();

        $admin = User::where('email', 'admin@dulceencanto.com')->first();

        if ($admin) {
            $admin->update([
                'full_name' => 'Administrador',
                'phone' => '+56912345678',
                'is_active' => true,
            ]);
        } else {
            $admin = User::create([
                'full_name' => 'Administrador',
                'email' => 'admin@dulceencanto.com',
                'password' => Hash::make('admin123456'),
                'phone' => '+56912345678',
                'is_active' => true,
            ]);
        }

        if ($adminRole && ! $admin->hasRole('Administrador')) {
            $admin->assignRole($adminRole);
        }
    }
}
