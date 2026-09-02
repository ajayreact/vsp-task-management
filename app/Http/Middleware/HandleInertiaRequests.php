<?php

namespace App\Http\Middleware;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\Core\Services\UserNotificationPreferenceService;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyDocument;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Services\TaskManagementNotificationSoundService;
use App\Support\NotificationPresenter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        return [
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
                'capabilities' => [
                    'logo_library' => $user ? Gate::allows('viewLogoLibrary', Company::class) : false,
                    'document_library' => $user ? Gate::allows('viewAny', CompanyDocument::class) : false,
                    'content_calendar' => $user ? Gate::allows('viewAny', ContentCalendarItem::class) : false,
                ],
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'share_url' => $request->session()->get('share_url'),
            ],
            'notifications' => NotificationPresenter::forUser($user),
            'notificationSound' => self::notificationSoundConfig($user),
            'notificationPreferences' => self::notificationPreferences($user),
        ];
    }

    /**
     * @return array{browser_notifications: bool, notification_sound: bool, in_app_notifications: bool}|null
     */
    protected static function notificationPreferences(?User $user): ?array
    {
        if ($user === null || ! $user->isInternal() || ! $user->can(Ability::AccessTasks->value)) {
            return null;
        }

        return app(UserNotificationPreferenceService::class)->forUser($user);
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
