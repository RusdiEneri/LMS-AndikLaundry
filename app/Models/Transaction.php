<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'cashier_id',
        'shift_id',
        'approved_by_id',
        'production_status',
        'payment_status',
        'intake_at',
        'target_completion_at',
        'actual_completion_at',
        'picked_up_at',
        'subtotal_amount',
        'discount_amount',
        'surcharge_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'change_amount',
        'outstanding_balance',
        'staging_rack',
        'rewash_count',
        'notes',
        'is_debt_approved',
        'debt_notes',
        'cancellation_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'production_status' => ProductionStatus::class,
            'payment_status' => PaymentStatus::class,
            'intake_at' => 'datetime',
            'target_completion_at' => 'datetime',
            'actual_completion_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'surcharge_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'is_debt_approved' => 'boolean',
            'rewash_count' => 'integer',
        ];
    }

    /**
     * Get the customer that owns the transaction.
     *
     * @return BelongsTo<Customer, Transaction>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the cashier/user who created the transaction.
     *
     * @return BelongsTo<User, Transaction>
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the user who approved credit/debt for the transaction.
     *
     * @return BelongsTo<User, Transaction>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * Get the line items belonging to this transaction.
     *
     * @return HasMany<TransactionItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    /**
     * Get the payments recorded for this transaction.
     *
     * @return HasMany<Payment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'transaction_id');
    }

    /**
     * Get the status transition history for this transaction.
     *
     * @return HasMany<TransactionStatusHistory>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(TransactionStatusHistory::class, 'transaction_id');
    }

    /**
     * Recalculate financial totals from items, discounts, surcharges, and payments.
     */
    public function recalculateTotals(): self
    {
        $subtotal = (float) $this->items()->sum('subtotal_snapshot');
        $discount = (float) ($this->discount_amount ?? 0.00);
        $surcharge = (float) ($this->surcharge_amount ?? 0.00);
        $tax = (float) ($this->tax_amount ?? 0.00);

        $total = max(0.00, round(($subtotal - $discount) + $surcharge + $tax, 2));
        $paid = (float) ($this->paid_amount ?? 0.00);

        $change = max(0.00, round($paid - $total, 2));
        $outstanding = max(0.00, round($total - $paid, 2));

        $this->subtotal_amount = $subtotal;
        $this->total_amount = $total;
        $this->change_amount = $change;
        $this->outstanding_balance = $outstanding;

        if ($outstanding <= 0.00 && $total > 0.00) {
            $this->payment_status = $paid > $total ? PaymentStatus::OVERPAID : PaymentStatus::PAID;
        } elseif ($paid > 0.00) {
            $this->payment_status = PaymentStatus::PARTIALLY_PAID;
        } elseif ($total == 0.00) {
            $this->payment_status = PaymentStatus::PAID;
        } else {
            $this->payment_status = PaymentStatus::UNPAID;
        }

        return $this;
    }

    /**
     * Check if transaction is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->payment_status === PaymentStatus::PAID
            || $this->payment_status === PaymentStatus::OVERPAID
            || (float) $this->outstanding_balance <= 0.00;
    }

    /**
     * Scope query to unpaid or partially paid transactions.
     *
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('payment_status', [
            PaymentStatus::UNPAID->value,
            PaymentStatus::PARTIALLY_PAID->value,
        ]);
    }

    /**
     * Scope query to transactions actively in production.
     *
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeInProduction(Builder $query): Builder
    {
        return $query->whereNotIn('production_status', [
            ProductionStatus::COMPLETED->value,
            ProductionStatus::CANCELLED->value,
        ]);
    }
}
