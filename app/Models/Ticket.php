<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'registration_id',
        'event_id',
        'ticket_number',
        'token',
        'ticket_type',
        'status',
        'checked_in_at',
        'checked_in_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * The secure token is never serialised into arrays or JSON payloads.
     *
     * @var list<string>
     */
    protected $hidden = [
        'token',
    ];

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

    /** @return BelongsTo<User, $this> */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function isValid(): bool
    {
        return $this->status === Event::TICKET_STATUS_VALID;
    }

    public function isCheckedIn(): bool
    {
        return $this->status === Event::TICKET_STATUS_CHECKED_IN;
    }

    public function isCancelled(): bool
    {
        return $this->status === Event::TICKET_STATUS_CANCELLED;
    }

    /**
     * The QR payload. Deliberately opaque: it carries only the secure token so
     * nothing about the attendee, event or price ever reaches a scanner.
     */
    public function qrPayload(): string
    {
        return (string) $this->token;
    }

    /**
     * Grouped ticket number for readability, e.g. EF-A1B2C3-D4E5F6.
     */
    public function displayNumber(): string
    {
        return Str::of($this->ticket_number)
            ->replaceFirst('EF-', 'EF-')
            ->upper()
            ->toString();
    }
}
