<?php

namespace App\Services;

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;

class OrganizerApprovalService
{
    public function approve(Organizer $organizer): Organizer
    {
        $organizer->update(['verification_status' => OrganizerVerificationStatus::Approved]);

        return $organizer;
    }

    public function reject(Organizer $organizer): Organizer
    {
        $organizer->update(['verification_status' => OrganizerVerificationStatus::Rejected]);

        return $organizer;
    }

    public function canPublish(Organizer $organizer): bool
    {
        return $organizer->verification_status === OrganizerVerificationStatus::Approved;
    }
}
