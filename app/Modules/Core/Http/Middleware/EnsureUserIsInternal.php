<?php

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff screens require an internal account.
 */
class EnsureUserIsInternal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null || ! $user->isInternal(), 403);

        return $next($request);
    }
}
