<?php

declare(strict_types=1);

namespace App\Foundation\Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;
}
