<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\CompanyLogoVariant;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Services\CompanyShareLinkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
});

function crtEmployee(): Employee
{
    $department = Department::factory()->create(['code' => 'CRT']);
    $employee = Employee::factory()->for($department)->create();

    $employee->user->syncPermissions([Ability::AccessTasks->value]);

    return $employee;
}

function opsEmployee(): Employee
{
    $department = Department::factory()->create(['code' => 'OPS']);
    $employee = Employee::factory()->for($department)->create();

    $employee->user->syncPermissions([Ability::AccessTasks->value]);

    return $employee;
}

test('crt users can view the company logo library', function () {
    Company::factory()->create(['name' => 'Acme Corp']);

    $this->actingAs(crtEmployee()->user)
        ->get('/tasks/logo-library')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/logo-library/index')
            ->has('companies.data', 1)
            ->where('companies.data.0.name', 'Acme Corp'));
});

test('ops users can view the company logo library', function () {
    Company::factory()->create(['name' => 'Northwind']);

    $this->actingAs(opsEmployee()->user)
        ->get('/tasks/logo-library')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/logo-library/index')
            ->where('companies.data.0.name', 'Northwind'));
});

test('unauthorized users cannot access the company logo library', function () {
    Company::factory()->create();

    $employee = employeeWith(Ability::AccessTasks);
    $employee->department->update(['code' => 'FIN']);

    $this->actingAs($employee->user)
        ->get('/tasks/logo-library')
        ->assertForbidden();
});

test('company details can be updated by logo managers', function () {
    $company = Company::factory()->create([
        'name' => 'Old Name',
        'website' => null,
        'primary_contact_email' => 'old@example.com',
        'primary_contact_phone' => '111',
    ]);

    $manager = employeeWith(Ability::AccessTasks, Ability::ManageCompanyLogos);

    $this->actingAs($manager->user)
        ->put("/tasks/logo-library/{$company->id}", [
            'name' => 'Updated Name',
            'website' => 'https://example.com',
            'primary_contact_email' => 'new@example.com',
            'primary_contact_phone' => '999',
        ])
        ->assertRedirect();

    $company->refresh();

    expect($company->name)->toBe('Updated Name')
        ->and($company->website)->toBe('https://example.com')
        ->and($company->primary_contact_email)->toBe('new@example.com')
        ->and($company->primary_contact_phone)->toBe('999');
});

test('multiple logo variants can be uploaded and listed', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageCompanyLogos);

    $this->actingAs($manager->user)
        ->post("/tasks/logo-library/{$company->id}/logos", [
            'variant' => CompanyLogoVariant::Original->value,
            'file' => UploadedFile::fake()->image('original.png'),
        ])
        ->assertRedirect();

    $this->actingAs($manager->user)
        ->post("/tasks/logo-library/{$company->id}/logos", [
            'variant' => CompanyLogoVariant::Transparent->value,
            'file' => UploadedFile::fake()->image('transparent.png'),
        ])
        ->assertRedirect();

    $this->actingAs($manager->user)
        ->get("/tasks/logo-library/{$company->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/logo-library/show')
            ->has('company.logos', 2));
});

test('logos can be previewed by media uuid', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $viewer = crtEmployee();

    $media = $company->addMedia(UploadedFile::fake()->image('brand.png'))
        ->withCustomProperties(['variant' => CompanyLogoVariant::Original->value])
        ->toMediaCollection('logos');

    $this->actingAs($viewer->user)
        ->get(route('tasks.logo-library.logos.preview', ['company' => $company, 'media' => $media->uuid]))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename="brand.png"');
});

test('logos can be downloaded with attachment disposition', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageCompanyLogos);
    $viewer = crtEmployee();

    $media = $company->addMedia(UploadedFile::fake()->image('brand.png'))
        ->withCustomProperties(['variant' => CompanyLogoVariant::Original->value])
        ->toMediaCollection('logos');

    $this->actingAs($viewer->user)
        ->get(route('tasks.logo-library.logos.download', ['company' => $company, 'media' => $media->uuid]))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="brand.png"');
});

test('logo managers can delete logos', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageCompanyLogos);

    $media = $company->addMedia(UploadedFile::fake()->image('brand.png'))
        ->withCustomProperties(['variant' => CompanyLogoVariant::Original->value])
        ->toMediaCollection('logos');

    $this->actingAs($manager->user)
        ->delete("/tasks/logo-library/{$company->id}/logos/{$media->uuid}")
        ->assertRedirect(route('tasks.logo-library.show', $company));

    expect($company->fresh()->getMedia('logos'))->toHaveCount(0);
});

test('logo library search filters companies', function () {
    Company::factory()->create(['name' => 'Alpha Studios']);
    Company::factory()->create(['name' => 'Beta Works']);

    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewCompanyLogos);

    $this->actingAs($viewer->user)
        ->get('/tasks/logo-library?search=Alpha')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('companies.data', 1)
            ->where('companies.data.0.name', 'Alpha Studios'));
});

test('a public company share link exposes company details and logos', function () {
    Storage::fake('public');

    $company = Company::factory()->create([
        'name' => 'Shared Client',
        'website' => 'https://shared.example',
        'primary_contact_email' => 'hello@shared.example',
        'primary_contact_phone' => '1234567890',
    ]);

    $logo = $company->addMedia(UploadedFile::fake()->image('logo.png'))
        ->withCustomProperties(['variant' => CompanyLogoVariant::Original->value])
        ->toMediaCollection('logos');

    $link = app(CompanyShareLinkService::class)->getOrCreate($company, User::factory()->create());

    $this->get($link->publicUrl())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/company-share/show')
            ->where('company.name', 'Shared Client')
            ->where('company.website', 'https://shared.example')
            ->has('logos', 1)
            ->where('logos.0.name', 'logo.png')
            ->missing('company.id'));

    $this->get($link->publicFileDownloadUrl($logo->uuid))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=logo.png');
});

test('revoked company share links are rejected', function () {
    $company = Company::factory()->create();
    $link = app(CompanyShareLinkService::class)->getOrCreate($company, User::factory()->create());
    $link->update(['revoked_at' => now()]);

    $this->get($link->publicUrl())
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('title', 'Link Unavailable'));
});

test('expired company share links are rejected', function () {
    $company = Company::factory()->create();
    $link = app(CompanyShareLinkService::class)->getOrCreate($company, User::factory()->create());
    $link->update(['expires_at' => now()->subDay()]);

    $this->get($link->publicUrl())
        ->assertStatus(410)
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/share/error')
            ->where('title', 'Link Expired'));
});
