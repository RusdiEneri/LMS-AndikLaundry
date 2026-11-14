<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Role Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function assignRole(Role|string $role): self
    {
        $roleModel = is_string($role) ? Role::where('name', $role)->firstOrFail() : $role;
        $this->roles()->syncWithPivotValues([$roleModel->id], ['model_type' => self::class], false);
        return $this;
    }

    public function hasRole(Role|string $role): bool
    {
        $roleName = is_string($role) ? $role : $role->name;
        return $this->role === $roleName || $this->roles->contains('name', $roleName);
    }

    /**
     * Check if user has specific permission via DB role relations or fallback map.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        // 1. Check via database role-permission relationship
        if ($this->relationLoaded('roles')) {
            if ($this->roles->flatMap->permissions->contains('name', $permission)) {
                return true;
            }
        } else {
            $hasDbPerm = $this->roles()
                ->whereHas('permissions', fn ($q) => $q->where('name', $permission))
                ->exists();

            if ($hasDbPerm) {
                return true;
            }
        }

        // 2. Fallback to canonical role-permission matrix
        $rolePermissions = self::canonicalPermissions();
        $allowedRoles = $rolePermissions[$permission] ?? [];

        return in_array($this->role, $allowedRoles, true);
    }

    /**
     * Canonical 23 permissions mapped to eligible roles.
     *
     * @return array<string, list<string>>
     */
    public static function canonicalPermissions(): array
    {
        return [
            'pos.orders.create'          => ['owner', 'cashier'],
            'pos.orders.view'            => ['owner', 'cashier', 'operator'],
            'pos.orders.cancel'          => ['owner'],
            'pos.orders.override_price'  => ['owner'],
            'pos.orders.release_unpaid'  => ['owner'],
            'payments.create'            => ['owner', 'cashier'],
            'payments.refund'            => ['owner'],
            'shifts.open'                => ['owner', 'cashier'],
            'shifts.close'               => ['owner', 'cashier'],
            'shifts.cash_drop'           => ['owner', 'cashier'],
            'shifts.petty_expense'       => ['owner', 'cashier'],
            'shifts.view_all'            => ['owner'],
            'production.status.update'   => ['owner', 'cashier', 'operator'],
            'production.rewash.flag'     => ['owner', 'operator'],
            'production.rack.assign'     => ['owner', 'cashier', 'operator'],
            'customers.manage'           => ['owner', 'cashier'],
            'catalog.manage'             => ['owner'],
            'expenses.create'            => ['owner', 'cashier'],
            'expenses.manage'            => ['owner'],
            'reports.financial.view'     => ['owner'],
            'reports.operational.view'   => ['owner', 'cashier'],
            'system.settings.manage'     => ['owner'],
            'system.audit.view'          => ['owner'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')
            ->withPivot('model_type')
            ->wherePivot('model_type', self::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}
