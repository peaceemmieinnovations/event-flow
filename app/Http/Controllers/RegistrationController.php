<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Services\CommunicationService;
use App\Services\PaymentProcessor;
use App\Services\PaystackGateway;
use App\Services\TicketIssuer;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly PaymentProcessor $payments,
        private readonly TicketIssuer $tickets,
        private readonly CommunicationService $communications,
    ) {}

    public function create(Event $event): View|RedirectResponse
    {
        $state = $event->registrationOpenState();

        if (! $state['open']) {
            return redirect()
                ->route('events.show', $event)
                ->with('status', $this->closedMessage($state['reason']));
        }

        return view('registrations.create', [
            'event' => $event,
            'spotsRemaining' => $event->spotsRemaining(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'organization' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Reject a duplicate before doing any work, then re-check inside the
        // lock so two simultaneous submissions cannot both take the last place.
        if ($event->registrations()->where('email', $data['email'])->exists()) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'You are already registered for this event.']);
        }

        try {
            $registration = DB::transaction(function () use ($event, $data, $request): Registration {
                /** @var Event $locked */
                $locked = Event::query()->lockForUpdate()->findOrFail($event->id);

                $state = $locked->registrationOpenState();

                if (! $state['open']) {
                    throw new HttpException(409, $this->closedMessage($state['reason']));
                }

                if ($locked->registrations()->where('email', $data['email'])->exists()) {
                    throw new HttpException(409, 'You are already registered for this event.');
                }

                return $locked->registrations()->create([
                    ...$data,
                    'user_id' => $request->user()?->id,
                    'reference' => $this->newReference(),
                    'status' => $locked->isFree()
                        ? Registration::STATUS_CONFIRMED
                        : Registration::STATUS_PENDING,
                    'registered_at' => now(),
                ]);
            });
        } catch (HttpException $exception) {
            return back()->withInput()->withErrors(['email' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['email' => 'We could not complete that registration. Please try again.']);
        }

        $request->session()->put('registration_reference', $registration->reference);

        if ($event->isFree()) {
            $this->tickets->issue($registration);
            $this->communications->resend($registration);

            return to_route('registrations.confirmation', $registration);
        }

        $payment = $this->payments->initiate($registration);

        return redirect()->away(app(PaystackGateway::class)->initialize($payment, $registration->email));
    }

    /**
     * The confirmation page is only reachable by the browser that submitted the
     * registration, so a reference alone is never enough to read a result.
     */
    public function confirmation(Request $request, Registration $registration): View|RedirectResponse
    {
        abort_unless($this->owns($request, $registration), 404);

        $registration->load([
            'event',
            'ticket',
            'payments' => fn ($query) => $query->latest('id'),
        ]);

        if ($registration->isPending()) {
            return view('registrations.confirmation', [
                'registration' => $registration,
                'payment' => $registration->latestPayment,
            ]);
        }

        return view('registrations.confirmation', [
            'registration' => $registration,
            'payment' => $registration->successfulPayment,
        ]);
    }

    public function resend(Request $request, Registration $registration): RedirectResponse
    {
        abort_unless($this->owns($request, $registration), 404);

        $registration->load('ticket');

        $this->communications->resend($registration);

        return back()->with('status', 'We have resent your confirmation email.');
    }

    /**
     * Ownership is the session reference the browser was given at submission time,
     * or the signed-in account the registration was created against.
     *
     * Both sides are compared for identity against a non-null account id: a guest
     * registration has a null user_id, and a null-on-null comparison would let any
     * visitor read anyone else's result.
     */
    private function owns(Request $request, Registration $registration): bool
    {
        if ($request->session()->get('registration_reference') === $registration->reference) {
            return true;
        }

        $userId = $request->user()?->id;

        return $userId !== null && $registration->user_id !== null && $userId === $registration->user_id;
    }

    private function newReference(): string
    {
        return 'EF-'.Str::upper(Str::random(8));
    }

    private function closedMessage(?string $reason): string
    {
        return match ($reason) {
            'full' => 'This event is fully booked.',
            'deadline_passed' => 'Registration for this event has closed.',
            'cancelled' => 'This event has been cancelled.',
            'past' => 'This event has already taken place.',
            default => 'Registration is not available for this event.',
        };
    }
}
