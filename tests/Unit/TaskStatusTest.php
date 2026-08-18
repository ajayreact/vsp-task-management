<?php

use App\Modules\TaskManagement\Enums\TaskStatus;

/*
|--------------------------------------------------------------------------
| The lifecycle in isolation
|--------------------------------------------------------------------------
|
| The HTTP tests prove the workflow uses the state machine. These prove the
| machine itself is right, without a database in the way.
|
*/

test('the simplified happy path from assignment to resubmission is walkable', function () {
    $path = [
        TaskStatus::Draft,
        TaskStatus::Assigned,
        TaskStatus::InProgress,
        TaskStatus::InReview,
        TaskStatus::Revision,
        TaskStatus::InReview,
    ];

    foreach (array_slice($path, 0, -1) as $index => $status) {
        expect($status->canTransitionTo($path[$index + 1]))->toBeTrue();
    }
});

test('completed is terminal', function () {
    expect(TaskStatus::Completed->allowedNext())->toBeEmpty()
        ->and(TaskStatus::Completed->isClosed())->toBeTrue();
});

test('acceptance moves directly to in progress', function () {
    expect(TaskStatus::Assigned->canTransitionTo(TaskStatus::InProgress))->toBeTrue()
        ->and(TaskStatus::Assigned->canTransitionTo(TaskStatus::Accepted))->toBeFalse()
        ->and(TaskStatus::Open->canTransitionTo(TaskStatus::InProgress))->toBeTrue();
});

test('a declined task falls back to the open board', function () {
    expect(TaskStatus::Assigned->canTransitionTo(TaskStatus::Open))->toBeTrue();
});

test('work must go through review before completion', function () {
    expect(TaskStatus::InProgress->canTransitionTo(TaskStatus::Completed))->toBeFalse()
        ->and(TaskStatus::InProgress->canTransitionTo(TaskStatus::InReview))->toBeTrue();
});

test('revision loops back for another round of review', function () {
    expect(TaskStatus::InReview->canTransitionTo(TaskStatus::Revision))->toBeTrue()
        ->and(TaskStatus::Revision->canTransitionTo(TaskStatus::InReview))->toBeTrue()
        ->and(TaskStatus::Revision->canTransitionTo(TaskStatus::Approved))->toBeFalse();
});

test('only the pre-work states count as unstarted', function () {
    expect(TaskStatus::Draft->isUnstarted())->toBeTrue()
        ->and(TaskStatus::Open->isUnstarted())->toBeTrue()
        ->and(TaskStatus::Assigned->isUnstarted())->toBeTrue()
        ->and(TaskStatus::InProgress->isUnstarted())->toBeFalse();
});

test('no status can transition to itself', function () {
    foreach (TaskStatus::cases() as $status) {
        expect($status->canTransitionTo($status))->toBeFalse();
    }
});

test('every status has a label', function () {
    foreach (TaskStatus::cases() as $status) {
        expect($status->label())->not->toBeEmpty();
    }
});
