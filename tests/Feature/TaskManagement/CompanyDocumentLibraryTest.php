<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\CompanyDocumentCategory;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyDocument;
use App\Modules\TaskManagement\Services\CompanyDocumentShareLinkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
});

function opsDocumentEmployee(): Employee
{
    $department = Department::factory()->create(['code' => 'OPS']);
    $employee = Employee::factory()->for($department)->create();
    $employee->user->syncPermissions([Ability::AccessTasks->value]);

    return $employee;
}

test('admin can view the operations document library', function () {
    CompanyDocument::factory()->create(['title' => 'Master agreement']);

    $admin = employeeWith(Ability::AccessTasks, Ability::ViewCompanyDocuments);

    $this->actingAs($admin->user)
        ->get('/tasks/documents')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/documents/index')
            ->where('documents.data.0.title', 'Master agreement'));
});

test('ops users can view the operations document library', function () {
    CompanyDocument::factory()->create(['title' => 'Ops NDA']);

    $this->actingAs(opsDocumentEmployee()->user)
        ->get('/tasks/documents')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('documents.data.0.title', 'Ops NDA'));
});

test('ops users can upload documents', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $employee = opsDocumentEmployee();

    $this->actingAs($employee->user)
        ->post('/tasks/documents', [
            'tm_company_id' => $company->id,
            'title' => 'Signed contract',
            'category' => CompanyDocumentCategory::Contract->value,
            'description' => 'Signed copy',
            'file' => UploadedFile::fake()->create('contract.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect();

    $document = CompanyDocument::query()->sole();

    expect($document->title)->toBe('Signed contract')
        ->and($document->tm_company_id)->toBe($company->id)
        ->and($document->getFirstMedia('file'))->not->toBeNull();
});

test('unauthorized users cannot access the operations document library', function () {
    CompanyDocument::factory()->create();

    $employee = employeeWith(Ability::AccessTasks);
    $employee->department->update(['code' => 'FIN']);

    $this->actingAs($employee->user)
        ->get('/tasks/documents')
        ->assertForbidden();
});

test('document download returns an attachment response', function () {
    Storage::fake('public');

    $document = CompanyDocument::factory()->create();
    $document->addMedia(UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'))
        ->toMediaCollection('file');

    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewCompanyDocuments);

    $this->actingAs($viewer->user)
        ->get("/tasks/documents/{$document->id}/download")
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('document preview and download are separate routes', function () {
    Storage::fake('public');

    $document = CompanyDocument::factory()->create();
    $document->addMedia(UploadedFile::fake()->image('scan.jpg'))
        ->toMediaCollection('file');

    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewCompanyDocuments);

    $this->actingAs($viewer->user)
        ->get("/tasks/documents/{$document->id}/preview")
        ->assertOk()
        ->assertHeaderMissing('content-disposition');

    $this->actingAs($viewer->user)
        ->get("/tasks/documents/{$document->id}/download")
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('invalid document uploads are rejected', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageCompanyDocuments);

    $this->actingAs($manager->user)
        ->post('/tasks/documents', [
            'tm_company_id' => $company->id,
            'title' => 'Bad file',
            'category' => CompanyDocumentCategory::Other->value,
            'file' => UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream'),
        ])
        ->assertSessionHasErrors('file');
});

test('documents can be replaced on update', function () {
    Storage::fake('public');

    $document = CompanyDocument::factory()->create();
    $document->addMedia(UploadedFile::fake()->create('old.pdf', 100, 'application/pdf'))
        ->toMediaCollection('file');

    $manager = employeeWith(Ability::AccessTasks, Ability::ManageCompanyDocuments);

    $this->actingAs($manager->user)
        ->put("/tasks/documents/{$document->id}", [
            'tm_company_id' => $document->tm_company_id,
            'title' => 'Updated title',
            'category' => CompanyDocumentCategory::Invoice->value,
            'file' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $document->refresh();

    expect($document->title)->toBe('Updated title')
        ->and($document->getFirstMedia('file')?->file_name)->toBe('new.pdf');
});

test('delete permissions are enforced for documents', function () {
    $document = CompanyDocument::factory()->create();
    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewCompanyDocuments);

    $this->actingAs($viewer->user)
        ->delete("/tasks/documents/{$document->id}")
        ->assertForbidden();
});

test('public document share links expose only intended information', function () {
    Storage::fake('public');

    $document = CompanyDocument::factory()->create([
        'title' => 'Shared contract',
        'description' => 'Client-facing notes',
    ]);
    $document->addMedia(UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'))
        ->toMediaCollection('file');

    $link = app(CompanyDocumentShareLinkService::class)->getOrCreate($document, $document->createdBy);

    $this->get($link->publicUrl())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/document-share/show')
            ->where('document.title', 'Shared contract')
            ->where('client_name', $document->company->name)
            ->missing('document.id'));
});
