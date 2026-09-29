<?php

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLessonContentType;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\TrainingContentService;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Storage::fake('local');
});

function trainingContentStaff(SystemRole $role): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

/**
 * A draft course (Version 1) with text lessons, built through the service.
 */
function draftTrainingCourse(User $author, int $lessons = 2): TrainingCourse
{
    $content = app(TrainingContentService::class);
    $category = TrainingCategory::factory()->create();
    $course = $content->createCourse(['category_id' => $category->id, 'title' => 'U.S. Time Zones', 'description' => null], $author);
    $version = $course->versions()->sole();

    for ($i = 1; $i <= $lessons; $i++) {
        $content->createLesson($version, [
            'title' => "Lesson {$i}",
            'content_type' => 'text',
            'body' => "Body of lesson {$i}.",
            'is_required' => true,
        ], null, $author);
    }

    return $course->fresh();
}

function trainingLessonPayload(array $overrides = []): array
{
    return [
        'title' => 'Eastern and Pacific time',
        'description' => 'Why the clock matters when you call.',
        'content_type' => 'text',
        'body' => "New York is on Eastern Time.\n\nCalifornia is on Pacific Time.",
        'duration_minutes' => 10,
        'is_required' => 1,
        'external_url' => '',
        ...$overrides,
    ];
}

// A. Categories

test('a training manager creates, edits and deactivates a category', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);

    $this->actingAs($lead->user)
        ->post('/recruiter/training/manage/categories', ['name' => 'Level 1 - U.S. Fundamentals', 'level_number' => 1, 'sort_order' => 1])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/recruiter/training/manage/categories');

    $category = TrainingCategory::query()->sole();

    expect($category->slug)->toBe('level-1-us-fundamentals')
        ->and($category->is_active)->toBeTrue()
        ->and($category->level_number)->toBe(1)
        ->and($category->created_by_user_id)->toBe($lead->user->id);

    $this->actingAs($lead->user)
        ->put("/recruiter/training/manage/categories/{$category->id}", ['name' => 'Level 1 - Fundamentals', 'level_number' => 1, 'sort_order' => 2])
        ->assertSessionHasNoErrors();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/categories/{$category->id}/toggle")
        ->assertSessionHas('success', 'Category deactivated.');

    expect($category->fresh())
        ->name->toBe('Level 1 - Fundamentals')
        ->sort_order->toBe(2)
        ->is_active->toBeFalse();
});

test('a new course cannot be filed under an inactive category', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $inactive = TrainingCategory::factory()->inactive()->create();

    $this->actingAs($lead->user)
        ->post('/recruiter/training/manage/courses', ['category_id' => $inactive->id, 'title' => 'Visa basics'])
        ->assertSessionHasErrors('category_id');

    expect(TrainingCourse::query()->count())->toBe(0);
});

test('recruiters without the manage permission cannot reach category management, even with an invalid payload', function () {
    $recruiter = trainingContentStaff(SystemRole::Recruiter);
    $category = TrainingCategory::factory()->create();

    $this->actingAs($recruiter->user)->get('/recruiter/training/manage/categories')->assertForbidden();
    $this->actingAs($recruiter->user)->post('/recruiter/training/manage/categories', [])->assertForbidden();
    $this->actingAs($recruiter->user)->post("/recruiter/training/manage/categories/{$category->id}/toggle")->assertForbidden();

    expect(TrainingCategory::query()->count())->toBe(1)
        ->and($category->fresh()->is_active)->toBeTrue();
});

// B. Courses

test('creating a course creates an empty draft Version 1', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $category = TrainingCategory::factory()->create();

    $response = $this->actingAs($lead->user)
        ->post('/recruiter/training/manage/courses', [
            'category_id' => $category->id,
            'title' => 'U.S. Time Zones',
            'description' => 'Calling hours across the country.',
            'estimated_minutes' => 45,
        ])
        ->assertSessionHasNoErrors();

    $course = TrainingCourse::query()->sole();
    $response->assertRedirect("/recruiter/training/manage/courses/{$course->id}");

    $version = $course->versions()->sole();

    expect($course->status)->toBe(TrainingContentStatus::Draft)
        ->and($course->current_version_id)->toBeNull()
        ->and($course->isAssignable())->toBeFalse()
        ->and($version->version_number)->toBe(1)
        ->and($version->status)->toBe(TrainingContentStatus::Draft)
        ->and($version->estimated_minutes)->toBe(45);
});

test('the course list filters by category and status', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $levelOne = TrainingCategory::factory()->create(['name' => 'Level 1']);
    $levelTwo = TrainingCategory::factory()->create(['name' => 'Level 2']);
    $draft = TrainingCourse::factory()->create(['category_id' => $levelOne->id]);
    $published = TrainingCourseVersion::factory()->published()
        ->for(TrainingCourse::factory()->state(['category_id' => $levelTwo->id]), 'course')
        ->create()->course;

    $ids = fn (string $query) => $this->actingAs($lead->user)
        ->get("/recruiter/training/manage?{$query}")
        ->assertOk()
        ->viewData('page')['props']['courses']['data'];

    expect(collect($ids("category={$levelOne->id}"))->pluck('id')->all())->toBe([$draft->id])
        ->and(collect($ids('status=published'))->pluck('id')->all())->toBe([$published->id])
        ->and(collect($ids(''))->pluck('id')->sort()->values()->all())->toBe(collect([$draft->id, $published->id])->sort()->values()->all());
});

test('archiving a course stops new assignments and restoring brings back its published state', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $course = TrainingCourseVersion::factory()->published()->create()->course;

    $this->actingAs($lead->user)->post("/recruiter/training/manage/courses/{$course->id}/archive")->assertSessionHas('success');

    expect($course->fresh()->status)->toBe(TrainingContentStatus::Archived)
        ->and($course->fresh()->isAssignable())->toBeFalse();

    $this->actingAs($lead->user)->post("/recruiter/training/manage/courses/{$course->id}/restore")->assertSessionHas('success');

    expect($course->fresh()->status)->toBe(TrainingContentStatus::Published)
        ->and($course->fresh()->isAssignable())->toBeTrue();
});

test('recruiters cannot open course management', function () {
    $recruiter = trainingContentStaff(SystemRole::Recruiter);
    $course = TrainingCourse::factory()->create();

    $this->actingAs($recruiter->user)->get('/recruiter/training/manage')->assertForbidden();
    $this->actingAs($recruiter->user)->get("/recruiter/training/manage/courses/{$course->id}")->assertForbidden();
    $this->actingAs($recruiter->user)->post('/recruiter/training/manage/courses', [])->assertForbidden();
});

// C. Versioning

test('publishing a draft makes it the live, assignable version', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $course = draftTrainingCourse($lead->user);
    $version = $course->versions()->sole();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$version->id}/publish")
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $version->refresh();
    $course->refresh();

    expect($version->status)->toBe(TrainingContentStatus::Published)
        ->and($version->published_at)->not->toBeNull()
        ->and($course->status)->toBe(TrainingContentStatus::Published)
        ->and($course->current_version_id)->toBe($version->id)
        ->and($course->isAssignable())->toBeTrue();
});

test('a version without lessons cannot be published', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $course = draftTrainingCourse($lead->user, 0);
    $version = $course->versions()->sole();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$version->id}/publish")
        ->assertSessionHasErrors('version');

    expect($version->fresh()->status)->toBe(TrainingContentStatus::Draft);
});

test('a published version and its lessons cannot be changed', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $version = TrainingCourseVersion::factory()->published()->create(['description' => 'Original']);
    $lesson = TrainingLesson::factory()->forVersion($version)->create(['title' => 'Original lesson']);

    $this->actingAs($lead->user)->put("/recruiter/training/manage/versions/{$version->id}", ['description' => 'Changed'])->assertForbidden();
    $this->actingAs($lead->user)->post("/recruiter/training/manage/versions/{$version->id}/lessons", trainingLessonPayload())->assertForbidden();
    $this->actingAs($lead->user)->put("/recruiter/training/manage/lessons/{$lesson->id}", trainingLessonPayload(['title' => 'Changed']))->assertForbidden();
    $this->actingAs($lead->user)->delete("/recruiter/training/manage/lessons/{$lesson->id}")->assertForbidden();
    $this->actingAs($lead->user)->post("/recruiter/training/manage/lessons/{$lesson->id}/move", ['direction' => 'down'])->assertForbidden();
    $this->actingAs($lead->user)->post("/recruiter/training/manage/versions/{$version->id}/publish")->assertForbidden();

    expect($version->fresh()->description)->toBe('Original')
        ->and($lesson->fresh()->title)->toBe('Original lesson')
        ->and($version->lessons()->count())->toBe(1);
});

test('the service also refuses to change a published version', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $version = TrainingCourseVersion::factory()->published()->create();
    $lesson = TrainingLesson::factory()->forVersion($version)->create();

    expect(fn () => app(TrainingContentService::class)->updateLesson($lesson, trainingLessonPayload(), null, $lead->user))
        ->toThrow(ValidationException::class);
});

test('a new version copies the lessons and files into a draft, and publishing it archives the old one while assignments stay pinned', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $recruiter = trainingContentStaff(SystemRole::Recruiter);
    $course = draftTrainingCourse($lead->user, 1);
    $v1 = $course->versions()->sole();
    $content = app(TrainingContentService::class);
    $content->createLesson($v1, trainingLessonPayload([
        'title' => 'Calling guide',
        'content_type' => 'pdf',
    ]), UploadedFile::fake()->create('guide.pdf', 20, 'application/pdf'), $lead->user);
    $content->publishVersion($v1, $lead->user);
    $assignment = TrainingAssignment::factory()->forVersion($v1)->forEmployee($recruiter)->create();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/courses/{$course->id}/versions")
        ->assertSessionHasNoErrors();

    $v2 = $course->versions()->where('version_number', 2)->sole();

    expect($v2->status)->toBe(TrainingContentStatus::Draft)
        ->and($v2->lessons()->pluck('title')->all())->toBe(['Lesson 1', 'Calling guide'])
        ->and($v2->lessons()->where('title', 'Calling guide')->sole()->file())->not->toBeNull()
        ->and($v1->lessons()->where('title', 'Calling guide')->sole()->file())->not->toBeNull();

    $this->actingAs($lead->user)
        ->put("/recruiter/training/manage/lessons/{$v2->lessons()->first()->id}", trainingLessonPayload(['title' => 'Lesson 1 revised']))
        ->assertSessionHasNoErrors();
    $this->actingAs($lead->user)->post("/recruiter/training/manage/versions/{$v2->id}/publish")->assertSessionHasNoErrors();

    expect($v1->fresh()->status)->toBe(TrainingContentStatus::Archived)
        ->and($v2->fresh()->status)->toBe(TrainingContentStatus::Published)
        ->and($course->fresh()->current_version_id)->toBe($v2->id)
        ->and($assignment->fresh()->course_version_id)->toBe($v1->id)
        ->and($v1->lessons()->pluck('title')->all())->toBe(['Lesson 1', 'Calling guide']);
});

test('a course has at most one draft version', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $course = draftTrainingCourse($lead->user);

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/courses/{$course->id}/versions")
        ->assertSessionHasErrors('version');

    expect($course->versions()->count())->toBe(1);
});

test('a draft can be discarded, but not when it is the course\'s only version', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $course = draftTrainingCourse($lead->user, 1);
    $v1 = $course->versions()->sole();

    $this->actingAs($lead->user)->delete("/recruiter/training/manage/versions/{$v1->id}")->assertSessionHasErrors('version');
    expect($v1->fresh())->not->toBeNull();

    app(TrainingContentService::class)->publishVersion($v1, $lead->user);
    $v2 = app(TrainingContentService::class)->createVersion($course->fresh(), $lead->user);

    $this->actingAs($lead->user)->delete("/recruiter/training/manage/versions/{$v2->id}")->assertSessionHasNoErrors();

    expect(TrainingCourseVersion::query()->find($v2->id))->toBeNull()
        ->and(TrainingLesson::query()->where('course_version_id', $v2->id)->count())->toBe(0)
        ->and($v1->lessons()->count())->toBe(1);
});

test('archiving the live version takes the course out of assignment until a new version is published', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $version = TrainingCourseVersion::factory()->published()->create();
    $course = $version->course;

    $this->actingAs($lead->user)->post("/recruiter/training/manage/versions/{$version->id}/archive")->assertSessionHasNoErrors();

    expect($version->fresh()->status)->toBe(TrainingContentStatus::Archived)
        ->and($course->fresh()->current_version_id)->toBeNull()
        ->and($course->fresh()->status)->toBe(TrainingContentStatus::Draft)
        ->and($course->fresh()->isAssignable())->toBeFalse();
});

test('the course page shows versions and the selected version\'s lessons', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $course = draftTrainingCourse($lead->user, 2);
    $version = $course->versions()->sole();

    $this->actingAs($lead->user)
        ->get("/recruiter/training/manage/courses/{$course->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/training/manage/courses/show')
            ->has('versions', 1)
            ->where('selectedVersion.id', $version->id)
            ->has('selectedVersion.lessons', 2)
            ->where('selectedVersion.can.update', true)
            ->where('selectedVersion.can.publish', true)
            ->where('selectedVersion.can.delete', false)
            ->where('can.createVersion', false)
            ->where('can.assign', false));
});

// D. Lessons

test('a manager adds lessons to a draft in order', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $course = draftTrainingCourse($lead->user, 0);
    $version = $course->versions()->sole();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$version->id}/lessons", trainingLessonPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect("/recruiter/training/manage/courses/{$course->id}?version={$version->id}");
    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$version->id}/lessons", trainingLessonPayload(['title' => 'Central time', 'is_required' => 0]))
        ->assertSessionHasNoErrors();

    $lessons = $version->lessons()->get();

    expect($lessons->pluck('title')->all())->toBe(['Eastern and Pacific time', 'Central time'])
        ->and($lessons->pluck('sort_order')->all())->toBe([1, 2])
        ->and($lessons[0]->is_required)->toBeTrue()
        ->and($lessons[1]->is_required)->toBeFalse()
        ->and($lessons[0]->content_type)->toBe(TrainingLessonContentType::Text)
        ->and($lessons[0]->created_by_user_id)->toBe($lead->user->id);
});

test('lesson text is stored as plain text, exactly as typed', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $version = draftTrainingCourse($lead->user, 0)->versions()->sole();
    $body = "Say hello.\n<script>alert('x')</script>";

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$version->id}/lessons", trainingLessonPayload(['body' => $body]))
        ->assertSessionHasNoErrors();

    $lesson = $version->lessons()->sole();

    $this->actingAs($lead->user)
        ->get("/recruiter/training/manage/lessons/{$lesson->id}")
        ->assertInertia(fn ($page) => $page->where('lesson.body', $body));
});

test('lesson fields are validated', function (array $overrides, string $field) {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $version = draftTrainingCourse($lead->user, 0)->versions()->sole();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$version->id}/lessons", trainingLessonPayload($overrides))
        ->assertSessionHasErrors($field);

    expect($version->lessons()->count())->toBe(0);
})->with([
    'missing title' => [['title' => ''], 'title'],
    'unknown content type' => [['content_type' => 'quiz'], 'content_type'],
    'external resource without a link' => [['content_type' => 'external_resource', 'external_url' => ''], 'external_url'],
    'javascript link' => [['content_type' => 'external_resource', 'external_url' => 'javascript:alert(1)'], 'external_url'],
    'ftp link' => [['content_type' => 'external_resource', 'external_url' => 'ftp://example.com/file'], 'external_url'],
    'file type without a file' => [['content_type' => 'pdf'], 'file'],
    'file on a text lesson' => [['file' => 'placeholder'], 'file'],
]);

test('uploaded files must match the lesson type', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $version = draftTrainingCourse($lead->user, 0)->versions()->sole();
    $url = "/recruiter/training/manage/versions/{$version->id}/lessons";

    $this->actingAs($lead->user)
        ->post($url, trainingLessonPayload(['content_type' => 'pdf', 'file' => UploadedFile::fake()->image('photo.png')]))
        ->assertSessionHasErrors('file');
    $this->actingAs($lead->user)
        ->post($url, trainingLessonPayload(['content_type' => 'video', 'file' => UploadedFile::fake()->create('run.exe', 10, 'application/x-msdownload')]))
        ->assertSessionHasErrors('file');
    $this->actingAs($lead->user)
        ->post($url, trainingLessonPayload(['file' => UploadedFile::fake()->image('photo.png')]))
        ->assertSessionHasErrors('file');

    expect($version->lessons()->count())->toBe(0);
});

test('lesson files are private and only reach managers and recruiters assigned that version', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $assigned = trainingContentStaff(SystemRole::Recruiter);
    $other = trainingContentStaff(SystemRole::Recruiter);
    $course = draftTrainingCourse($lead->user, 0);
    $version = $course->versions()->sole();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/versions/{$version->id}/lessons", trainingLessonPayload([
            'content_type' => 'pdf',
            'file' => UploadedFile::fake()->create('guide.pdf', 20, 'application/pdf'),
        ]))
        ->assertSessionHasNoErrors();

    $lesson = $version->lessons()->sole();
    $media = $lesson->file();

    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('local')
        ->and($media->getCustomProperty('uploaded_by_user_id'))->toBe($lead->user->id);
    Storage::disk('local')->assertExists($media->getPathRelativeToRoot());

    app(TrainingContentService::class)->publishVersion($version, $lead->user);
    TrainingAssignment::factory()->forVersion($version)->forEmployee($assigned)->create();
    $url = "/recruiter/training/lessons/{$lesson->id}/file";

    $this->actingAs($lead->user)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($assigned->user)->get($url)->assertOk();
    $this->actingAs($other->user)->get($url)->assertForbidden();
});

test('lessons can be reordered and deleting one closes the gap', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $version = draftTrainingCourse($lead->user, 3)->versions()->sole();
    [$first, $second, $third] = $version->lessons()->get()->all();

    $this->actingAs($lead->user)
        ->post("/recruiter/training/manage/lessons/{$first->id}/move", ['direction' => 'down'])
        ->assertSessionHasNoErrors();

    expect($version->lessons()->pluck('id')->all())->toBe([$second->id, $first->id, $third->id]);

    $this->actingAs($lead->user)->delete("/recruiter/training/manage/lessons/{$second->id}")->assertSessionHasNoErrors();

    expect($version->lessons()->pluck('id')->all())->toBe([$first->id, $third->id])
        ->and($version->lessons()->pluck('sort_order')->all())->toBe([1, 2]);
});

test('editing a lesson keeps its file unless a new one is uploaded, and switching to text removes it', function () {
    $lead = trainingContentStaff(SystemRole::RecruiterLead);
    $version = draftTrainingCourse($lead->user, 0)->versions()->sole();
    $lesson = app(TrainingContentService::class)->createLesson(
        $version,
        trainingLessonPayload(['content_type' => 'image']),
        UploadedFile::fake()->image('map.png'),
        $lead->user,
    );

    $this->actingAs($lead->user)
        ->put("/recruiter/training/manage/lessons/{$lesson->id}", trainingLessonPayload(['content_type' => 'image', 'title' => 'Time zone map']))
        ->assertSessionHasNoErrors();

    expect($lesson->fresh()->title)->toBe('Time zone map')
        ->and($lesson->fresh()->file())->not->toBeNull();

    $this->actingAs($lead->user)
        ->put("/recruiter/training/manage/lessons/{$lesson->id}", trainingLessonPayload(['content_type' => 'text']))
        ->assertSessionHasNoErrors();

    expect($lesson->fresh()->file())->toBeNull();
});
