<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast channels
|--------------------------------------------------------------------------
|
| Private user channel for Phase 2 notification delivery. Only the owning
| user may subscribe.
|
*/

Broadcast::channel('staff.user.{id}', function ($user, int|string $id): bool {
    return (int) $user->id === (int) $id;
});
