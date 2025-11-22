<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_dashboard_route_is_registered(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.dashboard'));
    }
}
