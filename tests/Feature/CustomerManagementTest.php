<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\QueryException;

/*
|--------------------------------------------------------------------------
| Milestone 1: Customer Management Feature Tests
|--------------------------------------------------------------------------
| Specification Source: docs/PRD.md FEAT-101, docs/security.md §9.1 & §9.2,
|                       docs/database-schema.md Table 5
*/

describe('Milestone 1: Customer Model & Phone Normalization Invariant', function () {

    it('T1-CUST-01: Customer model automatically normalizes domestic Indonesian phone to canonical E.164 on saving', function () {
        $customer = Customer::create([
            'name' => 'Budi Santoso',
            'phone' => '0812-3456-7890',
            'email' => 'budi@example.com',
            'address' => 'Jl. Kebon Jeruk No. 10',
            'whatsapp_consent' => true,
        ]);

        expect($customer->phone)->toBe('0812-3456-7890')
            ->and($customer->phone_normalized)->toBe('6281234567890');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => '0812-3456-7890',
            'phone_normalized' => '6281234567890',
        ]);
    });

    it('T1-CUST-02: Normalizes various phone input variants (+62, raw 8, spaces, dots) to identical canonical E.164', function () {
        $c1 = Customer::create(['name' => 'User Plus', 'phone' => '+62 857 1122 3344']);
        $c2 = Customer::create(['name' => 'User Eight', 'phone' => '878-9988-7766']);
        $c3 = Customer::create(['name' => 'User Dots', 'phone' => '0896.1234.5678']);

        expect($c1->phone_normalized)->toBe('6285711223344')
            ->and($c2->phone_normalized)->toBe('6287899887766')
            ->and($c3->phone_normalized)->toBe('6289612345678');
    });

    it('T1-CUST-03: Enforces database unique constraint on phone_normalized', function () {
        Customer::create([
            'name' => 'First Registrant',
            'phone' => '0812-3456-7890',
        ]);

        // Attempting to register another customer with the same phone in a different format
        expect(function () {
            Customer::create([
                'name' => 'Duplicate Registrant',
                'phone' => '+62 812 3456 7890',
            ]);
        })->toThrow(QueryException::class);
    });

    it('T1-CUST-04: Automatically tracks WhatsApp consent flag and records consent_at timestamp', function () {
        $consentingCustomer = Customer::create([
            'name' => 'Pelanggan Setuju',
            'phone' => '081211112222',
            'whatsapp_consent' => true,
        ]);

        expect($consentingCustomer->whatsapp_consent)->toBeTrue()
            ->and($consentingCustomer->consent_at)->not->toBeNull();

        $nonConsentingCustomer = Customer::create([
            'name' => 'Pelanggan Menolak',
            'phone' => '081233334444',
            'whatsapp_consent' => false,
        ]);

        expect($nonConsentingCustomer->whatsapp_consent)->toBeFalse()
            ->and($nonConsentingCustomer->consent_at)->toBeNull();
    });
});

describe('Milestone 1: Customer Quick Search Endpoint (GET /api/pos/customers/search)', function () {

    beforeEach(function () {
        $this->cashier = User::factory()->cashier()->create();
    });

    it('T1-CUST-05: Authenticated cashier can search customers by name', function () {
        Customer::factory()->create([
            'name' => 'Siti Aminah Rahmawati',
            'phone' => '085712345678',
            'outstanding_receivables' => 45000.00,
        ]);
        Customer::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081298765432',
            'outstanding_receivables' => 0.00,
        ]);

        $response = $this->actingAs($this->cashier)
            ->getJson('/api/pos/customers/search?q=Aminah');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'name' => 'Siti Aminah Rahmawati',
                'phone_normalized' => '6285712345678',
                'outstanding_receivables' => '45000.00',
            ]);
    });

    it('T1-CUST-06: Searches customers by raw or normalized phone number', function () {
        $customer = Customer::factory()->create([
            'name' => 'Dewi Lestari',
            'phone' => '089612345678',
            'outstanding_receivables' => 0.00,
        ]);

        // Search using raw domestic format
        $resDomestic = $this->actingAs($this->cashier)
            ->getJson('/api/pos/customers/search?q=089612345678');

        $resDomestic->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Dewi Lestari']);

        // Search using canonical E.164 format
        $resCanonical = $this->actingAs($this->cashier)
            ->getJson('/api/pos/customers/search?q=6289612345678');

        $resCanonical->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Dewi Lestari']);
    });

    it('T1-CUST-07: Returns empty array when query is empty or no match found', function () {
        Customer::factory()->count(3)->create();

        $emptyQueryResponse = $this->actingAs($this->cashier)
            ->getJson('/api/pos/customers/search?q=');
        $emptyQueryResponse->assertOk()
            ->assertExactJson([]);

        $noMatchResponse = $this->actingAs($this->cashier)
            ->getJson('/api/pos/customers/search?q=NonExistentCustomerNameXYZ');
        $noMatchResponse->assertOk()
            ->assertExactJson([]);
    });
});

describe('Milestone 1: Customer Creation Endpoint (POST /api/pos/customers)', function () {

    beforeEach(function () {
        $this->cashier = User::factory()->cashier()->create();
    });

    it('T1-CUST-08: Cashier can create a new customer and receives normalized E.164 profile', function () {
        $payload = [
            'name' => 'Ahmad Hidayat',
            'phone' => '0878-1122-3344',
            'email' => 'ahmad.hidayat@example.com',
            'address' => 'Jl. Tebet Barat No. 5',
            'notes' => 'Alergi deterjen wangi tajam',
            'whatsapp_consent' => true,
        ];

        $response = $this->actingAs($this->cashier)
            ->postJson('/api/pos/customers', $payload);

        $response->assertCreated()
            ->assertJsonPath('name', 'Ahmad Hidayat')
            ->assertJsonPath('phone_normalized', '6287811223344')
            ->assertJsonPath('whatsapp_consent', true);

        $this->assertDatabaseHas('customers', [
            'name' => 'Ahmad Hidayat',
            'phone_normalized' => '6287811223344',
        ]);
    });

    it('T1-CUST-09: Validates phone number and rejects non-Indonesian or malformed formats', function () {
        // Less than 10 digits
        $resShort = $this->actingAs($this->cashier)
            ->postJson('/api/pos/customers', [
                'name' => 'Invalid Phone',
                'phone' => '0812',
            ]);
        $resShort->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);

        // Non-numeric phone
        $resAlpha = $this->actingAs($this->cashier)
            ->postJson('/api/pos/customers', [
                'name' => 'Alpha Phone',
                'phone' => '0812-ABC-5678',
            ]);
        $resAlpha->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    });

    it('T1-CUST-10: Validates and rejects duplicate customer phone numbers', function () {
        Customer::factory()->create([
            'name' => 'Existing Customer',
            'phone' => '081234567890',
        ]);

        $response = $this->actingAs($this->cashier)
            ->postJson('/api/pos/customers', [
                'name' => 'Duplicate Phone Attempt',
                'phone' => '+62 812 3456 7890',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_normalized']);
    });

    it('T1-CUST-11: Unauthenticated guests are denied access to customer endpoints', function () {
        $this->getJson('/api/pos/customers/search?q=Budi')
            ->assertUnauthorized();

        $this->postJson('/api/pos/customers', ['name' => 'Hacker', 'phone' => '081234567890'])
            ->assertUnauthorized();
    });
});
