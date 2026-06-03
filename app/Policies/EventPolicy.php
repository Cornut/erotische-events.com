<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function update(User $user, Event $event): bool
    {
        return $user->role === UserRole::Admin
            || ($event->organizer !== null && $event->organizer->owner_user_id === $user->id);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}
