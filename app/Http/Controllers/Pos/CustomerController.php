<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Search customers by name or phone number for POS lookup.
     * GET /api/pos/customers/search?q={query}
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json([]);
        }

        $customers = Customer::search($term)
            ->select([
                'id',
                'name',
                'phone',
                'phone_normalized',
                'email',
                'address',
                'total_orders_count',
                'total_spend',
                'outstanding_receivables',
                'notes',
                'whatsapp_consent',
            ])
            ->orderByDesc('total_orders_count')
            ->limit(20)
            ->get();

        return response()->json($customers);
    }

    /**
     * Store a newly created customer profile.
     * POST /api/pos/customers
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! isset($validated['whatsapp_consent'])) {
            $validated['whatsapp_consent'] = true;
        }

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }
}
