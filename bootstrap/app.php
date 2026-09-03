<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Core\Http\Middleware\EnsureUserIsInternal;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Services\DeliverableShareLogger;
use App\Modules\TaskManagement\Services\DeliverableShareResponder;
use App\Support\InertiaErrorPresenter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
        // Do not append AddLinkHeadersForPreloadedAssets here. It emits many Link:
        // preload headers on full Inertia document loads (e.g. /tasks/8 refresh),
        // which can exceed Nginx fastcgi_buffer_size and produce 502 Bad Gateway.
        $middleware->web(append: [
            HandleInertiaRequests::class,
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

        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, Request $request) {
            $presenter = app(InertiaErrorPresenter::class);
            $status = $response->getStatusCode();

            if (! $presenter->shouldHandle($request, $status, $exception)) {
                return $response;
            }

            return $presenter->render($request, $status, $exception);
        });
    })->create();
