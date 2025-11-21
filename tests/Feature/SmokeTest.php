<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_basic_math_works(): void
    {
        $this->assertSame(4, 2 + 2);
    }
}