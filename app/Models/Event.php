<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_PENDING_REVIEW,
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
    ];

    /**
     * @var list<string>
     */
    public const TYPES = [self::TYPE_FREE, self::TYPE_PAID];

    /**
     * @var list<string>
     */
    public const TICKET_STATUSES = [
        self::TICKET_STATUS_VALID,
        self::TICKET_STATUS_CHECKED_IN,
        self::TICKET_STATUS_CANCELLED,
    ];

    public const TYPE_FREE = 'free';

    public const TYPE_PAID = 'paid';

    public const TICKET_STATUS_VALID = 'valid';

    public const TICKET_STATUS_CHECKED_IN = 'used';

    public const TICKET_STATUS_CANCELLED = 'cancelled';

    public const ACTIVE_REGISTRATION_STATUSES = ['pending', 'confirmed'];

    /**
     * Categories are stored as a free-form slug so organizers can add their own
     * without a migration, while the public navigation offers a curated set.
     *
     * @var list<string>
     */
    public const CATEGORIES = [
        'music',
        'arts-culture',
        'food-drink',
        'business',
        'wellness',
        'community',
    ];

    /**
     * The attributes that may be mass assigned when event creation is added.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'location',
        'event_date',
        'start_time',
        'end_time',
        'image',
        'address',
        'city',
        'state',
        'country',
        'category',
        'organizer_name',
        'organizer_email',
        'organizer_phone',
        'capacity',
        'event_type',
        'price_amount',
        'currency',
        'status',
        'registration_deadline',
        'user_id',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'registration_deadline' => 'datetime',
            'published_at' => 'datetime',
            'capacity' => 'integer',
            'price_amount' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<Registration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<CommunicationLog, $this> */
    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }

    /** @param  Builder<Event>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
    }

    /** @param  Builder<Event>  $query */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('event_date', '>=', now())->orderBy('event_date');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPaid(): bool
    {
        return $this->event_type === self::TYPE_PAID;
    }

    public function isFree(): bool
    {
        return ! $this->isPaid();
    }

    public function isPast(): bool
    {
        return $this->event_date->isPast();
    }

    public function registrationDeadlinePassed(): bool
    {
        return $this->registration_deadline !== null && now()->gt($this->registration_deadline);
    }

    /**
     * Registrations that hold a place (pending or confirmed).
     */
    /** @return HasMany<Registration, $this> */
    public function activeRegistrations(): HasMany
    {
        return $this->hasMany(Registration::class)->whereIn('status', self::ACTIVE_REGISTRATION_STATUSES);
    }

    public function registeredCount(): int
    {
        return (int) ($this->registration_count ?? $this->registrations()->whereIn('status', self::ACTIVE_REGISTRATION_STATUSES)->count());
    }

    public function checkedInCount(): int
    {
        return (int) ($this->tickets_count_checked_in ?? $this->tickets()->where('status', self::TICKET_STATUS_CHECKED_IN)->count());
    }

    public function issuedTicketCount(): int
    {
        return (int) ($this->tickets_count ?? $this->tickets()->count());
    }

    public function spotsRemaining(): ?int
    {
        if (! $this->capacity) {
            return null;
        }

        return max(0, $this->capacity - $this->registeredCount());
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->registeredCount() >= $this->capacity;
    }

    public function fillRate(): ?float
    {
        if (! $this->capacity) {
            return null;
        }

        return round($this->registeredCount() / $this->capacity * 100, 1);
    }

    /**
     * Server-side source of truth for whether a visitor may register.
     */
    public function registrationIsOpen(): bool
    {
        return $this->registrationOpenState()['open'];
    }

    /**
     * @return array{open: bool, reason: ?string}
     */
    public function registrationOpenState(): array
    {
        if (! $this->isPublished()) {
            return ['open' => false, 'reason' => $this->isCancelled() ? 'cancelled' : 'not_published'];
        }

        if ($this->isPast()) {
            return ['open' => false, 'reason' => 'past'];
        }

        if ($this->registrationDeadlinePassed()) {
            return ['open' => false, 'reason' => 'deadline_passed'];
        }

        if ($this->isFull()) {
            return ['open' => false, 'reason' => 'full'];
        }

        return ['open' => true, 'reason' => null];
    }

    public function priceLabel(): string
    {
        if ($this->isFree() || ! $this->price_amount) {
            return 'Free';
        }

        return Money::format($this->price_amount, $this->currency ?: 'NGN');
    }

    /**
     * The price in the currency's major unit, for form inputs.
     */
    public function priceInMajorUnits(): float
    {
        return round(($this->price_amount ?? 0) / 100, 2);
    }

    public function startTimeLabel(): ?string
    {
        $time = $this->start_time ?: $this->event_date->format('H:i:s');

        return Carbon::parse($time)->format('g:i A');
    }

    public function endTimeLabel(): ?string
    {
        if (! $this->end_time) {
            return null;
        }

        return Carbon::parse($this->end_time)->format('g:i A');
    }

    public function addressLine(): ?string
    {
        $parts = array_filter([$this->address, $this->city, $this->state, $this->country]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    public function categoryLabel(): ?string
    {
        if (! $this->category) {
            return null;
        }

        return ucwords(str_replace('-', ' ', $this->category));
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
