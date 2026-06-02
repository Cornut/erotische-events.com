<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin || $user->is($model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin;
    }
}
