<?php

use App\Constants\ThemeSettings;
use App\Http\Middleware\EnablePreview;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureCommunityMember;
use App\Http\Responses\LoginResponse;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            EnsureCommunityMember::ALIAS => EnsureCommunityMember::class,
            EnsureAccountIsActive::ALIAS => EnsureAccountIsActive::class,
        ]);

        // The theme cookie stays plain so the inline head script can read it before the first paint.
        $middleware->encryptCookies(except: [ThemeSettings::COOKIE]);

        // Signed preview links show a draft on its public page; global so it runs before any page service is built.
        $middleware->prepend(EnablePreview::class);

        // Deactivated accounts are signed out on their next request, whatever the page.
        $middleware->web(append: [EnsureAccountIsActive::class]);

        // Signed-in users who open a guest page (sign in, reset password) go where they belong.
        $middleware->redirectUsersTo(fn (Request $request): string => LoginResponse::destinationFor($request->user(), app(AuthenticationService::class)));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
