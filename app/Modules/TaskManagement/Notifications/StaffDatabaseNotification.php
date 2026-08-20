<?php

namespace App\Modules\TaskManagement\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Staff alert: database is the source of truth; broadcast pushes a live
 * update to the recipient's private channel (Phase 2). Same UUID is used
 * for both channels so the frontend can dedupe.
 */
class StaffDatabaseNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{event: string, title: string, body: string, url: string, task_id?: int|null, timesheet_id?: int|null}  $payload
     */
    public function __construct(public array $payload) {}

    /**
     * Database is always persisted. Broadcast is added by TaskNotifier when Reverb is configured.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->payload['event'],
            'title' => $this->payload['title'],
            'body' => $this->payload['body'],
            'url' => $this->payload['url'],
            'task_id' => $this->payload['task_id'] ?? null,
            'timesheet_id' => $this->payload['timesheet_id'] ?? null,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
