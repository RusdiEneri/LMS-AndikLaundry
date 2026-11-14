<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Support\PhoneFormatter;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $canonicalCustomers = [
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'email' => 'budi.santoso@example.com',
                'address' => 'Jl. Kebon Jeruk No. 15, Jakarta Barat',
                'total_orders_count' => 12,
                'total_spend' => 350000.00,
                'outstanding_receivables' => 0.00,
                'notes' => 'Pelanggan loyal, suka wangi Sakura.',
                'whatsapp_consent' => true,
            ],
            [
                'name' => 'Siti Aminah',
                'phone' => '085712345678',
                'email' => 'siti.aminah@example.com',
                'address' => 'Jl. Tebet Timur Dalam No. 8, Jakarta Selatan',
                'total_orders_count' => 5,
                'total_spend' => 175000.00,
                'outstanding_receivables' => 35000.00,
                'notes' => 'Ada sisa pembayaran cucian sebelumnya.',
                'whatsapp_consent' => true,
            ],
            [
                'name' => 'Ahmad Hidayat',
                'phone' => '087812345678',
                'email' => 'ahmad.hidayat@example.com',
                'address' => 'Jl. Melawai Raya No. 22, Jakarta Selatan',
                'total_orders_count' => 8,
                'total_spend' => 220000.00,
                'outstanding_receivables' => 0.00,
                'notes' => 'Sering titip jas dan kemeja satuan.',
                'whatsapp_consent' => true,
            ],
            [
                'name' => 'Dewi Lestari',
                'phone' => '089612345678',
                'email' => 'dewi.lestari@example.com',
                'address' => 'Jl. Cempaka Putih Tengah No. 4, Jakarta Pusat',
                'total_orders_count' => 3,
                'total_spend' => 90000.00,
                'outstanding_receivables' => 0.00,
                'notes' => 'Paling suka wangi Lavender.',
                'whatsapp_consent' => true,
            ],
            [
                'name' => 'Rudi Hermawan',
                'phone' => '088112345678',
                'email' => 'rudi.hermawan@example.com',
                'address' => 'Jl. Margonda Raya No. 50, Depok',
                'total_orders_count' => 6,
                'total_spend' => 310000.00,
                'outstanding_receivables' => 75000.00,
                'notes' => 'Ada piutang pending jatuh tempo.',
                'whatsapp_consent' => false,
            ],
        ];

        foreach ($canonicalCustomers as $data) {
            Customer::updateOrCreate(
                ['phone_normalized' => PhoneFormatter::normalizeToE164($data['phone'])],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'address' => $data['address'],
                    'total_orders_count' => $data['total_orders_count'],
                    'total_spend' => $data['total_spend'],
                    'outstanding_receivables' => $data['outstanding_receivables'],
                    'notes' => $data['notes'],
                    'whatsapp_consent' => $data['whatsapp_consent'],
                    'consent_at' => $data['whatsapp_consent'] ? now() : null,
                ]
            );
        }
    }
}
