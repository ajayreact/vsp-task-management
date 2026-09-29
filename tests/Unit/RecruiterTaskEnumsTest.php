<?php

use App\Modules\RecruiterOperations\Enums\RecruiterTaskEventType;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskPriority;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskStatus;
use App\Modules\RecruiterOperations\Enums\RecruiterWorkType;

test('recruiter task statuses use the agreed values with no persisted accepted state', function () {
    expect(array_map(fn (RecruiterTaskStatus $status) => $status->value, RecruiterTaskStatus::cases()))->toBe([
        'assigned', 'in_progress', 'on_hold', 'completed', 'declined', 'cancelled',
    ])
        ->and(RecruiterTaskStatus::tryFrom('accepted'))->toBeNull()
        ->and(RecruiterTaskStatus::tryFrom('reopened'))->toBeNull();
});

test('each status allows exactly the agreed next statuses', function (RecruiterTaskStatus $status, array $next) {
    expect($status->allowedNext())->toBe($next);

    foreach (RecruiterTaskStatus::cases() as $target) {
        expect($status->canTransitionTo($target))->toBe(in_array($target, $next, true));
    }
})->with([
    'assigned' => [RecruiterTaskStatus::Assigned, [RecruiterTaskStatus::InProgress, RecruiterTaskStatus::Declined, RecruiterTaskStatus::Cancelled]],
    'in progress' => [RecruiterTaskStatus::InProgress, [RecruiterTaskStatus::OnHold, RecruiterTaskStatus::Completed, RecruiterTaskStatus::Cancelled]],
    'on hold' => [RecruiterTaskStatus::OnHold, [RecruiterTaskStatus::InProgress, RecruiterTaskStatus::Cancelled]],
    'declined' => [RecruiterTaskStatus::Declined, [RecruiterTaskStatus::Assigned, RecruiterTaskStatus::Cancelled]],
    'completed' => [RecruiterTaskStatus::Completed, [RecruiterTaskStatus::InProgress]],
    'cancelled' => [RecruiterTaskStatus::Cancelled, []],
]);

test('only cancelled is terminal', function () {
    foreach (RecruiterTaskStatus::cases() as $status) {
        expect($status->isTerminal())->toBe($status === RecruiterTaskStatus::Cancelled);
    }
});

test('open, pending and reassignable groupings', function () {
    $open = array_filter(RecruiterTaskStatus::cases(), fn (RecruiterTaskStatus $status) => $status->isOpen());
    $reassignable = array_filter(RecruiterTaskStatus::cases(), fn (RecruiterTaskStatus $status) => $status->isReassignable());

    expect(array_values($open))->toBe([
        RecruiterTaskStatus::Assigned, RecruiterTaskStatus::InProgress, RecruiterTaskStatus::OnHold, RecruiterTaskStatus::Declined,
    ])
        ->and(RecruiterTaskStatus::pendingValues())->toBe(['assigned', 'in_progress', 'on_hold'])
        ->and(RecruiterTaskStatus::Declined->countsAsPending())->toBeFalse()
        ->and(array_values($reassignable))->toBe([RecruiterTaskStatus::Assigned, RecruiterTaskStatus::Declined]);
});

test('options carry a label for every case', function (string $enum) {
    $options = $enum::options();

    expect($options)->toHaveCount(count($enum::cases()));

    foreach ($options as $option) {
        expect($option)->toHaveKeys(['value', 'label'])
            ->and($option['label'])->not->toBeEmpty();
    }
})->with([RecruiterTaskStatus::class, RecruiterTaskPriority::class, RecruiterWorkType::class]);

test('priorities and work types use the agreed values', function () {
    expect(array_map(fn ($case) => $case->value, RecruiterTaskPriority::cases()))->toBe(['low', 'normal', 'high', 'urgent'])
        ->and(array_map(fn ($case) => $case->value, RecruiterWorkType::cases()))->toBe([
            'candidate_sourcing',
            'candidate_outreach',
            'follow_up',
            'linkedin_sourcing',
            'university_research',
            'training',
            'internal_meeting',
            'other',
        ]);
});

test('event types cover the lifecycle and reopening is only an event', function () {
    expect(array_map(fn (RecruiterTaskEventType $event) => $event->value, RecruiterTaskEventType::cases()))->toBe([
        'created', 'updated', 'assigned', 'reassigned', 'accepted', 'declined',
        'put_on_hold', 'resumed', 'completed', 'reopened', 'cancelled',
    ])
        ->and(RecruiterTaskEventType::setupEvents())->toBe([
            RecruiterTaskEventType::Created, RecruiterTaskEventType::Updated, RecruiterTaskEventType::Assigned,
        ]);

    foreach (RecruiterTaskEventType::cases() as $event) {
        expect($event->label())->not->toBeEmpty();
    }
});
