<?php

use App\Modules\RecruiterOperations\Enums\RecruiterActivityType;
use App\Modules\RecruiterOperations\Services\RecruiterDailyActivityService;

test('activity types use the agreed stable values', function () {
    expect(array_map(fn (RecruiterActivityType $type) => $type->value, RecruiterActivityType::cases()))->toBe([
        'candidate_sourcing',
        'candidate_outreach',
        'follow_up',
        'linkedin_sourcing',
        'university_research',
        'email_outreach',
        'phone_calls',
        'internal_meeting',
        'training',
        'research',
        'administrative',
        'other',
    ]);
});

test('every activity type has a label and an option', function () {
    $options = RecruiterActivityType::options();

    expect($options)->toHaveCount(12);

    foreach (RecruiterActivityType::cases() as $index => $type) {
        expect($type->label())->not->toBeEmpty()
            ->and($options[$index])->toBe(['value' => $type->value, 'label' => $type->label()]);
    }
});

test('minutes between two times of day', function (string $start, string $end, int $minutes) {
    expect(RecruiterDailyActivityService::minutesBetween($start, $end))->toBe($minutes);
})->with([
    'one hour' => ['09:30', '10:30', 60],
    'two and a half hours' => ['09:15', '11:45', 150],
    'stored format with seconds' => ['09:00:00', '09:45:00', 45],
    'whole day' => ['00:00', '23:59', 1439],
    'same time' => ['10:00', '10:00', 0],
    'end before start is negative' => ['11:00', '10:00', -60],
]);

test('no same-day time pair can exceed the 24 hour limit', function () {
    expect(RecruiterDailyActivityService::minutesBetween('00:00', '23:59'))
        ->toBeLessThanOrEqual(RecruiterDailyActivityService::MAX_DURATION_MINUTES);
});

test('validation requires start and end together and caps the quantity', function () {
    $rules = RecruiterDailyActivityService::rules();

    expect($rules['start_time'])->toContain('required_with:end_time')
        ->and($rules['end_time'])->toContain('required_with:start_time')
        ->and($rules['end_time'])->toContain('after:start_time')
        ->and($rules['quantity'])->toContain('min:0')
        ->and($rules)->not->toHaveKey('employee_id')
        ->and($rules)->not->toHaveKey('duration_minutes');
});
