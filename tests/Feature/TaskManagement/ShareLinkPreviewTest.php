<?php

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Services\ContentCalendarItemShareLinkService;
use App\Modules\TaskManagement\Services\DeliverableShareLinkService;
use App\Modules\TaskManagement\Support\ShareLinkPreviewMeta;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->withoutVite();
});

test('share preview og image is publicly accessible without authentication', function () {
    $response = $this->get(route('share-preview.og-image', [
        'brand' => 'VSP CRM',
        'line' => 'Demo Client · Teachers Day Post',
        'host' => 'app.vspcrm.in',
        'v' => ShareLinkPreviewMeta::sourceLogoVersion(),
    ]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $binary = $response->getContent();
    $size = getimagesizefromstring($binary);

    expect(strlen($binary))->toBeGreaterThan(1000)
        ->and(strlen($binary))->toBeLessThan(500_000)
        ->and($size[0] ?? null)->toBe(1200)
        ->and($size[1] ?? null)->toBe(630);
});

test('creative review share html includes open graph and twitter metadata', function () {
    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());
    $deliverable->load('task.project.company');

    $html = $this->get($link->publicUrl())->assertOk()->getContent();

    expect($html)
        ->toContain('property="og:title"')
        ->toContain('property="og:description"')
        ->toContain('property="og:image"')
        ->toContain('property="og:url"')
        ->toContain('name="twitter:card"')
        ->toContain('content="summary_large_image"')
        ->toContain('/share-preview/og-image.png?')
        ->toContain('v=')
        ->toContain('brand=')
        ->toContain('line=')
        ->toContain('host=')
        ->toContain($deliverable->task->project->company->name)
        ->not->toContain('/images/branding/logo.png')
        ->not->toContain('/images/branding/vsp-crm-logo.png');
});

test('content calendar share html includes open graph metadata', function () {
    $item = ContentCalendarItem::factory()->create([
        'description' => 'Teachers Day greeting post',
        'caption' => 'Happy Teachers Day',
    ]);
    $link = app(ContentCalendarItemShareLinkService::class)->getOrCreate($item, $item->createdBy);
    $item->load('company');

    $html = $this->get($link->publicUrl())->assertOk()->getContent();

    expect($html)
        ->toContain('property="og:title"')
        ->toContain('property="og:image"')
        ->toContain('/share-preview/og-image.png?')
        ->toContain('line=')
        ->toContain(e($item->company->name))
        ->toContain('Happy Teachers Day')
        ->not->toContain('/images/branding/logo.png');
});

test('legacy content calendar short urls still emit og metadata', function () {
    $item = ContentCalendarItem::factory()->create();
    $link = app(ContentCalendarItemShareLinkService::class)->getOrCreate($item, $item->createdBy);

    $html = $this->get(route('content-share.short.show', ['shortCode' => $link->short_code]))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('property="og:image"')
        ->toContain('/share-preview/og-image.png?');
});

test('og image version changes when the source v logo changes', function () {
    $logoDir = public_path('images/branding/share-preview');
    File::ensureDirectoryExists($logoDir);
    $logoPath = $logoDir.'/v-logo.png';
    $original = is_file($logoPath) ? File::get($logoPath) : null;

    // Tiny valid 1x1 PNG.
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    File::put($logoPath, $png);
    touch($logoPath, time() - 100);
    clearstatcache(true, $logoPath);

    $first = ShareLinkPreviewMeta::ogImageUrl(['brand' => 'VSP CRM', 'line' => 'Demo', 'host' => 'app.vspcrm.in']);
    expect($first)->toContain('v=');

    touch($logoPath, time());
    clearstatcache(true, $logoPath);

    $second = ShareLinkPreviewMeta::ogImageUrl(['brand' => 'VSP CRM', 'line' => 'Demo', 'host' => 'app.vspcrm.in']);

    expect($second)->not->toBe($first);

    if ($original !== null) {
        File::put($logoPath, $original);
    } else {
        File::delete($logoPath);
    }
});

test('og image query includes dynamic client line without changing share routes', function () {
    $url = ShareLinkPreviewMeta::ogImageUrl([
        'brand' => 'VSP CRM',
        'line' => 'VSP Law Associates · Teachers Day Post',
        'host' => 'app.vspcrm.in',
    ]);

    expect($url)
        ->toContain('/share-preview/og-image.png?')
        ->toContain('brand=VSP+CRM')
        ->toContain('line=')
        ->toContain('host=app.vspcrm.in')
        ->toContain('v=');
});

test('share preview meta prefers user v-logo over full branding logos', function () {
    expect(ShareLinkPreviewMeta::V_LOGO_RELATIVE_PATH)
        ->toBe('images/branding/share-preview/v-logo.png')
        ->and(ShareLinkPreviewMeta::sourceLogoAbsolutePath())
        ->not->toEndWith('images/branding/logo.png')
        ->and(ShareLinkPreviewMeta::sourceLogoAbsolutePath())
        ->not->toEndWith('images/branding/vsp-crm-logo.png');
});
