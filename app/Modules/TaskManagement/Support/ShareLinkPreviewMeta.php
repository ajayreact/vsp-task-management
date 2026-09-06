<?php

namespace App\Modules\TaskManagement\Support;

/**
 * Server-rendered Open Graph / Twitter Card metadata for public share pages.
 *
 * WhatsApp and other crawlers do not execute React, so these tags must live in
 * the initial Blade HTML (not only Inertia <Head>).
 */
final class ShareLinkPreviewMeta
{
    /**
     * Inertia page components that should emit share-preview metadata.
     *
     * @var list<string>
     */
    public const SHARE_COMPONENTS = [
        'TaskManagement/share/show',
        'TaskManagement/share/error',
        'TaskManagement/content-share/show-item',
        'TaskManagement/content-share/show-schedule',
    ];

    /**
     * Manual V logo for share previews (user-supplied). Prefer this over favicon.
     */
    public const V_LOGO_RELATIVE_PATH = 'images/branding/share-preview/v-logo.png';

    /**
     * Fallback source used only until v-logo.png is placed (existing V mark, not full CRM wordmark).
     */
    public const FALLBACK_V_MARK_RELATIVE_PATH = 'favicon.png';

    /**
     * @param  array{component?: string, props?: array<string, mixed>, url?: string}|null  $page
     * @return array{title: string, description: string, image: string, url: string, type: string}|null
     */
    public static function fromInertiaPage(?array $page): ?array
    {
        if ($page === null) {
            return null;
        }

        $component = (string) ($page['component'] ?? '');

        if (! in_array($component, self::SHARE_COMPONENTS, true)) {
            return null;
        }

        $props = is_array($page['props'] ?? null) ? $page['props'] : [];
        $url = self::absoluteUrl((string) ($page['url'] ?? request()->getRequestUri()));

        return [
            'title' => self::titleFor($component, $props),
            'description' => self::descriptionFor($component, $props),
            'image' => self::ogImageUrl(),
            'url' => $url,
            'type' => 'website',
        ];
    }

    public static function vLogoAbsolutePath(): string
    {
        return public_path(self::V_LOGO_RELATIVE_PATH);
    }

    public static function sourceLogoAbsolutePath(): string
    {
        $preferred = self::vLogoAbsolutePath();

        if (is_file($preferred)) {
            return $preferred;
        }

        return public_path(self::FALLBACK_V_MARK_RELATIVE_PATH);
    }

    public static function sourceLogoVersion(): string
    {
        $path = self::sourceLogoAbsolutePath();

        return is_file($path) ? (string) filemtime($path) : '0';
    }

    public static function ogImageUrl(): string
    {
        return url('/share-preview/og-image.png').'?v='.self::sourceLogoVersion();
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private static function titleFor(string $component, array $props): string
    {
        $client = trim((string) ($props['client_name'] ?? ''));
        $brand = trim((string) ($props['brand'] ?? config('app.name', 'VSP CRM')));

        return match ($component) {
            'TaskManagement/share/show' => self::joinTitle(
                $client !== '' ? $client : $brand,
                (string) data_get($props, 'deliverable.title', 'Creative Review'),
            ),
            'TaskManagement/content-share/show-item' => self::joinTitle(
                $client !== '' ? $client : $brand,
                (string) data_get($props, 'item.content_type', 'Content'),
            ),
            'TaskManagement/content-share/show-schedule' => self::joinTitle(
                $client !== '' ? $client : $brand,
                'Content schedule'.(isset($props['period_label']) ? ' · '.$props['period_label'] : ''),
            ),
            'TaskManagement/share/error' => (string) ($props['title'] ?? 'Share unavailable'),
            default => $brand,
        };
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private static function descriptionFor(string $component, array $props): string
    {
        return match ($component) {
            'TaskManagement/share/show' => self::firstNonEmpty([
                trim((string) ($props['project_name'] ?? '')).' · '.trim((string) ($props['task_title'] ?? '')),
                'Creative review shared for feedback.',
            ]),
            'TaskManagement/content-share/show-item' => self::firstNonEmpty([
                (string) data_get($props, 'item.caption', ''),
                (string) data_get($props, 'item.description', ''),
                'Content calendar item shared for review.',
            ]),
            'TaskManagement/content-share/show-schedule' => self::firstNonEmpty([
                'Content schedule'.(isset($props['period_label']) ? ' · '.$props['period_label'] : ''),
                'Shared content schedule.',
            ]),
            'TaskManagement/share/error' => (string) ($props['message'] ?? 'This share link is unavailable.'),
            default => (string) config('app.name', 'VSP CRM'),
        };
    }

    private static function joinTitle(string $left, string $right): string
    {
        $left = trim($left);
        $right = trim($right);

        if ($left === '') {
            return $right !== '' ? $right : (string) config('app.name', 'VSP CRM');
        }

        if ($right === '') {
            return $left;
        }

        return $left.' · '.$right;
    }

    /**
     * @param  list<string>  $candidates
     */
    private static function firstNonEmpty(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            $value = trim(preg_replace('/\s+/', ' ', $candidate) ?? '');
            if ($value !== '' && $value !== '·') {
                return mb_substr($value, 0, 200);
            }
        }

        return (string) config('app.name', 'VSP CRM');
    }

    private static function absoluteUrl(string $pathOrUrl): string
    {
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return $pathOrUrl;
        }

        return url($pathOrUrl);
    }
}
