<?php

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\DeliverableShareLink;
use App\Modules\TaskManagement\Services\DeliverableShareLinkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

beforeEach(function () {
    $this->withoutVite();
});

function shareLinkForDeliverable(?Deliverable $deliverable = null): DeliverableShareLink
{
    $deliverable ??= Deliverable::factory()->create();

    return app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());
}

test('an unknown short code shows a clean link not found page', function () {
    $this->get('/d/zzzzzzzz')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('title', 'Link Not Found')
            ->where('message', 'The link you followed could not be found.')
            ->where('status', 404));
});

test('a malformed short code shows a clean link not found page', function () {
    $this->get('/d/not-valid')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('title', 'Link Not Found')
            ->where('message', 'The link you followed could not be found.')
            ->where('status', 404));
});

test('an unknown short code returns consistent json for ajax requests', function () {
    $this->getJson('/d/zzzzzzzz')
        ->assertNotFound()
        ->assertJson([
            'error' => true,
            'message' => 'The link you followed could not be found.',
            'status' => 404,
        ]);
});

test('an expired share link shows the expired message', function () {
    $link = shareLinkForDeliverable();
    $link->update(['expires_at' => now()->subMinute()]);

    $this->get(route('share.short.show', ['shortCode' => $link->short_code]))
        ->assertStatus(410)
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('title', 'Link Expired')
            ->where('message', 'This shared link has expired.')
            ->where('status', 410));
});

test('an expired share link returns consistent json for ajax requests', function () {
    $link = shareLinkForDeliverable();
    $link->update(['expires_at' => now()->subMinute()]);

    $this->getJson(route('share.short.show', ['shortCode' => $link->short_code]))
        ->assertStatus(410)
        ->assertJson([
            'error' => true,
            'message' => 'This shared link has expired.',
            'status' => 410,
        ]);
});

test('a revoked share link shows the unavailable message', function () {
    $link = shareLinkForDeliverable();
    $link->update(['revoked_at' => now()]);

    $this->get(route('share.short.show', ['shortCode' => $link->short_code]))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('title', 'Link Unavailable')
            ->where('message', 'This shared link is no longer available.')
            ->where('status', 403));
});

test('a deleted share link shows link not found without exposing internal details', function () {
    $link = shareLinkForDeliverable();
    $shortCode = $link->short_code;
    $token = $link->token;

    $link->delete();

    $this->get(route('share.short.show', ['shortCode' => $shortCode]))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('title', 'Link Not Found'));

    $this->get(route('share.show', ['token' => $token]))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('TaskManagement/share/error'));
});

test('a share link with a missing deliverable shows link not found', function () {
    $link = shareLinkForDeliverable();
    $shortCode = $link->short_code;
    $deliverableId = $link->tm_deliverable_id;

    if (DB::getDriverName() === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = OFF');
    }

    Deliverable::query()->whereKey($deliverableId)->delete();

    if (DB::getDriverName() === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = ON');
    }

    $this->get(route('share.short.show', ['shortCode' => $shortCode]))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('title', 'Link Not Found')
            ->where('message', 'The link you followed could not be found.'));
});

test('unauthorized proof file access returns access denied', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $other = Deliverable::factory()->create();
    $foreign = $other->addMedia(UploadedFile::fake()->image('secret.jpg'))->toMediaCollection('proofs');
    $link = shareLinkForDeliverable($deliverable);

    $this->get($link->publicFileUrl($foreign->uuid))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('title', 'Access Denied')
            ->where('message', 'You do not have permission to access this resource.')
            ->where('status', 403));
});

test('unauthorized proof file access returns consistent json for ajax requests', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $other = Deliverable::factory()->create();
    $foreign = $other->addMedia(UploadedFile::fake()->image('secret.jpg'))->toMediaCollection('proofs');
    $link = shareLinkForDeliverable($deliverable);

    $this->getJson($link->publicFileUrl($foreign->uuid))
        ->assertForbidden()
        ->assertJson([
            'error' => true,
            'message' => 'You do not have permission to access this resource.',
            'status' => 403,
        ]);
});

test('unexpected server failures show a friendly error page and log internally', function () {
    Log::spy();

    $this->mock(DeliverableShareLinkService::class, function ($mock) {
        $mock->shouldReceive('resolveByShortCode')
            ->andThrow(new RuntimeException('Simulated database failure'));
    });

    $this->get('/d/AbCd1234')
        ->assertStatus(500)
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('title', 'Something Went Wrong')
            ->where('message', 'Something went wrong while loading this page. Please try again later.')
            ->where('status', 500));

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'deliverable_share.unexpected_failure'
                && ($context['exception_message'] ?? '') === 'Simulated database failure';
        });
});

test('unexpected server failures return consistent json for ajax requests', function () {
    $this->mock(DeliverableShareLinkService::class, function ($mock) {
        $mock->shouldReceive('resolveByShortCode')
            ->andThrow(new RuntimeException('Simulated database failure'));
    });

    $this->getJson('/d/AbCd1234')
        ->assertStatus(500)
        ->assertJson([
            'error' => true,
            'message' => 'Something went wrong while loading this page. Please try again later.',
            'status' => 500,
        ]);
});

test('short code generation retries after a unique constraint collision', function () {
    $firstDeliverable = Deliverable::factory()->create();
    $secondDeliverable = Deliverable::factory()->create();
    $user = User::factory()->create();

    $firstLink = app(DeliverableShareLinkService::class)->getOrCreate($firstDeliverable, $user);

    $this->partialMock(DeliverableShareLinkService::class, function (MockInterface $mock) use ($firstLink) {
        $mock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('randomShortCode')
            ->andReturn($firstLink->short_code, 'Unique9Z');
    });

    $secondLink = app(DeliverableShareLinkService::class)->getOrCreate($secondDeliverable, $user);

    expect($secondLink->short_code)->toBe('Unique9Z')
        ->and($secondLink->short_code)->not->toBe($firstLink->short_code);
});

test('share access failures are logged with structured context', function () {
    Log::spy();

    $this->get('/d/zzzzzzzz')->assertNotFound();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'deliverable_share.access_denied'
                && ($context['reason'] ?? null) === 'not_found'
                && ($context['short_code'] ?? null) === 'zzzzzzzz';
        });
});
