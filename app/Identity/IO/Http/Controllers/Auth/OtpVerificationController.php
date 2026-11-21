<?php

declare(strict_types=1);

namespace App\Identity\IO\Http\Controllers\Auth;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Exceptions\AccountNotFoundException;
use App\Identity\Exceptions\OtpCooldownException;
use App\Identity\Exceptions\OtpException;
use App\Identity\IO\Http\OtpSession;
use App\Identity\IO\Http\Requests\Auth\VerifyOtpFormRequest;
use App\Identity\Services\OtpCooldown;
use App\Identity\UseCases\Requests\ResendOtpRequest;
use App\Identity\UseCases\Requests\VerifyOtpRequest;
use App\Identity\UseCases\ResendOtpUseCase;
use App\Identity\UseCases\VerifyOtpUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    public function __construct(
        private readonly VerifyOtpUseCase $verify,
        private readonly ResendOtpUseCase $resend,
        private readonly OtpCooldown $cooldown,
    ) {}

    /**
     * Show the passcode form for the pending email address.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $email = OtpSession::email($request->session());

        if ($email === null) {
            return $this->missingPendingEmail();
        }

        return view('auth.verify', [
            'email' => $email,
            'resendAfter' => $this->cooldown->secondsUntilResend($email),
        ]);
    }

    /**
     * Verify the submitted passcode and sign the user in.
     */
    public function store(VerifyOtpFormRequest $request): RedirectResponse
    {
        $email = OtpSession::email($request->session());

        if ($email === null) {
            return $this->missingPendingEmail();
        }

        try {
            $user = $this->verify->handle(
                new VerifyOtpRequest($email, $request->string('code')->toString()),
            );
        } catch (OtpException $exception) {
            return $this->invalidCode($exception);
        } catch (AccountNotFoundException $exception) {
            return $this->accountMissing($request, $email, $exception);
        }

        $this->signIn($request, $user);

        return redirect()->intended(route('home'));
    }

    /**
     * Send a replacement passcode for the pending email address.
     */
    public function resend(Request $request): RedirectResponse
    {
        $email = OtpSession::email($request->session());

        if ($email === null) {
            return $this->missingPendingEmail();
        }

        try {
            $this->resend->handle(new ResendOtpRequest($email, $request->ip()));
        } catch (OtpCooldownException $exception) {
            return $this->cooldownActive($exception);
        }

        return back()->with('status', 'If an account exists for that email, we have sent a new code.');
    }

    private function signIn(Request $request, User $user): void
    {
        Auth::login($user);

        $request->session()->regenerate();
        OtpSession::forget($request->session());
    }

    private function invalidCode(OtpException $exception): RedirectResponse
    {
        return back()->withErrors(['code' => $exception->getMessage()]);
    }

    private function accountMissing(
        Request $request,
        string $email,
        AccountNotFoundException $exception,
    ): RedirectResponse {
        OtpSession::forget($request->session());

        return redirect()
            ->route('register')
            ->withErrors(['email' => $exception->getMessage()])
            ->withInput(['email' => $email]);
    }

    private function cooldownActive(OtpCooldownException $exception): RedirectResponse
    {
        return back()->withErrors(['code' => $exception->getMessage()]);
    }

    private function missingPendingEmail(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withErrors(['email' => 'Enter your email address to start signing in.']);
    }
}
