<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start measuring response time
        $startTime = microtime(true);

        // Process the request
        $response = $next($request);

        // Calculate response time
        $responseTime = microtime(true) - $startTime;
        $responseTimeMs = round($responseTime * 1000, 2); // Convert to milliseconds

        // Log the request details
        $this->logRequest($request, $response, $responseTimeMs);

        return $response;
    }

    /**
     * Log the request details
     */
    private function logRequest(Request $request, Response $response, float $responseTimeMs): void
    {
        $userId = auth()->id() ?? 'guest';
        $userEmail = auth()->user()?->email ?? 'unauthenticated';

        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $userId,
            'user_email' => $userEmail,
            'status_code' => $response->getStatusCode(),
            'response_time_ms' => $responseTimeMs,
            'timestamp' => now()->toISOString(),
            'content_length' => $response->headers->get('Content-Length', 0),
        ];

        // Log based on response status
        if ($response->getStatusCode() >= 500) {
            Log::error('API Request Error', $logData);
        } elseif ($response->getStatusCode() >= 400) {
            Log::warning('API Client Error', $logData);
        } else {
            Log::info('API Request', $logData);
        }

        // Add response time header for client-side monitoring
        $response->headers->set('X-Response-Time', $responseTimeMs . 'ms');
    }
}