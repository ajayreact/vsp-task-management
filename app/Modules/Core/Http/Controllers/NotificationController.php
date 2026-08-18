<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\NotificationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-app notification inbox for internal staff. Ownership is always the
 * signed-in user — there is no cross-user read or mark-read.
 */
class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (DatabaseNotification $notification) => NotificationPresenter::one($notification));

        return Inertia::render('Core/notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Lightweight JSON feed for notification polling when websockets are unavailable.
     */
    public function feed(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json(NotificationPresenter::forUser($request->user()));
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $row = $this->owned($request, $notification);
        $row->markAsRead();

        $url = is_string($row->data['url'] ?? null) ? $row->data['url'] : null;

        if ($request->boolean('redirect') && $url) {
            return redirect($url);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    protected function owned(Request $request, string $id): DatabaseNotification
    {
        return $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();
    }
}
