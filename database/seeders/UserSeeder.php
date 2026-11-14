<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Andik (Owner)',
                'email' => 'owner@andiklaundry.test',
                'phone' => '6281234567001',
                'password' => Hash::make('Password123!'),
                'role' => 'owner',
                'is_active' => true,
            ],
            [
                'name' => 'Siti (Kasir)',
                'email' => 'cashier@andiklaundry.test',
                'phone' => '6281234567002',
                'password' => Hash::make('Password123!'),
                'role' => 'cashier',
                'is_active' => true,
            ],
            [
                'name' => 'Budi (Operator)',
                'email' => 'operator@andiklaundry.test',
                'phone' => '6281234567003',
                'password' => Hash::make('Password123!'),
                'role' => 'operator',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Synchronize with Spatie-compatible RBAC model_has_roles
            $role = Role::where('name', $userData['role'])->first();
            if ($role) {
                $user->assignRole($role);
            }
        }
    }
}
