<?php

declare(strict_types=1);

use App\Identity\IO\Http\Controllers\Auth\LoginController;
use App\Identity\IO\Http\Controllers\Auth\LogoutController;
use App\Identity\IO\Http\Controllers\Auth\OtpVerificationController;
use App\Identity\IO\Http\Controllers\Auth\RegistrationController;
use App\Identity\IO\Http\Controllers\HomeController;
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
