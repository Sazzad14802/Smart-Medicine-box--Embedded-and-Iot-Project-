<?php

use App\Exceptions\Esp32ConnectionException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Esp32Service already catches this internally on every method and returns a
        // { success: false, error } array, so controllers normally never see it. This is a
        // safety net so that if it ever does escape uncaught, the app redirects back with a
        // flashed error (or returns JSON for AJAX requests) instead of a 500 error page.
        $exceptions->render(function (Esp32ConnectionException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 503);
            }

            return back()->with('error', $e->getMessage());
        });
    })->create();
