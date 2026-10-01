<?php

namespace App\Modules\RecruiterOperations\Http\Middleware;

use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Learner routes are for people who take training. Runs on the route so a
 * management-only user is refused before any request validation.
 */
class EnsureTrainingLearner
{
    public function __construct(protected RecruiterDirectory $directory) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null || ! $this->directory->isTrainingLearner($user), 403);

        return $next($request);
    }
}
