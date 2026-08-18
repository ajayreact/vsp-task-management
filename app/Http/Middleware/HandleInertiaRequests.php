<?php

namespace App\Http\Middleware;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Services\TaskManagementNotificationSoundService;
use App\Support\NotificationPresenter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                // Navigation and buttons hide on these, but every one of them is
                // enforced again server side. This list is a convenience for the
                // UI, never the authorization boundary.
                'permissions' => $user?->effectivePermissions() ?? [],
                'roles' => $user?->getRoleNames()->values() ?? [],
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'notifications' => NotificationPresenter::forUser($user),
            'notificationSound' => self::notificationSoundConfig($user),
        ]);
    }

    /**
     * @return array{enabled: bool, source: string|null, system_sound: string|null, url: string|null}|null
     */
    protected static function notificationSoundConfig(?User $user): ?array
    {
        if ($user === null || ! $user->isInternal() || ! $user->can(Ability::AccessTasks->value)) {
            return null;
        }

        return app(TaskManagementNotificationSoundService::class)->playbackConfig();
    }
}
