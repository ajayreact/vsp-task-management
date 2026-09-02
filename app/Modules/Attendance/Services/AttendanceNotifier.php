<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Models\WfhRequest;
use App\Modules\Attendance\Notifications\AttendanceDatabaseNotification;
use App\Modules\Core\Enums\UserType;
use App\Modules\Core\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AttendanceNotifier
{
    public function wfhApproved(WfhRequest $request, User $approver): void
    {
        $request->loadMissing('employee.user');
        $recipient = $request->employee->user;

        $this->deliver($recipient, [
            'event' => 'attendance.wfh.approved',
            'title' => 'WFH request approved',
            'body' => 'Your work from home request for '.$request->date->format('M j, Y').' has been approved.',
            'url' => '/attendance/mark',
            'actor' => $this->actorPayload($approver),
        ]);
    }

    public function wfhRejected(WfhRequest $request, User $approver): void
    {
        $request->loadMissing('employee.user');
        $recipient = $request->employee->user;

        $this->deliver($recipient, [
            'event' => 'attendance.wfh.rejected',
            'title' => 'WFH request rejected',
            'body' => 'Your work from home request for '.$request->date->format('M j, Y').' was rejected.',
            'url' => '/attendance/wfh',
            'actor' => $this->actorPayload($approver),
        ]);
    }

    /**
     * @param  array{event: string, title: string, body: string, url: string, actor?: array{id: int, name: string, avatar: string|null}|null}  $payload
     */
    protected function deliver(User $recipient, array $payload): void
    {
        if (! $recipient->is_active || $recipient->user_type !== UserType::Internal) {
            return;
        }

        try {
            Notification::sendNow($recipient, new AttendanceDatabaseNotification($payload), ['database']);
        } catch (\Throwable $exception) {
            report($exception);
            Log::warning('Attendance notification delivery failed.', [
                'recipient_id' => $recipient->id,
                'event' => $payload['event'] ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array{id: int, name: string, avatar: string|null}
     */
    protected function actorPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->employee?->getFirstMediaUrl('avatar', 'thumb') ?: null,
        ];
    }
}
