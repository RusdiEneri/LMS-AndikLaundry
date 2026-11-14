<?php

namespace App\Models;

use App\Support\PhoneFormatter;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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
        'consent_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_orders_count' => 'integer',
            'total_spend' => 'decimal:2',
            'outstanding_receivables' => 'decimal:2',
            'whatsapp_consent' => 'boolean',
            'consent_at' => 'datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Customer $customer): void {
            if (!empty($customer->phone)) {
                $customer->phone_normalized = PhoneFormatter::normalizeToE164($customer->phone);
            }

            if ($customer->whatsapp_consent && is_null($customer->consent_at)) {
                $customer->consent_at = now();
            } elseif (!$customer->whatsapp_consent) {
                $customer->consent_at = null;
            }
        });
    }

    /**
     * Normalize an Indonesian phone number to canonical E.164.
     */
    public static function normalizePhone(string $phone): string
    {
        return PhoneFormatter::normalizeToE164($phone);
    }

    /**
     * Scope a query to search customers by name or phone number.
     *
     * @param  Builder<Customer>  $query
     * @return Builder<Customer>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $normalizedTerm = PhoneFormatter::normalizeToE164($term);

        return $query->where(function (Builder $subQuery) use ($term, $normalizedTerm): void {
            $subQuery->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('phone_normalized', 'like', "%{$term}%");

            if (!empty($normalizedTerm)) {
                $subQuery->orWhere('phone_normalized', 'like', "%{$normalizedTerm}%");
            }
        });
    }

    /**
     * Get all transactions belonging to this customer.
     *
     * @return HasMany<Transaction>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }

    /**
     * Alias relation for orders.
     *
     * @return HasMany<Transaction>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }

    /**
     * Recalculate customer receivables balance from all outstanding transactions.
     */
    public function updateReceivables(): self
    {
        $this->outstanding_receivables = (float) $this->transactions()->sum('outstanding_balance');
        $this->save();

        return $this;
    }
}
