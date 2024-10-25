<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: [// <-- we can use an array here
            __DIR__.'/../routes/api.php',
            __DIR__.'/../routes/api_v1.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        AdminMiddleware::class;
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            // AdminMiddleware::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
