<?php

namespace App\Modules\Attendance\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceDatabaseNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{event: string, title: string, body: string, url: string, actor?: array{id: int, name: string, avatar: string|null}|null}  $payload
     */
    public function __construct(public array $payload) {}

    /**
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
            'actor' => $this->payload['actor'] ?? null,
        ];
    }
}
