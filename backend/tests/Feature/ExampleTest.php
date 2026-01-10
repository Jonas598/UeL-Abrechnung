<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_boots(): void
    {
        // Einfacher Smoke-Test: App/Container bootet.
        $this->assertTrue(app()->isBooted());
    }
}
