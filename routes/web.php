<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CommunicationManagementController;
use App\Http\Controllers\Admin\EventManagementController;
use App\Http\Controllers\Admin\PaymentManagementController;
use App\Http\Controllers\Admin\RegistrationManagementController;
use App\Http\Controllers\Admin\TicketManagementController;
use App\Http\Controllers\AttendeeTicketController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HostEventController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public experience
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/events/{event}/register', [RegistrationController::class, 'create'])->name('registrations.create');
Route::post('/events/{event}/register', [RegistrationController::class, 'store'])->name('registrations.store');
Route::get('/registrations/{registration}/confirmation', [RegistrationController::class, 'confirmation'])->name('registrations.confirmation');
Route::post('/registrations/{registration}/resend', [RegistrationController::class, 'resend'])->name('registrations.resend');

Route::get('/tickets/{token}', [TicketController::class, 'show'])->name('tickets.show');
Route::get('/tickets/{token}/print', [TicketController::class, 'print'])->name('tickets.print');

Route::get('/payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');
Route::post('/payments/{payment}/retry', [PaymentController::class, 'retry'])->name('payments.retry');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [AuthController::class, 'create'])->name('register');
    Route::post('/register', [AuthController::class, 'store']);
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Attendee area
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/my/tickets', [AttendeeTicketController::class, 'index'])->name('attendee.tickets');
    Route::get('/my/tickets/{token}', [AttendeeTicketController::class, 'show'])->name('attendee.tickets.show');
    Route::get('/host/events/create', [HostEventController::class, 'create'])->name('host.events.create');
    Route::post('/host/events', [HostEventController::class, 'store'])->name('host.events.store');
});

/*
|--------------------------------------------------------------------------
| Admin area
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,organizer'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [AnalyticsController::class, 'index'])->name('dashboard');

        Route::resource('events', EventManagementController::class)->except('show');
        Route::patch('events/{event}/status', [EventManagementController::class, 'setStatus'])->name('events.status');
        Route::get('events/{event}/analytics', [AnalyticsController::class, 'event'])->name('events.analytics');

        Route::get('registrations', [RegistrationManagementController::class, 'index'])->name('registrations.index');
        Route::get('registrations/{registration}', [RegistrationManagementController::class, 'show'])->name('registrations.show');
        Route::patch('registrations/{registration}/status', [RegistrationManagementController::class, 'updateStatus'])->name('registrations.status');

        Route::get('tickets', [TicketManagementController::class, 'index'])->name('tickets.index');
        Route::get('check-in', [TicketManagementController::class, 'checkinIndex'])->name('tickets.checkin.index');

        Route::get('events/{event}/check-in', [TicketManagementController::class, 'checkin'])->name('tickets.checkin');
        Route::post('events/{event}/check-in', [TicketManagementController::class, 'process'])->name('tickets.checkin.process');
        Route::get('events/{event}/check-in/verify', [TicketManagementController::class, 'verify'])->name('tickets.checkin.verify');

        Route::get('payments', [PaymentManagementController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PaymentManagementController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/reverify', [PaymentManagementController::class, 'reverify'])->name('payments.reverify');

        Route::get('communications', [CommunicationManagementController::class, 'index'])->name('communications.index');
        Route::get('communications/compose', [CommunicationManagementController::class, 'create'])->name('communications.compose');
        Route::post('communications', [CommunicationManagementController::class, 'store'])->name('communications.store');

        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    });
