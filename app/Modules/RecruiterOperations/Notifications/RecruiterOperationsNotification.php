<?php

namespace App\Modules\RecruiterOperations\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Recruiter Operations staff alert. The database row is the source of truth;
 * RecruiterNotifier adds a best-effort broadcast on the recipient's existing
 * staff.user.{id} channel. The same id is used for both so the frontend can
 * dedupe.
 */
class RecruiterOperationsNotification extends Notification
{
    use Queueable;

    /**
     * Besides the fields below, any `recruiter_*_id` key (such as
     * recruiter_training_assignment_id) is stored as a record reference.
     *
     * @param  array{event: string, title: string, body: string, url: string, recruiter_task_id?: int|null, actor?: array{id: int, name: string, avatar: string|null}|null}&array<string, mixed>  $payload
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
        $references = array_filter(
            $this->payload,
            fn ($value, string $key) => preg_match('/^recruiter_[a-z_]+_id$/', $key) === 1 && (is_int($value) || $value === null),
            ARRAY_FILTER_USE_BOTH,
        );

        return [
            'event' => $this->payload['event'],
            'title' => $this->payload['title'],
            'body' => $this->payload['body'],
            'url' => $this->payload['url'],
            'recruiter_task_id' => $this->payload['recruiter_task_id'] ?? null,
            ...$references,
            'actor' => $this->payload['actor'] ?? null,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
