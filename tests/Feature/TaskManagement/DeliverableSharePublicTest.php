<?php

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\DeliverableStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
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

test('the public share url uses the short code route', function () {
    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    expect($link->publicUrl())->toEndWith('/d/'.$link->short_code)
        ->and(route('share.short.show', ['shortCode' => $link->short_code], false))->toBe('/d/'.$link->short_code);
});

test('a valid short code shows the deliverable to a guest', function () {
    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());
    $deliverable->load('task.project.company');

    $this->get(route('share.short.show', ['shortCode' => $link->short_code]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/show')
            ->where('client_name', $deliverable->task->project->company->name)
            ->where('approve_url', $link->publicApproveUrl())
            ->where('request_changes_url', $link->publicRequestChangesUrl()));
});

test('legacy long token urls continue to work', function () {
    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    $this->get(route('share.show', ['token' => $link->token]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/show')
            ->where('approve_url', route('share.approve', ['token' => $link->token]))
            ->where('request_changes_url', route('share.request-changes', ['token' => $link->token])));
});

test('an invalid short code returns a link not found page', function () {
    $this->get('/d/not-valid')->assertNotFound();
    $this->get('/d/zzzzzzzz')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('TaskManagement/share/error'));
});

test('a revoked share link shows the unavailable message for both short and legacy urls', function () {
    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());
    $link->update(['revoked_at' => now()]);

    $this->get(route('share.short.show', ['shortCode' => $link->short_code]))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('message', 'This shared link is no longer available.'));

    $this->get(route('share.show', ['token' => $link->token]))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('message', 'This shared link is no longer available.'));
});

test('a deleted share link returns link not found for both short and legacy urls', function () {
    $deliverable = Deliverable::factory()->create();
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());
    $token = $link->token;
    $shortCode = $link->short_code;

    $link->delete();

    $this->get(route('share.short.show', ['shortCode' => $shortCode]))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('TaskManagement/share/error'));

    $this->get(route('share.show', ['token' => $token]))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('TaskManagement/share/error'));
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
    $expectedUrl = route('share.file', ['token' => $link->token, 'mediaUuid' => $proof->uuid]);

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
            ->where('files.0.download_url', route('share.file.download', ['token' => $link->token, 'mediaUuid' => $proof->uuid]))
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

test('a valid share download returns attachment disposition with the original filename', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $media = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    $this->get($link->publicFileDownloadUrl($media->uuid))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=hero.jpg')
        ->assertHeader('content-type', $media->mime_type);

    $this->assertGuest();
});

test('an expired share link cannot download proof files', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $media = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());
    $link->update(['expires_at' => now()->subDay()]);

    $this->get($link->publicFileDownloadUrl($media->uuid))->assertStatus(410);
});

test('a revoked share link cannot download proof files', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $media = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());
    $link->update(['revoked_at' => now()]);

    $this->get($link->publicFileDownloadUrl($media->uuid))->assertForbidden();
});

test('a valid share token cannot fetch a proof from another deliverable', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $other = Deliverable::factory()->create();
    $foreign = $other->addMedia(UploadedFile::fake()->image('secret-other.jpg'))->toMediaCollection('proofs');
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    $this->get($link->publicFileUrl($foreign->uuid))->assertForbidden();
});

test('a valid share token cannot fetch media outside the proofs collection', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    $working = $deliverable->addMedia(UploadedFile::fake()->create('notes.txt', 20, 'text/plain'))->toMediaCollection('working');
    $attachment = $deliverable->task->addMedia(UploadedFile::fake()->create('brief.pdf', 30, 'application/pdf'))->toMediaCollection('attachments');
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    $this->get($link->publicFileUrl($working->uuid))->assertForbidden();
    $this->get($link->publicFileUrl($attachment->uuid))->assertForbidden();
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

test('a client can approve through the short url', function () {
    $employee = employeeWith(\App\Modules\Core\Enums\Ability::AccessTasks);
    $reviewer = employeeWith(\App\Modules\Core\Enums\Ability::AccessTasks, \App\Modules\Core\Enums\Ability::ReviewDeliverables);
    $task = \App\Modules\TaskManagement\Models\Task::factory()->create([
        'status' => TaskStatus::InReview,
        'assigned_employee_id' => $employee->id,
    ]);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => $task->id,
        'submitted_by_employee_id' => $employee->id,
        'status' => DeliverableStatus::Approved,
    ]);
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, $reviewer->user);

    $this->post(route('share.short.approve', ['shortCode' => $link->short_code]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($task->refresh()->status)->toBe(TaskStatus::Completed);
});

test('a client can approve an internally approved deliverable and complete the task', function () {
    $employee = employeeWith(\App\Modules\Core\Enums\Ability::AccessTasks);
    $reviewer = employeeWith(\App\Modules\Core\Enums\Ability::AccessTasks, \App\Modules\Core\Enums\Ability::ReviewDeliverables);
    $task = \App\Modules\TaskManagement\Models\Task::factory()->create([
        'status' => TaskStatus::InReview,
        'assigned_employee_id' => $employee->id,
    ]);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => $task->id,
        'submitted_by_employee_id' => $employee->id,
        'status' => DeliverableStatus::Approved,
    ]);
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, $reviewer->user);

    $this->post(route('share.approve', ['token' => $link->token]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($task->refresh()->status)->toBe(TaskStatus::Completed)
        ->and($task->completed_at)->not->toBeNull();
});

test('a client can request changes and send the task back to revision', function () {
    $employee = employeeWith(\App\Modules\Core\Enums\Ability::AccessTasks);
    $reviewer = employeeWith(\App\Modules\Core\Enums\Ability::AccessTasks, \App\Modules\Core\Enums\Ability::ReviewDeliverables);
    $task = \App\Modules\TaskManagement\Models\Task::factory()->create([
        'status' => TaskStatus::InReview,
        'assigned_employee_id' => $employee->id,
    ]);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => $task->id,
        'submitted_by_employee_id' => $employee->id,
        'status' => DeliverableStatus::Approved,
    ]);
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, $reviewer->user);

    $this->post(route('share.request-changes', ['token' => $link->token]), [
        'feedback' => 'Please adjust the headline.',
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $deliverable->refresh();
    $task->refresh();

    expect($task->status)->toBe(TaskStatus::Revision)
        ->and($deliverable->status)->toBe(DeliverableStatus::ChangesRequested)
        ->and($deliverable->client_feedback)->toBe('Please adjust the headline.');
});
