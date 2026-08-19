<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Http\Requests\OfficeLocationRequest;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficeLocationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAttendance');

        $offices = OfficeLocation::query()
            ->orderBy('name')
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (OfficeLocation $office) => $this->summarise($office));

        return Inertia::render('Attendance/offices/index', [
            'offices' => $offices,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('viewAttendance');

        return Inertia::render('Attendance/offices/create');
    }

    public function store(OfficeLocationRequest $request): RedirectResponse
    {
        $this->authorize('viewAttendance');

        OfficeLocation::query()->create($request->validated());

        return redirect()
            ->route('admin.attendance.offices.index')
            ->with('success', 'Office location created.');
    }

    public function edit(OfficeLocation $officeLocation): Response
    {
        $this->authorize('viewAttendance');

        return Inertia::render('Attendance/offices/edit', [
            'office' => $this->formPayload($officeLocation),
        ]);
    }

    public function update(OfficeLocationRequest $request, OfficeLocation $officeLocation): RedirectResponse
    {
        $this->authorize('viewAttendance');

        $officeLocation->update($request->validated());

        return redirect()
            ->route('admin.attendance.offices.index')
            ->with('success', 'Office location updated.');
    }

    public function deactivate(OfficeLocation $officeLocation): RedirectResponse
    {
        $this->authorize('viewAttendance');

        $officeLocation->update(['is_active' => false]);

        return back()->with('success', 'Office location deactivated.');
    }

    public function destroy(OfficeLocation $officeLocation): RedirectResponse
    {
        $this->authorize('viewAttendance');

        $officeLocation->delete();

        return back()->with('success', 'Office location deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(OfficeLocation $office): array
    {
        return [
            'id' => $office->id,
            'name' => $office->name,
            'address' => $office->address,
            'latitude' => (float) $office->latitude,
            'longitude' => (float) $office->longitude,
            'allowed_gps_radius_meters' => $office->allowed_gps_radius_meters,
            'late_check_in_time' => substr((string) $office->late_check_in_time, 0, 5),
            'network_verification_enabled' => $office->network_verification_enabled,
            'authorized_public_ips' => $office->authorized_public_ips ?? [],
            'is_active' => $office->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formPayload(OfficeLocation $office): array
    {
        return [
            'id' => $office->id,
            'name' => $office->name,
            'address' => $office->address,
            'latitude' => (string) $office->latitude,
            'longitude' => (string) $office->longitude,
            'allowed_gps_radius_meters' => (string) $office->allowed_gps_radius_meters,
            'late_check_in_time' => substr((string) $office->late_check_in_time, 0, 5),
            'network_verification_enabled' => $office->network_verification_enabled,
            'authorized_public_ips_text' => implode("\n", $office->authorized_public_ips ?? []),
            'is_active' => $office->is_active,
        ];
    }
}
