<?php

namespace App\Notifications;

use App\Models\CommunicationLog;
use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Every attendee-facing EventFlow email. The notification is itself queued so a
 * slow mail server never delays a registration, payment or check-in.
 */
class EventLifecycleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Registration $registration,
        public string $type,
        public ?string $subject = null,
        public ?string $message = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->registration->event;
        $ticket = $this->registration->ticket;

        $mail = (new MailMessage)
            ->subject($this->subject ?? 'EventFlow · '.$event->title)
            ->greeting('Hello '.$this->registration->first_name.',')
            ->line($this->message ?? 'Here are your EventFlow event details.');

        $mail->line('When: '.$event->event_date->format('l, F j, Y').' at '.$event->startTimeLabel());

        if ($event->location) {
            $mail->line('Where: '.$event->location);
        }

        if ($event->addressLine()) {
            $mail->line('Address: '.$event->addressLine());
        }

        if ($ticket) {
            $mail->action('View your ticket', route('tickets.show', $ticket->token));
            $mail->line('Ticket number: '.$ticket->ticket_number);
        } else {
            $mail->action('View event', route('events.show', $event));
        }

        $mail->line('Registration reference: '.$this->registration->reference);

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'registration_id' => $this->registration->id,
            'reference' => $this->registration->reference,
            'event_id' => $this->registration->event_id,
        ];
    }

    public function type(): string
    {
        return $this->type;
    }

    public function defaultSubject(): string
    {
        return match ($this->type) {
            CommunicationLog::TYPE_REGISTRATION_CONFIRMATION => 'Registration confirmed · '.$this->registration->event->title,
            CommunicationLog::TYPE_TICKET_ISSUED => 'Your ticket is ready · '.$this->registration->event->title,
            CommunicationLog::TYPE_PAYMENT_RECEIPT => 'Payment received · '.$this->registration->event->title,
            CommunicationLog::TYPE_REMINDER_24H => 'Tomorrow · '.$this->registration->event->title,
            CommunicationLog::TYPE_REMINDER_1H => 'Starting soon · '.$this->registration->event->title,
            CommunicationLog::TYPE_EVENT_CANCELLED => 'Event cancelled · '.$this->registration->event->title,
            CommunicationLog::TYPE_EVENT_UPDATED => 'Event update · '.$this->registration->event->title,
            CommunicationLog::TYPE_REGISTRATION_STATUS => 'Registration updated · '.$this->registration->event->title,
            default => 'EventFlow · '.$this->registration->event->title,
        };
    }
}
