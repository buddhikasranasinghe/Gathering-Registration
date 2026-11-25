<?php

declare(strict_types=1);

namespace App\Identity\IO\Http\Controllers\Auth;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\IO\Http\OtpSession;
use App\Identity\IO\Http\Requests\Auth\LoginFormRequest;
use App\Identity\UseCases\LoginUseCase;
use App\Identity\UseCases\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly LoginUseCase $login) {}

    /**
     * Show the passwordless sign-in form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Start a passwordless sign-in challenge.
     */
    public function store(LoginFormRequest $request): RedirectResponse
    {
        $loginRequest = $this->loginRequest($request);

        $this->login->handle($loginRequest);

        OtpSession::put($request->session(), $loginRequest->email);

        return redirect()->route('verify');
    }

    private function loginRequest(LoginFormRequest $request): LoginRequest
    {
        return new LoginRequest(
            email: $request->string('email')->toString(),
            ipAddress: $request->ip(),
        );
    }
}
