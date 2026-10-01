<?php

namespace App\Modules\RecruiterOperations\Http\Middleware;

use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells the Training pages whether the signed-in person is a learner, so
 * learner navigation is only offered to people the server lets in.
 */
class ShareTrainingLearner
{
    public function __construct(protected RecruiterDirectory $directory) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        Inertia::share('trainingLearner', fn () => $user !== null && $this->directory->isTrainingLearner($user));

        return $next($request);
    }
}
