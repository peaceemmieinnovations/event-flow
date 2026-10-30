<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESSFUL = 'successful';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ABANDONED = 'abandoned';

    public const STATUS_REFUNDED = 'refunded';

    /**
     * Only verified successful payments count as revenue.
     *
     * @var list<string>
     */
    public const REVENUE_STATUSES = [self::STATUS_SUCCESSFUL];

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SUCCESSFUL,
        self::STATUS_FAILED,
        self::STATUS_ABANDONED,
        self::STATUS_REFUNDED,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'registration_id',
        'event_id',
        'reference',
        'gateway',
        'gateway_transaction_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Registration, $this> */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeRetried(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_ABANDONED], true);
    }

    /**
     * Minor units (kobo/cents) rendered as a major-unit decimal string.
     */
    public function formattedAmount(): string
    {
        return Money::format($this->amount, $this->currency);
    }

    public function formattedAmountWithCode(): string
    {
        return Money::formatWithCode($this->amount, $this->currency);
    }
}
