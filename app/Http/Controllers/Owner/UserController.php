<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreUserRequest;
use App\Http\Requests\Owner\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of staff users with search and filter capabilities.
     */
    public function index(Request $request): Response
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->has('is_active') && $request->input('is_active') !== null && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $users = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Owner/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', ''),
                'is_active' => $request->input('is_active', ''),
            ],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'password' => $validated['password'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $role = Role::where('name', $validated['role'])->first();
        if ($role) {
            $user->assignRole($role);
        }

        AuditLog::record(
            action: 'create',
            auditable: $user,
            oldValues: null,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
            ],
            userId: $request->user()->id
        );

        return redirect()->route('owner.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Redundant defense-in-depth against self-demotion & self-deactivation
        if ($request->user()->id === $user->id) {
            if ($request->filled('role') && $request->input('role') !== 'owner') {
                abort(403, 'Anda tidak dapat mengubah peran akun Anda sendiri.');
            }
            if ($request->has('is_active') && ! filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)) {
                abort(403, 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            }
        }

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
        ];

        $validated = $request->validated();

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
        ];

        if (isset($validated['is_active'])) {
            $updateData['is_active'] = $validated['is_active'];
        }

        if (! empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $user->update($updateData);

        $role = Role::where('name', $validated['role'])->first();
        if ($role) {
            $user->roles()->syncWithPivotValues([$role->id], ['model_type' => User::class]);
        }

        AuditLog::record(
            action: 'update',
            auditable: $user,
            oldValues: $oldValues,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
            ],
            userId: $request->user()->id
        );

        return redirect()->route('owner.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Remove the specified user from storage (soft delete).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Guard against self-deletion
        if ($request->user()->id === $user->id) {
            abort(403, 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
        ];

        $user->delete();

        AuditLog::record(
            action: 'delete',
            auditable: $user,
            oldValues: $oldValues,
            newValues: null,
            userId: $request->user()->id
        );

        return redirect()->route('owner.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        // Guard against self-deactivation
        if ($request->user()->id === $user->id) {
            abort(403, 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $oldStatus = (bool) $user->is_active;
        $newStatus = ! $oldStatus;

        $user->update(['is_active' => $newStatus]);

        AuditLog::record(
            action: 'toggle_status',
            auditable: $user,
            oldValues: ['is_active' => $oldStatus],
            newValues: ['is_active' => $newStatus],
            userId: $request->user()->id
        );

        return redirect()->route('owner.users.index')->with('success', 'Status pengguna berhasil diperbarui.');
    }
}
