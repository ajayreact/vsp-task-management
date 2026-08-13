<?php

namespace App\Modules\TaskManagement\Enums;

/**
 * A person's part on one project. Unrelated to the system roles in
 * `App\Modules\Core\Enums\SystemRole`, which grant permissions; this only
 * describes involvement.
 */
enum ProjectRole: string
{
    case Lead = 'lead';
    case Member = 'member';
    case Reviewer = 'reviewer';
    case Observer = 'observer';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead',
            self::Member => 'Member',
            self::Reviewer => 'Reviewer',
            self::Observer => 'Observer',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role) => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
