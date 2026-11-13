<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Instalasi pertama (cPanel tanpa terminal): buat .env dari .env.example lengkap dengan APP_KEY acak.
$root = dirname(__DIR__);
if (! file_exists($root.'/.env') && file_exists($root.'/.env.example') && is_writable($root)) {
    $env = file_get_contents($root.'/.env.example');
    $key = 'base64:'.base64_encode(random_bytes(32));
    $env = preg_match('/^APP_KEY=.*$/m', $env) ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $env) : $env."\nAPP_KEY=".$key."\n";
    file_put_contents($root.'/.env', $env);
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\EnsureInstalled::class, \App\Http\Middleware\RoleGate::class]);
        $middleware->redirectGuestsTo('/');
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport(\App\Support\WmsException::class);
        $exceptions->render(function (\App\Support\WmsException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['wms' => $e->getMessage()]);
        });
    })->create();
