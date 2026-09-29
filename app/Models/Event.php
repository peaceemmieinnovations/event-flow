<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
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
        'organizer_name',
        'organizer_email',
        'organizer_phone',
        'capacity',
        'event_type',
        'status',
        'registration_deadline',
    ];

    /**
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'registration_deadline' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    public function registrations(): HasMany { return $this->hasMany(Registration::class); }

    public function registrationIsOpen(): bool
    {
        return $this->status === 'published' && (! $this->registration_deadline || now()->lte($this->registration_deadline)) && (! $this->capacity || $this->registrations()->whereIn('status', ['pending', 'confirmed'])->count() < $this->capacity);
    }
}
