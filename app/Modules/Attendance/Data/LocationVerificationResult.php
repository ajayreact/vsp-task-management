<?php

namespace App\Modules\Attendance\Data;

use App\Modules\Attendance\Enums\AttendanceAction;

final class LocationVerificationResult
{
    public function __construct(
        public readonly bool $passed,
        public readonly string $message,
        public readonly AttendanceAction $action,
        public readonly float $distanceMeters,
        public readonly int $allowedRadiusMeters,
        public readonly ?int $officeId,
        public readonly ?string $officeName,
        public readonly bool $networkVerificationRequired = false,
        public readonly ?bool $networkVerified = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'message' => $this->message,
            'action' => $this->action->value,
            'distance_meters' => round($this->distanceMeters, 2),
            'allowed_radius_meters' => $this->allowedRadiusMeters,
            'network_verification_required' => $this->networkVerificationRequired,
            'network_verified' => $this->networkVerified,
            'office' => $this->officeId === null ? null : [
                'id' => $this->officeId,
                'name' => $this->officeName,
            ],
        ];
    }
}
