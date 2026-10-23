<?php

use App\Http\Middleware\EnsureOrganizationHasAccess;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureUserHasOrganization;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveCurrentOrganization;
use App\Http\Middleware\ResolveOrganizationFromRoute;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance']);

        $middleware->validateCsrfTokens(except: ['stripe/*']);

        $middleware->alias([
            'organization' => ResolveCurrentOrganization::class,
            'has-organization' => EnsureUserHasOrganization::class,
            'has-access' => EnsureOrganizationHasAccess::class,
            'platform-admin' => EnsurePlatformAdmin::class,
            'public-organization' => ResolveOrganizationFromRoute::class,
        ]);

        // Route model binding queries tenant-scoped models, so the current
        // organization has to be known before bindings are resolved.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolveCurrentOrganization::class,
        );
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolveOrganizationFromRoute::class,
        );

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
