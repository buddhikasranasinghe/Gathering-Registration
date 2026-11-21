<?php

declare(strict_types=1);

namespace App\Identity\IO\Http\Controllers\Auth;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Exceptions\AccountAlreadyExistsException;
use App\Identity\Exceptions\OtpCooldownException;
use App\Identity\IO\Http\OtpSession;
use App\Identity\IO\Http\Requests\Auth\RegisterFormRequest;
use App\Identity\UseCases\RegisterUseCase;
use App\Identity\UseCases\Requests\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(private readonly RegisterUseCase $registration) {}

    /**
     * Show the registration form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Start the passwordless registration flow.
     */
    public function store(RegisterFormRequest $request): RedirectResponse
    {
        $registerRequest = $this->registerRequest($request);

        try {
            $this->registration->handle($registerRequest);
        } catch (AccountAlreadyExistsException $exception) {
            return $this->accountAlreadyExists($registerRequest, $exception);
        } catch (OtpCooldownException $exception) {
            return $this->cooldownActive($exception);
        }

        OtpSession::put($request->session(), $registerRequest->email);

        return redirect()
            ->route('verify')
            ->with('status', 'We sent a verification code to your email.');
    }

    private function registerRequest(RegisterFormRequest $request): RegisterRequest
    {
        return new RegisterRequest(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            ipAddress: $request->ip(),
        );
    }

    private function accountAlreadyExists(
        RegisterRequest $request,
        AccountAlreadyExistsException $exception,
    ): RedirectResponse {
        return redirect()
            ->route('login')
            ->withInput(['email' => $request->email])
            ->withErrors(['email' => $exception->getMessage()]);
    }

    private function cooldownActive(OtpCooldownException $exception): RedirectResponse
    {
        return back()
            ->withInput()
            ->withErrors(['email' => $exception->getMessage()]);
    }
}
