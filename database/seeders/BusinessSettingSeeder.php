<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class BusinessSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Store Settings (4)
            [
                'key' => 'store.name',
                'value' => 'Andik Laundry',
                'type' => 'string',
                'group' => 'store',
                'description' => 'Nama resmi outlet laundry',
            ],
            [
                'key' => 'store.phone',
                'value' => '6281234567890',
                'type' => 'string',
                'group' => 'store',
                'description' => 'Nomor WhatsApp kontak utama toko',
            ],
            [
                'key' => 'store.address',
                'value' => 'Jl. Raya Laundry No. 12, Jakarta',
                'type' => 'string',
                'group' => 'store',
                'description' => 'Alamat fisik toko pada nota transaksi',
            ],
            [
                'key' => 'store.receipt_footer',
                'value' => 'Terima kasih telah mencuci di Andik Laundry. Bersih, Wangi, Rapi!',
                'type' => 'string',
                'group' => 'store',
                'description' => 'Catatan kaki pada cetakan nota thermal',
            ],

            // Operational Limits (5)
            [
                'key' => 'operational.default_min_weight',
                'value' => '3.00',
                'type' => 'decimal',
                'group' => 'operational',
                'description' => 'Minimum cas kiloan default (kg)',
            ],
            [
                'key' => 'operational.customer_credit_ceiling',
                'value' => '150000.00',
                'type' => 'decimal',
                'group' => 'operational',
                'description' => 'Batas maksimum plafon piutang per pelanggan (Rp)',
            ],
            [
                'key' => 'operational.default_debt_due_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'operational',
                'description' => 'Jatuh tempo piutang default (hari)',
            ],
            [
                'key' => 'operational.cashier_petty_cash_limit',
                'value' => '100000.00',
                'type' => 'decimal',
                'group' => 'operational',
                'description' => 'Batas kas kecil kasir tanpa persetujuan owner (Rp)',
            ],
            [
                'key' => 'operational.max_rewash_limit',
                'value' => '2',
                'type' => 'integer',
                'group' => 'operational',
                'description' => 'Batas siklus cuci ulang sebelum eskalasi owner',
            ],

            // Financial Defaults (2)
            [
                'key' => 'financial.tax_rate_percent',
                'value' => '0.00',
                'type' => 'decimal',
                'group' => 'financial',
                'description' => 'Tarif PPN (0.00% untuk UMKM non-PKP)',
            ],
            [
                'key' => 'financial.currency_code',
                'value' => 'IDR',
                'type' => 'string',
                'group' => 'financial',
                'description' => 'Kode mata uang standar',
            ],

            // Printer Configuration (2)
            [
                'key' => 'printer.paper_width',
                'value' => '58mm',
                'type' => 'string',
                'group' => 'printer',
                'description' => 'Lebar kertas printer nota thermal (58mm / 80mm)',
            ],
            [
                'key' => 'printer.auto_print',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'printer',
                'description' => 'Cetak nota otomatis saat transaksi selesai',
            ],

            // WhatsApp Gateway (2)
            [
                'key' => 'whatsapp.provider',
                'value' => 'wuzapi',
                'type' => 'string',
                'group' => 'whatsapp',
                'description' => 'Gateway WhatsApp aktif (wuzapi / meta_cloud / manual_link)',
            ],
            [
                'key' => 'whatsapp.anti_ban_rate_limit',
                'value' => '12',
                'type' => 'integer',
                'group' => 'whatsapp',
                'description' => 'Batas pengiriman pesan per menit',
            ],
        ];

        foreach ($settings as $setting) {
            BusinessSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
