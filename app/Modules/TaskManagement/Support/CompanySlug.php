<?php

namespace App\Modules\TaskManagement\Support;

use Illuminate\Support\Str;

final class CompanySlug
{
    /**
     * First-path segments reserved by the app so client slugs cannot collide.
     *
     * @var list<string>
     */
    private const RESERVED = [
        'd',
        'c',
        'cc',
        'od',
        'docs',
        'ci',
        'cs',
        'ct',
        'share',
        'content-share',
        'content-schedule-share',
        'contract-share',
        'tasks',
        'admin',
        'api',
        'login',
        'logout',
        'register',
        'password',
        'settings',
        'dashboard',
        'notifications',
        'attendance',
        'sanctum',
        'up',
        'horizon',
        'telescope',
        'livewire',
        'vendor',
        'build',
        'storage',
        'images',
        'favicon',
        'employees',
        'departments',
        'roles',
        'clients',
        'companies',
        'projects',
        'profile',
        'email',
        'user',
        'home',
    ];

    public static function fromName(?string $name): string
    {
        $slug = Str::slug((string) $name);

        if ($slug === '') {
            return 'client';
        }

        if (in_array($slug, self::RESERVED, true)) {
            return $slug.'-client';
        }

        return $slug;
    }
}
