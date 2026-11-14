<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'transaction_id',
        'cashier_id',
        'shift_id',
        'payment_number',
        'idempotency_key',
        'payment_method',
        'amount',
        'tender_amount',
        'change_amount',
        'payment_status',
        'transaction_reference',
        'paid_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'tender_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the transaction associated with this payment.
     *
     * @return BelongsTo<Transaction, Payment>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Get the order alias associated with this payment.
     *
     * @return BelongsTo<Transaction, Payment>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Get the cashier who recorded this payment.
     *
     * @return BelongsTo<User, Payment>
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the user alias who recorded this payment.
     *
     * @return BelongsTo<User, Payment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the shift associated with this payment if available.
     */
    public function shift(): BelongsTo
    {
        /** @var class-string<Model> $shiftClass */
        $shiftClass = class_exists(Shift::class)
            ? Shift::class
            : 'App\\Models\\Shift';

        return $this->belongsTo($shiftClass, 'shift_id');
    }
}
