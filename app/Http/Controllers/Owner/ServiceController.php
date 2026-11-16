<?php

namespace App\Http\Controllers\Owner;

use App\Enums\ServiceUnit;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    /**
     * Store a newly created service along with default turnaround tiers.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:service_categories,id'],
            'unit' => ['required', 'string', Rule::in(['kg', 'pcs', 'meter', 'm2'])],
            'base_price' => ['required', 'numeric', 'min:0'],
            'min_weight' => ['nullable', 'numeric', 'min:0'],
            'estimated_duration_hours' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'has_express' => ['nullable', 'boolean'],
            'express_surcharge' => ['nullable', 'numeric', 'min:0'],
            'express_duration_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        $baseCode = Str::upper(Str::slug($validated['name']));
        $code = $baseCode;
        if (Service::withTrashed()->where('code', $code)->exists()) {
            $code = $baseCode.'-'.strtoupper(Str::random(4));
        }

        $minWeight = $validated['min_weight'] ?? ($validated['unit'] === 'kg' ? 2.0 : 1.0);

        $service = Service::create([
            'category_id' => $validated['category_id'],
            'code' => $code,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'unit' => ServiceUnit::from($validated['unit']),
            'base_price' => $validated['base_price'],
            'min_weight' => $minWeight,
            'estimated_duration_hours' => $validated['estimated_duration_hours'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Default Reguler Tier
        $service->tiers()->create([
            'tier_name' => 'Reguler',
            'tier_code' => 'reguler',
            'multiplier_factor' => 1.00,
            'flat_surcharge' => 0.00,
            'duration_hours' => (int) $validated['estimated_duration_hours'],
            'is_active' => true,
        ]);

        // Optional Ekspres Tier
        if ($request->boolean('has_express') || (float) ($validated['express_surcharge'] ?? 0) > 0) {
            $service->tiers()->create([
                'tier_name' => 'Ekspres',
                'tier_code' => 'ekspres',
                'multiplier_factor' => 1.00,
                'flat_surcharge' => (float) ($validated['express_surcharge'] ?? 2000.00),
                'duration_hours' => (int) ($validated['express_duration_hours'] ?? 12),
                'is_active' => true,
            ]);
        }

        AuditLog::record(
            action: 'create',
            auditable: $service,
            oldValues: null,
            newValues: $service->toArray(),
            userId: $request->user()?->id
        );

        return redirect()->back()->with('success', "Layanan {$service->name} berhasil ditambahkan!");
    }

    /**
     * Update an existing service and its turnaround speed tiers.
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:service_categories,id'],
            'unit' => ['required', 'string', Rule::in(['kg', 'pcs', 'meter', 'm2'])],
            'base_price' => ['required', 'numeric', 'min:0'],
            'min_weight' => ['nullable', 'numeric', 'min:0'],
            'estimated_duration_hours' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'has_express' => ['nullable', 'boolean'],
            'express_surcharge' => ['nullable', 'numeric', 'min:0'],
            'express_duration_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        $oldValues = $service->toArray();

        $minWeight = $validated['min_weight'] ?? ($validated['unit'] === 'kg' ? 2.0 : 1.0);

        $service->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'unit' => ServiceUnit::from($validated['unit']),
            'base_price' => $validated['base_price'],
            'min_weight' => $minWeight,
            'estimated_duration_hours' => $validated['estimated_duration_hours'],
            'is_active' => $request->boolean('is_active', $service->is_active),
        ]);

        // Update or create Reguler Tier
        $service->tiers()->updateOrCreate(
            ['tier_code' => 'reguler'],
            [
                'tier_name' => 'Reguler',
                'multiplier_factor' => 1.00,
                'flat_surcharge' => 0.00,
                'duration_hours' => (int) $validated['estimated_duration_hours'],
                'is_active' => true,
            ]
        );

        // Update or toggle Ekspres Tier
        if ($request->boolean('has_express') || (float) ($validated['express_surcharge'] ?? 0) > 0) {
            $service->tiers()->updateOrCreate(
                ['tier_code' => 'ekspres'],
                [
                    'tier_name' => 'Ekspres',
                    'multiplier_factor' => 1.00,
                    'flat_surcharge' => (float) ($validated['express_surcharge'] ?? 2000.00),
                    'duration_hours' => (int) ($validated['express_duration_hours'] ?? 12),
                    'is_active' => true,
                ]
            );
        } else {
            $service->tiers()->where('tier_code', 'ekspres')->delete();
        }

        AuditLog::record(
            action: 'update',
            auditable: $service,
            oldValues: $oldValues,
            newValues: $service->fresh()->toArray(),
            userId: $request->user()?->id
        );

        return redirect()->back()->with('success', "Layanan {$service->name} berhasil diperbarui!");
    }

    /**
     * Toggle the active/inactive status of a service.
     */
    public function toggleStatus(Request $request, Service $service): RedirectResponse
    {
        $oldStatus = $service->is_active;
        $service->update(['is_active' => ! $oldStatus]);

        AuditLog::record(
            action: 'toggle_status',
            auditable: $service,
            oldValues: ['is_active' => $oldStatus],
            newValues: ['is_active' => ! $oldStatus],
            userId: $request->user()?->id
        );

        $statusText = $service->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()->with('success', "Layanan {$service->name} berhasil {$statusText}!");
    }

    /**
     * Soft delete a service.
     */
    public function destroy(Request $request, Service $service): RedirectResponse
    {
        $serviceName = $service->name;
        $oldValues = $service->toArray();

        $service->delete();

        AuditLog::record(
            action: 'delete',
            auditable: $service,
            oldValues: $oldValues,
            newValues: null,
            userId: $request->user()?->id
        );

        return redirect()->back()->with('success', "Layanan {$serviceName} berhasil dihapus!");
    }
}
