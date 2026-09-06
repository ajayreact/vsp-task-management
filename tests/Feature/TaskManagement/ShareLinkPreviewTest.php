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
    $response = $this->get(route('share-preview.og-image'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    expect(strlen($response->getContent()))->toBeGreaterThan(1000)
        ->and(strlen($response->getContent()))->toBeLessThan(500_000);
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
        ->toContain('/share-preview/og-image.png?v=')
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
        ->toContain('/share-preview/og-image.png?v=')
        ->toContain($item->company->name)
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
        ->toContain('/share-preview/og-image.png?v=');
});

test('og image version changes when the source v logo changes', function () {
    $logoDir = public_path('images/branding/share-preview');
    File::ensureDirectoryExists($logoDir);
    $logoPath = $logoDir.'/v-logo.png';

    // Tiny valid 1x1 PNG.
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    File::put($logoPath, $png);
    touch($logoPath, time() - 100);

    $first = ShareLinkPreviewMeta::ogImageUrl();
    expect($first)->toContain('?v=');

    touch($logoPath, time());
    clearstatcache(true, $logoPath);

    $second = ShareLinkPreviewMeta::ogImageUrl();

    expect($second)->not->toBe($first);

    File::delete($logoPath);
});

test('share preview meta prefers user v-logo over full branding logos', function () {
    expect(ShareLinkPreviewMeta::V_LOGO_RELATIVE_PATH)
        ->toBe('images/branding/share-preview/v-logo.png')
        ->and(ShareLinkPreviewMeta::sourceLogoAbsolutePath())
        ->not->toEndWith('images/branding/logo.png')
        ->and(ShareLinkPreviewMeta::sourceLogoAbsolutePath())
        ->not->toEndWith('images/branding/vsp-crm-logo.png');
});
