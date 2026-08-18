<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CommandCenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff home. Aggregation lives in CommandCenter so Core never imports Crm
 * or Task Management directly.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, CommandCenter $center): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        return Inertia::render('Dashboard', [
            'snapshot' => $center->snapshot($user),
        ]);
    }
}
