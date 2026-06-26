<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Broadcast;

/*
 * Private channel for each child's real-time notifications.
 *
 * Frontend:    Echo.private(`child.${childId}`).listen('.reminder.notification', cb)
 * Auth:        POST /broadcasting/auth  (protected by auth.jwt middleware)
 */
Broadcast::channel('child.{childId}', function (User $user, string $childId): bool {
    return $user->id === $childId;
});
