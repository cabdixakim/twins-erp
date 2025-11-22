<?php

namespace Tests;

use App\Http\Middleware\VerifyCsrfToken;
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

        // 1) In feature tests we don't care about CSRF tokens,
        //    so disable the VerifyCsrfToken middleware.
        $this->withoutMiddleware(VerifyCsrfToken::class);

        // 2) SAFETY FUSE: never allow tests to touch a non-testing DB.
        $connection = config('database.default');
        $dbName     = config("database.connections.{$connection}.database");

        if ($dbName !== 'twins_testing') {
            throw new \RuntimeException(
                '❌ Tests are only allowed against the "twins_testing" database. '
                . 'Current DB: ' . ($dbName ?: '(null)')
            );
        }
    }
}