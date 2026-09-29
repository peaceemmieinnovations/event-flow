<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\EventManagementController;
use App\Http\Controllers\Admin\RegistrationManagementController;
use App\Http\Controllers\RegistrationController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/events/{event}/register', [RegistrationController::class, 'create'])->name('registrations.create');
Route::post('/events/{event}/register', [RegistrationController::class, 'store'])->name('registrations.store');
Route::get('/registrations/{registration}/confirmation', [RegistrationController::class, 'confirmation'])->name('registrations.confirmation');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [AuthController::class, 'create'])->name('register');
    Route::post('/register', [AuthController::class, 'store']);
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::resource('events', EventManagementController::class)->except('show');
    Route::patch('events/{event}/status', [EventManagementController::class, 'setStatus'])->name('events.status');
    Route::get('registrations', [RegistrationManagementController::class, 'index'])->name('registrations.index');
    Route::get('registrations/{registration}', [RegistrationManagementController::class, 'show'])->name('registrations.show');
    Route::patch('registrations/{registration}/status', [RegistrationManagementController::class, 'updateStatus'])->name('registrations.status');
});
