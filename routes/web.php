<?php

declare(strict_types=1);

use App\Identity\IO\Http\Controllers\Auth\LoginController;
use App\Identity\IO\Http\Controllers\Auth\LogoutController;
use App\Identity\IO\Http\Controllers\Auth\OtpVerificationController;
use App\Identity\IO\Http\Controllers\Auth\RegistrationController;
use App\Identity\IO\Http\Controllers\HomeController;
use App\Identity\IO\Http\Controllers\UserController;
use App\Identity\IO\Http\Controllers\UserRoleController;
use App\Participation\IO\Http\Controllers\BatchController;
use App\Participation\IO\Http\Controllers\DownloadIssuedTicketController;
use App\Participation\IO\Http\Controllers\DownloadPaymentSlipController;
use App\Participation\IO\Http\Controllers\IssueTicketController;
use App\Participation\IO\Http\Controllers\ParticipantController;
use App\Participation\IO\Http\Controllers\ParticipantImportController;
use App\Participation\IO\Http\Controllers\RecordPaymentController;
use App\Participation\IO\Http\Controllers\ShowParticipantController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:otp-request')
        ->name('login.store');

    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])
        ->middleware('throttle:otp-request')
        ->name('register.store');

    Route::get('/verify', [OtpVerificationController::class, 'show'])->name('verify');
    Route::post('/verify', [OtpVerificationController::class, 'store'])
        ->middleware('throttle:otp-verify')
        ->name('verify.store');
    Route::post('/verify/resend', [OtpVerificationController::class, 'resend'])
        ->middleware('throttle:otp-request')
        ->name('verify.resend');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', HomeController::class)->name('home');
    Route::post('/logout', LogoutController::class)->name('logout');
});

Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/participants', ParticipantController::class)->name('participants.index');
    Route::get('/participants/{participant}', ShowParticipantController::class)->name('participants.show');
    Route::post('/participants/{participant}/payments', RecordPaymentController::class)->name('participants.payments.store');
    Route::post('/participants/{participant}/tickets', IssueTicketController::class)->name('participants.tickets.store');
    Route::get('/participants/{participant}/payments/{payment}/slip', DownloadPaymentSlipController::class)->name('participants.payments.slip');
    Route::get('/participants/{participant}/tickets/{ticket}/download', DownloadIssuedTicketController::class)->name('participants.tickets.download');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('batches', BatchController::class)->except('show', 'destroy');
    Route::get('/users', UserController::class)->name('users.index');
    Route::patch('/users/{user}/role', [UserRoleController::class, 'update'])->name('users.role.update');
    Route::post('/participants/import', [ParticipantImportController::class, 'store'])
        ->name('participants.import');
});
