<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class RequestLoggingMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_logging_middleware_logs_requests(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'API Request');
            });

        $user = $this->actingAsUser();
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200);
    }
}