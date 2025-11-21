<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken; // 👈 change this line
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    /**
     * Global test setup.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // In feature tests we don't care about CSRF tokens,
        // so disable the VerifyCsrfToken middleware.
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }
}