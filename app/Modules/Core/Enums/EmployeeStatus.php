<?php

namespace App\Modules\Core\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Suspended = 'suspended';
    case Exited = 'exited';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnLeave => 'On leave',
            self::Suspended => 'Suspended',
            self::Exited => 'Exited',
        };
    }

    /**
     * Whether work may be routed to this employee. Task Management reads this
     * when building the assignable pool.
     */
    public function isAssignable(): bool
    {
        return $this === self::Active;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
