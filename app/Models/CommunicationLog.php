<?php

namespace App\Models;

use Database\Factories\CommunicationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends Model
{
    /** @use HasFactory<CommunicationLogFactory> */
    use HasFactory;

    public const TYPE_REGISTRATION_CONFIRMATION = 'registration_confirmation';

    public const TYPE_TICKET_ISSUED = 'ticket_issued';

    public const TYPE_PAYMENT_RECEIPT = 'payment_receipt';

    public const TYPE_REMINDER_24H = 'reminder_24h';

    public const TYPE_REMINDER_1H = 'reminder_1h';

    public const TYPE_EVENT_UPDATED = 'event_updated';

    public const TYPE_EVENT_CANCELLED = 'event_cancelled';

    public const TYPE_REGISTRATION_STATUS = 'registration_status';

    public const TYPE_ADMIN_BULK = 'admin_bulk';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /**
     * Types that must only ever be recorded once per registration. Used to keep
     * reminder and lifecycle emails idempotent.
     *
     * @var list<string>
     */
    public const UNIQUE_PER_REGISTRATION_TYPES = [
        self::TYPE_REGISTRATION_CONFIRMATION,
        self::TYPE_TICKET_ISSUED,
        self::TYPE_PAYMENT_RECEIPT,
        self::TYPE_REMINDER_24H,
        self::TYPE_REMINDER_1H,
        self::TYPE_EVENT_CANCELLED,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'registration_id',
        'recipient_email',
        'type',
        'dedupe_key',
        'subject',
        'message',
        'channel',
        'recipient_count',
        'status',
        'sent_at',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'recipient_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Registration, $this> */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function typeLabel(): string
    {
        return ucwords(str_replace('_', ' ', (string) $this->type));
    }
}
