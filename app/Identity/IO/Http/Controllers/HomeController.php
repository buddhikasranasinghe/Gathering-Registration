<?php

declare(strict_types=1);

namespace App\Identity\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Foundation\Support\FunctionCountdown;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the shared home page for every signed-in user.
     */
    public function __invoke(): View
    {
        return view('home', [
            'countdown' => FunctionCountdown::fromConfig(),
        ]);
    }
}
