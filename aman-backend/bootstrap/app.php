<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('api')
                ->group(__DIR__.'/../routes/admin.php');

            // مسار تفويض قنوات البث: /broadcasting/auth (يتحقق من التوكن Sanctum لأي من الحراس الثلاثة)
            \Illuminate\Support\Facades\Broadcast::routes(['middleware' => ['auth:sanctum']]);
            require __DIR__.'/../routes/channels.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // تسجيل alias middleware للتحقق من صلاحيات RBAC: Route::middleware('permission:trips.view')
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
