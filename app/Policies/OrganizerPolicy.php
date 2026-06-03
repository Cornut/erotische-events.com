<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;

class OrganizerPolicy
{
    public function update(User $user, Organizer $organizer): bool
    {
        return $user->role === UserRole::Admin || $organizer->owner_user_id === $user->id;
    }

    public function delete(User $user, Organizer $organizer): bool
    {
        return $user->role === UserRole::Admin;
    }
}
