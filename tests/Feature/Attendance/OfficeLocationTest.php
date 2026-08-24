<?php

use App\Modules\Attendance\Models\OfficeLocation;

beforeEach(function () {
    $this->withoutVite();
});

test('super admin can manage office locations', function () {
    $office = OfficeLocation::factory()->create([
        'name' => 'Head Office',
        'address' => '123 Main Street, Delhi',
        'latitude' => 28.613939,
        'longitude' => 77.209023,
        'allowed_gps_radius_meters' => 150,
        'is_active' => true,
    ]);

    $this->actingAs(superAdmin())
        ->get('/admin/attendance/offices')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/offices/index')
            ->has('offices.data', 1)
            ->where('offices.data.0.name', 'Head Office'));

    $this->actingAs(superAdmin())
        ->get('/admin/attendance/offices/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Attendance/offices/create'));

    $this->actingAs(superAdmin())
        ->post('/admin/attendance/offices', [
            'name' => 'Branch Office',
            'address' => '456 Park Avenue, Mumbai',
            'latitude' => 19.076090,
            'longitude' => 72.877426,
            'allowed_gps_radius_meters' => 200,
            'late_check_in_time' => '09:30',
            'network_verification_enabled' => true,
            'authorized_public_ips_text' => "203.0.113.10\n198.51.100.0/24",
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.attendance.offices.index'))
        ->assertSessionHas('success');

    expect(OfficeLocation::query()->where('name', 'Branch Office')->exists())->toBeTrue();

    $branch = OfficeLocation::query()->where('name', 'Branch Office')->first();
    expect($branch->network_verification_enabled)->toBeTrue()
        ->and($branch->authorized_public_ips)->toBe(['203.0.113.10', '198.51.100.0/24']);

    $this->actingAs(superAdmin())
        ->get("/admin/attendance/offices/{$office->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Attendance/offices/edit')
            ->where('office.name', 'Head Office'));

    $this->actingAs(superAdmin())
        ->put("/admin/attendance/offices/{$office->id}", [
            'name' => 'Head Office Updated',
            'address' => '789 Updated Street, Delhi',
            'latitude' => 28.704060,
            'longitude' => 77.102493,
            'allowed_gps_radius_meters' => 250,
            'late_check_in_time' => '09:30',
            'network_verification_enabled' => false,
            'authorized_public_ips_text' => '',
            'is_active' => false,
        ])
        ->assertRedirect(route('admin.attendance.offices.index'))
        ->assertSessionHas('success');

    $office->refresh();
    expect($office->name)->toBe('Head Office Updated')
        ->and($office->is_active)->toBeFalse();

    $this->actingAs(superAdmin())
        ->post("/admin/attendance/offices/{$office->id}/deactivate")
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs(superAdmin())
        ->delete("/admin/attendance/offices/{$office->id}")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(OfficeLocation::query()->find($office->id))->toBeNull();
});

test('staff without super admin role cannot manage office locations', function () {
    $office = OfficeLocation::factory()->create();

    $this->actingAs(staffWith())
        ->get('/admin/attendance/offices')
        ->assertForbidden();

    $this->actingAs(staffWith())
        ->post('/admin/attendance/offices', [
            'name' => 'Blocked Office',
            'address' => 'Nowhere',
            'latitude' => 12.971599,
            'longitude' => 77.594566,
            'allowed_gps_radius_meters' => 100,
            'late_check_in_time' => '09:30',
            'network_verification_enabled' => false,
            'authorized_public_ips_text' => '',
            'is_active' => true,
        ])
        ->assertForbidden();

    $this->actingAs(staffWith())
        ->put("/admin/attendance/offices/{$office->id}", [
            'name' => 'Blocked Update',
            'address' => 'Nowhere',
            'latitude' => 12.971599,
            'longitude' => 77.594566,
            'allowed_gps_radius_meters' => 100,
            'late_check_in_time' => '09:30',
            'network_verification_enabled' => false,
            'authorized_public_ips_text' => '',
            'is_active' => true,
        ])
        ->assertForbidden();

    $this->actingAs(staffWith())
        ->delete("/admin/attendance/offices/{$office->id}")
        ->assertForbidden();
});

test('office location validation rejects invalid gps values', function () {
    $this->actingAs(superAdmin())
        ->post('/admin/attendance/offices', [
            'name' => 'Invalid Office',
            'address' => 'Somewhere',
            'latitude' => 120,
            'longitude' => 200,
            'allowed_gps_radius_meters' => 0,
            'late_check_in_time' => '09:30',
            'network_verification_enabled' => true,
            'authorized_public_ips_text' => 'not-an-ip',
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['latitude', 'longitude', 'allowed_gps_radius_meters', 'authorized_public_ips_text']);
});
