<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Core\Http\Middleware\EnsureUserIsInternal;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Services\DeliverableShareLogger;
use App\Modules\TaskManagement\Services\DeliverableShareResponder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'internal' => EnsureUserIsInternal::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (DeliverableShareException $exception, Request $request) {
            if (! $request->is('d/*', 'share/*')) {
                return null;
            }

            return app(DeliverableShareResponder::class)->respond($exception, $request);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('d/*', 'share/*')) {
                return null;
            }

            return app(DeliverableShareResponder::class)->respond(
                DeliverableShareException::notFound(),
                $request,
            );
        });

        $exceptions->render(function (\Throwable $throwable, Request $request) {
            if (! $request->is('d/*', 'share/*')) {
                return null;
            }

            return app(DeliverableShareResponder::class)->respondUnexpected($throwable, $request);
        });
    })->create();
