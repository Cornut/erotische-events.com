<?php

namespace App\Filament\Resources\Users\Concerns;

use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;

/**
 * Persists the virtual `organizer_id` field from the user form to
 * organizers.owner_user_id.
 *
 * organizers.owner_user_id is NOT NULL: every organizer must have an owner.
 * Unclaimed (e.g. scraped) organizers are parked under an admin account, so
 * "releasing" an organizer re-parks it there rather than nulling the column.
 *
 * Only non-admin users are touched: an admin is the parking account and may own
 * many organizers, so we never bulk-reassign their organizers away.
 */
trait SyncsOrganizerOwnership
{
    protected function syncOrganizerOwnership(): void
    {
        /** @var User $user */
        $user = $this->record;

        // The parking account holds unclaimed organizers; never rewrite its holdings.
        if ($user->role === UserRole::Admin) {
            return;
        }

        $organizerId = $this->data['organizer_id'] ?? null;

        $parkingOwnerId = User::query()
            ->where('role', UserRole::Admin)
            ->orderBy('id')
            ->value('id');

        // Re-park any organizer this user managed but is no longer assigned.
        if ($parkingOwnerId !== null) {
            Organizer::query()
                ->where('owner_user_id', $user->getKey())
                ->when($organizerId, fn ($query) => $query->whereKeyNot($organizerId))
                ->update(['owner_user_id' => $parkingOwnerId]);
        }

        if ($organizerId) {
            Organizer::whereKey($organizerId)->update(['owner_user_id' => $user->getKey()]);
        }
    }
}
