<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InertiaErrorPresenter
{
    /**
     * @var list<int>
     */
    private const HANDLED_STATUSES = [403, 404, 419, 500, 503];

    public function shouldHandle(Request $request, int $status, ?Throwable $exception = null): bool
    {
        if ($request->is('d/*', 'share/*')) {
            return false;
        }

        if (! in_array($status, self::HANDLED_STATUSES, true)) {
            return false;
        }

        if ($status === 500 && app()->hasDebugModeEnabled() && app()->environment('local')) {
            return false;
        }

        return $this->wantsInertiaResponse($request);
    }

    public function render(Request $request, int $status, ?Throwable $exception = null): Response|JsonResponse
    {
        if ($status === 500 && $exception !== null) {
            $this->logServerError($exception, $request);
        }

        $definition = self::definition($status);

        if ($this->wantsJson($request)) {
            return response()->json([
                'error' => true,
                'status' => $status,
                'title' => $definition['title'],
                'message' => $definition['message'],
            ], $status);
        }

        return $this->page($definition, $status)
            ->toResponse($request)
            ->setStatusCode($status);
    }

    /**
     * @param  array{title: string, message: string, hint: string, action: string}  $definition
     */
    protected function page(array $definition, int $status): InertiaResponse
    {
        return Inertia::render('Error', [
            'status' => $status,
            'title' => $definition['title'],
            'message' => $definition['message'],
            'hint' => $definition['hint'],
            'action' => $definition['action'],
        ]);
    }

    protected function wantsInertiaResponse(Request $request): bool
    {
        if ($request->header('X-Inertia')) {
            return true;
        }

        if ($this->wantsJson($request)) {
            return true;
        }

        return $request->isMethod('GET') || $request->isMethod('HEAD');
    }

    protected function wantsJson(Request $request): bool
    {
        return ($request->expectsJson() || $request->ajax()) && ! $request->header('X-Inertia');
    }

    protected function logServerError(Throwable $exception, Request $request): void
    {
        Log::error('application.unexpected_failure', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'exception_trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * @return array{title: string, message: string, hint: string, action: string}
     */
    public static function definition(int $status): array
    {
        return match ($status) {
            403 => [
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view this page.',
                'hint' => 'If you think you should have access, contact your administrator.',
                'action' => 'dashboard',
            ],
            404 => [
                'title' => 'Page Not Found',
                'message' => 'The page you requested could not be found.',
                'hint' => 'It may have been moved, deleted, or the link may be incorrect.',
                'action' => 'dashboard',
            ],
            419 => [
                'title' => 'Page Expired',
                'message' => 'Your session expired or the page is out of date.',
                'hint' => 'Refresh the page and try again. Unsaved changes may have been lost.',
                'action' => 'refresh',
            ],
            503 => [
                'title' => 'Service Unavailable',
                'message' => 'The application is temporarily unavailable.',
                'hint' => 'Please wait a moment and try again.',
                'action' => 'refresh',
            ],
            default => [
                'title' => 'Something Went Wrong',
                'message' => 'Something went wrong while loading this page.',
                'hint' => 'Please try again. If the problem continues, contact support.',
                'action' => 'dashboard',
            ],
        };
    }
}
