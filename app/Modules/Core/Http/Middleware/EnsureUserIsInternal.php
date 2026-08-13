<?php

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff screens are closed to client-portal accounts. This is the routing layer
 * of the portal isolation; the tenant scope and policies enforce it again
 * independently, so a missing middleware alone cannot leak data.
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
