<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'expenses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'expense_number',
        'user_id',
        'amount',
        'category',
        'description',
        'payment_method',
        'expense_date',
        'receipt_image_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    /**
     * User who recorded the expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for a specific date.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('expense_date', $date);
    }

    /**
     * Scope for a date range.
     */
    public function scopeDateBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    /**
     * Generate sequential unique expense number format: EXP-YYYYMMDD-XXXX
     */
    public static function generateExpenseNumber(?Carbon $date = null): string
    {
        $targetDate = $date ?? Carbon::today();
        $datePrefix = 'EXP-' . $targetDate->format('Ymd') . '-';

        $lastExpense = static::where('expense_number', 'LIKE', $datePrefix . '%')
            ->orderByDesc('id')
            ->first();

        if ($lastExpense) {
            $lastSequence = (int) substr($lastExpense->expense_number, -4);
            $newSequence = str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newSequence = '0001';
        }

        return $datePrefix . $newSequence;
    }
}

