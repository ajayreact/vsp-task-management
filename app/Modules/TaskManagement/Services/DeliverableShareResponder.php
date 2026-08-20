<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DeliverableShareResponder
{
    public function __construct(protected DeliverableShareLogger $logger) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function respond(
        DeliverableShareException $exception,
        Request $request,
        array $context = [],
    ): Response|JsonResponse {
        $this->logger->logShareFailure($exception, $request, $context);

        if ($this->wantsJson($request)) {
            return $this->jsonError($exception->userMessage, $exception->statusCode);
        }

        return $this->errorPage($exception->title, $exception->userMessage, $exception->statusCode)
            ->toResponse($request)
            ->setStatusCode($exception->statusCode);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function respondUnexpected(
        Throwable $throwable,
        Request $request,
        array $context = [],
    ): Response|JsonResponse {
        $this->logger->logUnexpectedFailure($throwable, $request, $context);

        $exception = DeliverableShareException::serverError();

        if ($this->wantsJson($request)) {
            return $this->jsonError($exception->userMessage, $exception->statusCode);
        }

        return $this->errorPage($exception->title, $exception->userMessage, $exception->statusCode)
            ->toResponse($request)
            ->setStatusCode($exception->statusCode);
    }

    protected function errorPage(string $title, string $message, int $status): InertiaResponse
    {
        return Inertia::render('TaskManagement/share/error', [
            'brand' => config('app.name'),
            'title' => $title,
            'message' => $message,
            'status' => $status,
        ]);
    }

    protected function jsonError(string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $message,
            'status' => $status,
        ], $status);
    }

    protected function wantsJson(Request $request): bool
    {
        return ($request->expectsJson() || $request->ajax()) && ! $request->header('X-Inertia');
    }
}
