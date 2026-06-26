<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the child's private channel when a reminder notification fires.
 *
 * Frontend listens on:  Echo.private(`child.${childId}`).listen('.reminder.notification', ...)
 */
class ReminderNotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $notificationId,
        public readonly string $reminderId,
        public readonly string $childId,
        public readonly string $title,
        public readonly ?string $shortDescription,
        public readonly ?string $description,
        public readonly string $time,
        public readonly ?string $date,
        public readonly string $repeatingPattern,
        public readonly ?string $repeatingDays,
        public readonly string $scope,
        public readonly string $sentAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("child.{$this->childId}"),
        ];
    }

    /** Custom event name with a leading dot so Laravel Echo can listen with `.reminder.notification` */
    public function broadcastAs(): string
    {
        return 'reminder.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notificationId,
            'reminder_id' => $this->reminderId,
            'title' => $this->title,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'time' => $this->time,
            'date' => $this->date,
            'repeating_pattern' => $this->repeatingPattern,
            'repeating_days' => $this->repeatingDays,
            'scope' => $this->scope,
            'sent_at' => $this->sentAt,
        ];
    }
}
