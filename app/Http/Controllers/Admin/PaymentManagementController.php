<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payment;
use App\Services\PaymentProcessor;
use App\Services\PaystackGateway;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentManagementController extends Controller
{
    public function __construct(
        private readonly PaymentProcessor $processor,
        private readonly PaystackGateway $gateway,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $payments = Payment::query()
            ->with(['event:id,title,event_date', 'registration:id,event_id,first_name,last_name,email,reference'])
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('gateway_transaction_id', 'like', '%'.$search.'%')
                        ->orWhereHas('registration', fn ($q) => $q->where('email', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['event'], fn (Builder $query, int $event) => $query->where('event_id', $event))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['gateway'], fn (Builder $query, string $gateway) => $query->where('gateway', $gateway))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'events' => Event::query()->orderBy('event_date')->get(['id', 'title']),
            'filters' => $filters,
            'statuses' => Payment::STATUSES,
            'summary' => $this->summary(),
        ]);
    }

    public function show(Payment $payment): View
    {
        return view('admin.payments.show', [
            'payment' => $payment->load(['event', 'registration.ticket', 'registration.communicationLogs']),
        ]);
    }

    /**
     * Let staff re-query the gateway for a payment that never resolved. The
     * settlement path is idempotent, so a repeat is always safe.
     */
    public function reverify(Payment $payment): RedirectResponse
    {
        if ($payment->isSuccessful()) {
            return back()->with('status', 'This payment is already confirmed.');
        }

        $result = $this->processor->verify($payment);

        return match ($result['reason']) {
            null => back()->with('status', "Payment {$payment->reference} is now confirmed."),
            'mismatch' => back()->with('error', "Gateway data for {$payment->reference} did not match the stored amount."),
            default => back()->with('error', 'The gateway could not be reached. Please try again shortly.'),
        };
    }

    /**
     * @return array{search: ?string, event: ?int, status: ?string, gateway: ?string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->filled('search') ? trim((string) $request->string('search')) : null,
            'event' => $request->integer('event') ?: null,
            'status' => in_array((string) $request->string('status'), Payment::STATUSES, true) ? (string) $request->string('status') : null,
            'gateway' => $request->filled('gateway') ? (string) $request->string('gateway') : null,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function summary(): array
    {
        $successful = Payment::query()->where('status', Payment::STATUS_SUCCESSFUL);
        $pending = Payment::query()->where('status', Payment::STATUS_PENDING);
        $problem = Payment::query()->whereIn('status', [Payment::STATUS_FAILED, Payment::STATUS_ABANDONED]);

        return [
            'successful_count' => (clone $successful)->count(),
            'successful_total' => (int) (clone $successful)->sum('amount'),
            'pending_count' => (clone $pending)->count(),
            'pending_total' => (int) (clone $pending)->sum('amount'),
            'problem_count' => (clone $problem)->count(),
            'problem_total' => (int) (clone $problem)->sum('amount'),
            'webhook_configured' => $this->gateway->hasWebhookSecret() ? 1 : 0,
        ];
    }
}
