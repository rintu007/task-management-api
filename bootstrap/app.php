<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
		api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\LogRequests::class,
            \App\Http\Middleware\SecurityHeaders::class, 

        ]);

        $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
       ]);

       $middleware->alias([
        'throttle.api' => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
       ]);

        // Register alias for route-specific usage (optional)
        $middleware->alias([
            'log.requests' => \App\Http\Middleware\LogRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
