<?php

declare(strict_types=1);

use App\Participation\IO\Console\Commands\RegenerateTicketPdfsCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        RegenerateTicketPdfsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson() || ! $request->is('login', 'register', 'verify', 'verify/resend')) {
                return null;
            }

            $retryAfter = max(1, (int) ($e->getHeaders()['Retry-After'] ?? 60));
            $errorKey = $request->routeIs('verify.store', 'verify.resend') ? 'code' : 'email';

            return back()
                ->withInput($request->only('name', 'email'))
                ->withErrors([
                    $errorKey => "Too many attempts. Please try again in {$retryAfter} seconds.",
                ]);
        });
    })->create();
