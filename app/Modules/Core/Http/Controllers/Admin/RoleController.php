<?php

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Http\Requests\Admin\RoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => SystemRole::tryFrom($role->name)?->label() ?? $role->name,
                'is_system' => SystemRole::tryFrom($role->name) !== null,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions_count,
                'can_update' => $request->user()?->can('update', $role) ?? false,
                'can_delete' => $request->user()?->can('delete', $role) ?? false,
            ]);

        return Inertia::render('Core/admin/roles/index', [
            'roles' => $roles,
            'can' => [
                'manage' => $request->user()?->can('create', Role::class) ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('Core/admin/roles/create', [
            'abilities' => $this->abilityGroups(),
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return to_route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        return Inertia::render('Core/admin/roles/edit', [
            'abilities' => $this->abilityGroups(),
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => SystemRole::tryFrom($role->name)?->label() ?? $role->name,
                'is_system' => SystemRole::tryFrom($role->name) !== null,
                'permissions' => $role->permissions->pluck('name')->all(),
            ],
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        if ($request->filled('name')) {
            $role->update(['name' => $request->validated('name')]);
        }

        $role->syncPermissions($request->validated('permissions', []));

        return to_route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();

        return to_route('admin.roles.index')->with('success', 'Role deleted.');
    }

    /**
     * Abilities keyed by the area they belong to, so the editor can render one
     * block per area instead of a flat list of ten checkboxes.
     *
     * @return array<int, array{group: string, abilities: array<int, array{value: string, label: string}>}>
     */
    protected function abilityGroups(): array
    {
        $grouped = [];

        foreach (Ability::cases() as $ability) {
            $grouped[$ability->group()][] = [
                'value' => $ability->value,
                'label' => $ability->label(),
            ];
        }

        return array_map(
            fn (string $group, array $abilities) => ['group' => $group, 'abilities' => $abilities],
            array_keys($grouped),
            $grouped,
        );
    }
}
