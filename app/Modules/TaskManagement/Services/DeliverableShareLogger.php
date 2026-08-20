<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverableShareLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function logShareFailure(DeliverableShareException $exception, Request $request, array $context = []): void
    {
        Log::warning('deliverable_share.access_denied', $this->baseContext($request, [
            'reason' => $exception->reason,
            'status' => $exception->statusCode,
            ...$exception->context,
            ...$context,
        ]));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logUnexpectedFailure(Throwable $throwable, Request $request, array $context = []): void
    {
        Log::error('deliverable_share.unexpected_failure', $this->baseContext($request, [
            ...$context,
            'exception_class' => $throwable::class,
            'exception_message' => $throwable->getMessage(),
            'exception_trace' => $throwable->getTraceAsString(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function baseContext(Request $request, array $context = []): array
    {
        return array_filter([
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            ...$context,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
