<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles
        $ownerRole = Role::firstOrCreate(
            ['name' => 'owner', 'guard_name' => 'web']
        );

        $cashierRole = Role::firstOrCreate(
            ['name' => 'cashier', 'guard_name' => 'web']
        );

        $operatorRole = Role::firstOrCreate(
            ['name' => 'operator', 'guard_name' => 'web']
        );

        // 2. Define 23 Canonical Permissions
        $permissions = [
            // POS Intake (5)
            [
                'name' => 'pos.orders.create',
                'module' => 'pos',
                'description' => 'Buat Pesanan Baru',
                'roles' => ['owner', 'cashier'],
            ],
            [
                'name' => 'pos.orders.view',
                'module' => 'pos',
                'description' => 'Lihat Antrean Pesanan',
                'roles' => ['owner', 'cashier', 'operator'],
            ],
            [
                'name' => 'pos.orders.cancel',
                'module' => 'pos',
                'description' => 'Batalkan Pesanan',
                'roles' => ['owner'],
            ],
            [
                'name' => 'pos.orders.override_price',
                'module' => 'pos',
                'description' => 'Ubah Harga/Diskon Khusus',
                'roles' => ['owner'],
            ],
            [
                'name' => 'pos.orders.release_unpaid',
                'module' => 'pos',
                'description' => 'Serahkan Cucian Belum Lunas',
                'roles' => ['owner'],
            ],

            // Cashier / Payments (2)
            [
                'name' => 'payments.create',
                'module' => 'payments',
                'description' => 'Terima Pembayaran',
                'roles' => ['owner', 'cashier'],
            ],
            [
                'name' => 'payments.refund',
                'module' => 'payments',
                'description' => 'Proses Refund',
                'roles' => ['owner'],
            ],

            // Shifts (5)
            [
                'name' => 'shifts.open',
                'module' => 'shifts',
                'description' => 'Buka Shift Kasir',
                'roles' => ['owner', 'cashier'],
            ],
            [
                'name' => 'shifts.close',
                'module' => 'shifts',
                'description' => 'Tutup Shift & Blind Count',
                'roles' => ['owner', 'cashier'],
            ],
            [
                'name' => 'shifts.cash_drop',
                'module' => 'shifts',
                'description' => 'Setor Kas (Cash Drop)',
                'roles' => ['owner', 'cashier'],
            ],
            [
                'name' => 'shifts.petty_expense',
                'module' => 'shifts',
                'description' => 'Kas Kecil Drawer',
                'roles' => ['owner', 'cashier'],
            ],
            [
                'name' => 'shifts.view_all',
                'module' => 'shifts',
                'description' => 'Lihat Semua Shift',
                'roles' => ['owner'],
            ],

            // Production (3)
            [
                'name' => 'production.status.update',
                'module' => 'production',
                'description' => 'Perbarui Tahap Produksi',
                'roles' => ['owner', 'cashier', 'operator'],
            ],
            [
                'name' => 'production.rewash.flag',
                'module' => 'production',
                'description' => 'Tandai Cuci Ulang',
                'roles' => ['owner', 'operator'],
            ],
            [
                'name' => 'production.rack.assign',
                'module' => 'production',
                'description' => 'Tempatkan ke Rak',
                'roles' => ['owner', 'cashier', 'operator'],
            ],

            // Customers (1)
            [
                'name' => 'customers.manage',
                'module' => 'customers',
                'description' => 'Kelola Pelanggan',
                'roles' => ['owner', 'cashier'],
            ],

            // Catalog (1)
            [
                'name' => 'catalog.manage',
                'module' => 'catalog',
                'description' => 'Kelola Katalog Layanan',
                'roles' => ['owner'],
            ],

            // Expenses (2)
            [
                'name' => 'expenses.create',
                'module' => 'expenses',
                'description' => 'Catat Pengeluaran',
                'roles' => ['owner', 'cashier'],
            ],
            [
                'name' => 'expenses.manage',
                'module' => 'expenses',
                'description' => 'Kelola & Setujui Biaya',
                'roles' => ['owner'],
            ],

            // Reports (2)
            [
                'name' => 'reports.financial.view',
                'module' => 'reports',
                'description' => 'Lihat Laporan Keuangan',
                'roles' => ['owner'],
            ],
            [
                'name' => 'reports.operational.view',
                'module' => 'reports',
                'description' => 'Lihat Laporan Operasional',
                'roles' => ['owner', 'cashier'],
            ],

            // System Settings (1)
            [
                'name' => 'system.settings.manage',
                'module' => 'settings',
                'description' => 'Kelola Pengaturan Sistem',
                'roles' => ['owner'],
            ],

            // Audit (1)
            [
                'name' => 'system.audit.view',
                'module' => 'audit',
                'description' => 'Lihat Jejak Audit Log',
                'roles' => ['owner'],
            ],
        ];

        // 3. Seed Permissions and Map to Roles
        $ownerPermissionIds = [];
        $cashierPermissionIds = [];
        $operatorPermissionIds = [];

        foreach ($permissions as $permData) {
            $permission = Permission::updateOrCreate(
                ['name' => $permData['name'], 'guard_name' => 'web'],
                [
                    'module' => $permData['module'],
                    'description' => $permData['description'],
                ]
            );

            if (in_array('owner', $permData['roles'])) {
                $ownerPermissionIds[] = $permission->id;
            }
            if (in_array('cashier', $permData['roles'])) {
                $cashierPermissionIds[] = $permission->id;
            }
            if (in_array('operator', $permData['roles'])) {
                $operatorPermissionIds[] = $permission->id;
            }
        }

        // 4. Synchronize Role Permissions
        $ownerRole->permissions()->sync($ownerPermissionIds);
        $cashierRole->permissions()->sync($cashierPermissionIds);
        $operatorRole->permissions()->sync($operatorPermissionIds);
    }
}
