<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;

test('recruiter abilities use the agreed names and share one group', function () {
    $recruiter = array_values(array_filter(
        Ability::cases(),
        fn (Ability $ability) => str_starts_with($ability->value, 'recruiter.'),
    ));

    expect(array_map(fn (Ability $ability) => $ability->value, $recruiter))->toBe([
        'recruiter.access',
        'recruiter.team.view',
        'recruiter.tasks.manage',
        'recruiter.training.manage',
        'recruiter.training.assign',
        'recruiter.assessments.manage',
        'recruiter.assessments.invite',
        'recruiter.assessments.review',
    ]);

    foreach ($recruiter as $ability) {
        expect($ability->group())->toBe('Recruiter Operations')
            ->and($ability->label())->not->toBeEmpty();
    }
});

test('recruiter roles never carry digital marketing, administration or finance abilities', function (SystemRole $role) {
    foreach ($role->abilities() as $ability) {
        expect($ability->value)->toStartWith('recruiter.');
    }
})->with([SystemRole::Recruiter, SystemRole::RecruiterLead]);

test('digital marketing roles carry no recruiter abilities', function (SystemRole $role) {
    foreach ($role->abilities() as $ability) {
        expect($ability->value)->not->toStartWith('recruiter.');
    }
})->with([SystemRole::TeamLead, SystemRole::Manager, SystemRole::Employee]);

test('admin keeps every ability, recruiter ones included', function () {
    expect(SystemRole::Admin->abilities())->toBe(Ability::cases());
});
