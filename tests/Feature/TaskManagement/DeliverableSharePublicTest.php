<?php

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Services\DeliverableShareLinkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
});

test('a valid token shows the deliverable to a guest', function () {
    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());
    $deliverable->load('task.project.company');

    $this->get(route('share.show', ['token' => $link->token]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/show')
            ->where('client_name', $deliverable->task->project->company->name)
            ->where('project_name', $deliverable->task->project->name)
            ->where('task_title', $deliverable->task->title)
            ->where('deliverable.title', 'Version '.$deliverable->version)
            ->where('deliverable.status', $deliverable->status->label())
            ->missing('deliverable.id')
            ->missing('auth.user.id'));
});

test('the public share url is built from the named route', function () {
    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    expect($link->publicUrl())->toEndWith('/share/'.$link->token)
        ->and(route('share.show', ['token' => $link->token], false))->toBe('/share/'.$link->token);
});

test('an invalid token returns 404', function () {
    $this->get('/share/not-a-valid-token')->assertNotFound();
});

test('a random non-existent token returns 404', function () {
    $this->get('/share/'.bin2hex(random_bytes(32)))->assertNotFound();
});

test('the public share page is reachable without authentication', function () {
    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    $this->get(route('share.show', ['token' => $link->token]))
        ->assertOk();

    $this->assertGuest();
});

test('the public share page returns only proof files for that deliverable', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $other = Deliverable::factory()->create();

    $proof = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    $deliverable->addMedia(UploadedFile::fake()->create('notes.txt', 20, 'text/plain'))->toMediaCollection('working');
    $other->addMedia(UploadedFile::fake()->image('secret-other.jpg'))->toMediaCollection('proofs');
    $deliverable->task->addMedia(UploadedFile::fake()->create('brief.pdf', 30, 'application/pdf'))->toMediaCollection('attachments');

    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());
    $expectedUrl = $link->publicFileUrl($proof->uuid);

    $this->get(route('share.show', ['token' => $link->token]))
        ->assertOk()
        ->assertDontSee('secret-other.jpg')
        ->assertDontSee('brief.pdf')
        ->assertDontSee('notes.txt')
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/show')
            ->has('files', 1)
            ->where('files.0.name', 'hero.jpg')
            ->where('files.0.mime', $proof->mime_type)
            ->where('files.0.size', $proof->size)
            ->where('files.0.url', $expectedUrl)
            ->missing('files.0.id')
            ->missing('files.0.uuid')
            ->missing('deliverable.id')
            ->missing('deliverable.notes')
            ->missing('deliverable.tm_task_id'));
});

test('a valid share token and proof media uuid returns the file', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $media = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    $this->get($link->publicFileUrl($media->uuid))
        ->assertOk()
        ->assertHeader('content-type', $media->mime_type);

    $this->assertGuest();
});

test('a valid share token cannot fetch a proof from another deliverable', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $other = Deliverable::factory()->create();
    $foreign = $other->addMedia(UploadedFile::fake()->image('secret-other.jpg'))->toMediaCollection('proofs');
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    $this->get($link->publicFileUrl($foreign->uuid))->assertNotFound();
});

test('a valid share token cannot fetch media outside the proofs collection', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    $working = $deliverable->addMedia(UploadedFile::fake()->create('notes.txt', 20, 'text/plain'))->toMediaCollection('working');
    $attachment = $deliverable->task->addMedia(UploadedFile::fake()->create('brief.pdf', 30, 'application/pdf'))->toMediaCollection('attachments');
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    $this->get($link->publicFileUrl($working->uuid))->assertNotFound();
    $this->get($link->publicFileUrl($attachment->uuid))->assertNotFound();
});

test('an invalid token cannot fetch a real media uuid', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $media = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    $bogus = bin2hex(random_bytes(32));

    $this->get('/share/'.$bogus.'/files/'.$media->uuid)->assertNotFound();
});

test('an unknown media uuid on a valid token returns 404', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    $this->get($link->publicFileUrl('00000000-0000-0000-0000-000000000000'))->assertNotFound();
});

test('the public share page does not include staff navigation', function () {
    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    $this->get(route('share.show', ['token' => $link->token]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('TaskManagement/share/show'))
        ->assertDontSee('Open Board')
        ->assertDontSee('>Employees<', false)
        ->assertDontSee('>Workload<', false);
});
