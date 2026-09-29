<?php

use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Services\TrainingProgressCalculator;
use App\Modules\RecruiterOperations\Services\TrainingSpeechService;
use App\Modules\RecruiterOperations\Speech\Providers\BrowserSpeechProvider;
use App\Modules\RecruiterOperations\Speech\Providers\DisabledSpeechProvider;
use App\Modules\RecruiterOperations\Speech\TrainingAudio;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechDelivery;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechProvider;
use App\Modules\RecruiterOperations\Speech\TrainingSpeechProviderResolver;
use App\Modules\RecruiterOperations\Speech\VoiceProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

function unitLesson(int $id, bool $required = true): TrainingLesson
{
    return (new TrainingLesson)->forceFill(['id' => $id, 'is_required' => $required]);
}

function unitAssignment(TrainingAssignmentStatus $status, ?string $due = null): TrainingAssignment
{
    return (new TrainingAssignment)->forceFill([
        'status' => $status,
        'due_at' => $due !== null ? Carbon::parse($due) : null,
    ]);
}

// Progress calculation

test('progress counts only the required lessons', function () {
    $lessons = [unitLesson(1), unitLesson(2, false), unitLesson(3)];

    expect(TrainingProgressCalculator::countedLessonIds($lessons))->toBe([1, 3]);
});

test('a course with no required lessons counts all of them', function () {
    $lessons = [unitLesson(1, false), unitLesson(2, false)];

    expect(TrainingProgressCalculator::countedLessonIds($lessons))->toBe([1, 2]);
});

test('progress is a whole percent rounded down, ignoring optional lessons', function (array $counted, array $completed, int $percent) {
    expect(TrainingProgressCalculator::percent($counted, $completed))->toBe($percent);
})->with([
    'nothing done' => [[1, 2, 3], [], 0],
    'one of three' => [[1, 2, 3], [1], 33],
    'two of three' => [[1, 2, 3], [1, 3], 66],
    'optional lesson done too' => [[1, 3], [1, 2], 50],
    'all done' => [[1, 2], [2, 1], 100],
    'no lessons' => [[], [], 0],
]);

// Course completion

test('a course is complete only when every counted lesson is complete', function (array $counted, array $completed, bool $complete) {
    expect(TrainingProgressCalculator::isComplete($counted, $completed))->toBe($complete);
})->with([
    'all counted done' => [[1, 3], [1, 3], true],
    'extra optional done' => [[1, 3], [1, 2, 3], true],
    'one missing' => [[1, 3], [1, 2], false],
    'no lessons at all' => [[], [], false],
]);

// Lesson completion

test('a lesson counts as completed only once completed_at is set', function () {
    $started = (new TrainingLessonCompletion)->forceFill(['started_at' => now(), 'completed_at' => null]);
    $done = (new TrainingLessonCompletion)->forceFill(['started_at' => now(), 'completed_at' => now()]);

    expect($started->isCompleted())->toBeFalse()
        ->and($done->isCompleted())->toBeTrue();
});

// Version resolution

test('only a published course with a live version can be assigned', function (TrainingContentStatus $status, ?int $currentVersionId, bool $assignable) {
    $course = (new TrainingCourse)->forceFill(['status' => $status, 'current_version_id' => $currentVersionId]);

    expect($course->isAssignable())->toBe($assignable);
})->with([
    'published with a live version' => [TrainingContentStatus::Published, 7, true],
    'published without a live version' => [TrainingContentStatus::Published, null, false],
    'draft' => [TrainingContentStatus::Draft, null, false],
    'archived with a live version' => [TrainingContentStatus::Archived, 7, false],
]);

test('only a draft version is editable, and versions are labelled by number', function () {
    $draft = (new TrainingCourseVersion)->forceFill(['status' => TrainingContentStatus::Draft, 'version_number' => 3]);
    $published = (new TrainingCourseVersion)->forceFill(['status' => TrainingContentStatus::Published, 'version_number' => 2]);

    expect($draft->isDraft())->toBeTrue()
        ->and($draft->label())->toBe('v3')
        ->and($published->isDraft())->toBeFalse()
        ->and($published->isPublished())->toBeTrue();
});

// Assignment status

test('overdue is derived from the due date and never overrides completion', function (TrainingAssignmentStatus $stored, ?string $due, TrainingAssignmentStatus $expected) {
    $now = Carbon::parse('2026-09-29 12:00:00');

    expect(unitAssignment($stored, $due)->effectiveStatus($now))->toBe($expected);
})->with([
    'no due date' => [TrainingAssignmentStatus::Assigned, null, TrainingAssignmentStatus::Assigned],
    'due later' => [TrainingAssignmentStatus::InProgress, '2026-09-30 23:59:59', TrainingAssignmentStatus::InProgress],
    'past due, not started' => [TrainingAssignmentStatus::Assigned, '2026-09-28 23:59:59', TrainingAssignmentStatus::Overdue],
    'past due, in progress' => [TrainingAssignmentStatus::InProgress, '2026-09-28 23:59:59', TrainingAssignmentStatus::Overdue],
    'past due but completed' => [TrainingAssignmentStatus::Completed, '2026-09-28 23:59:59', TrainingAssignmentStatus::Completed],
]);

test('an assignment counts as started once it has a start time or has moved on from assigned', function () {
    expect(unitAssignment(TrainingAssignmentStatus::Assigned)->isStarted())->toBeFalse()
        ->and(unitAssignment(TrainingAssignmentStatus::InProgress)->isStarted())->toBeTrue()
        ->and(unitAssignment(TrainingAssignmentStatus::Assigned)->forceFill(['started_at' => now()])->isStarted())->toBeTrue();
});

test('overdue is shown, never stored', function () {
    expect(array_column(TrainingAssignmentStatus::options(), 'value'))->toBe(['assigned', 'in_progress', 'completed', 'overdue'])
        ->and(TrainingAssignmentStatus::Assigned->label())->toBe('Not started');
});

// Audio provider selection

test('the speech driver picks the configured provider', function (?string $driver, string $class) {
    expect(app(TrainingSpeechProviderResolver::class)->resolve($driver))->toBeInstanceOf($class);
})->with([
    'browser' => ['browser', BrowserSpeechProvider::class],
    'disabled' => ['disabled', DisabledSpeechProvider::class],
    'not set' => [null, BrowserSpeechProvider::class],
]);

test('an unknown speech driver falls back to the browser provider and logs a warning', function () {
    Log::shouldReceive('warning')->once()->withArgs(fn (string $message, array $context) => $context === ['driver' => 'polly']);

    expect(app(TrainingSpeechProviderResolver::class)->resolve('polly'))->toBeInstanceOf(BrowserSpeechProvider::class);
});

test('a configured class that is not a speech provider is never used', function () {
    config(['recruiter-training.speech.providers.bogus' => stdClass::class]);
    Log::shouldReceive('warning')->once();

    expect(app(TrainingSpeechProviderResolver::class)->resolve('bogus'))->toBeInstanceOf(BrowserSpeechProvider::class);
});

test('the application uses the provider named by the environment setting', function () {
    expect(app(TrainingSpeechProvider::class))->toBeInstanceOf(BrowserSpeechProvider::class)
        ->and(config('recruiter-training.speech.driver'))->toBe('browser');
});

// Voices

test('Indian English is offered first, with the en-IN locale', function () {
    $voices = (new TrainingSpeechService(new BrowserSpeechProvider))->voiceOptions();

    expect($voices[0])->toBe(['key' => 'indian_english', 'label' => 'Indian English', 'locale' => 'en-IN', 'flag' => '🇮🇳'])
        ->and(array_column($voices, 'key'))->toBe(['indian_english', 'us_english', 'uk_english']);
});

test('only voices the provider really supports are offered', function () {
    $provider = new class implements TrainingSpeechProvider
    {
        public function name(): string
        {
            return 'us-only';
        }

        public function delivery(): TrainingSpeechDelivery
        {
            return TrainingSpeechDelivery::Audio;
        }

        public function supportedVoices(): array
        {
            return ['us_english'];
        }

        public function synthesize(string $text, VoiceProfile $voice): TrainingAudio
        {
            return new TrainingAudio('x');
        }
    };
    $speech = new TrainingSpeechService($provider);

    expect(array_column($speech->voiceOptions(), 'key'))->toBe(['us_english'])
        ->and($speech->resolveVoice(null)->key)->toBe('us_english');
    expect(fn () => $speech->resolveVoice('indian_english'))->toThrow(ValidationException::class);
});

test('voice resolution defaults to Indian English and refuses unknown voices', function () {
    $speech = new TrainingSpeechService(new BrowserSpeechProvider);

    expect($speech->resolveVoice(null)->locale)->toBe('en-IN')
        ->and($speech->resolveVoice('')->key)->toBe('indian_english')
        ->and($speech->resolveVoice('uk_english')->locale)->toBe('en-GB');
    expect(fn () => $speech->resolveVoice('robot'))->toThrow(ValidationException::class);
});

test('with speech disabled there are no voices to choose', function () {
    $speech = new TrainingSpeechService(new DisabledSpeechProvider);

    expect($speech->voices())->toBe([]);
    expect(fn () => $speech->resolveVoice(null))->toThrow(ValidationException::class);
});

// Spoken text

test('the spoken text is the title, summary and body, capped at the configured length', function () {
    $speech = new TrainingSpeechService(new BrowserSpeechProvider);
    $lesson = (new TrainingLesson)->forceFill(['title' => 'Time zones', 'description' => 'Why it matters.', 'body' => "Line one.\r\nLine two."]);

    expect($speech->lessonText($lesson))->toBe("Time zones.\n\nWhy it matters.\n\nLine one.\nLine two.");

    config(['recruiter-training.speech.max_characters' => 10]);

    expect($speech->lessonText($lesson))->toBe('Time zones');
});

test('long text is split into short pieces for the browser voice', function () {
    $speech = new TrainingSpeechService(new BrowserSpeechProvider);
    $text = str_repeat('Recruiters call candidates during business hours in the candidate\'s own time zone. ', 12)
        ."\n\n".str_repeat('word', 80);

    $segments = $speech->segments($text);

    expect(count($segments))->toBeGreaterThan(3);

    foreach ($segments as $segment) {
        expect(mb_strlen($segment))->toBeLessThanOrEqual(TrainingSpeechService::SEGMENT_CHARACTERS)
            ->and(trim($segment))->not->toBe('');
    }

    expect(implode('', $segments))->toContain('wordword');
});

test('listening time is estimated from the word count', function () {
    $speech = new TrainingSpeechService(new BrowserSpeechProvider);

    expect($speech->estimateSeconds(''))->toBe(0)
        ->and($speech->estimateSeconds(str_repeat('word ', 150)))->toBe(60)
        ->and($speech->estimateSeconds('one'))->toBe(1);
});
