<?php

namespace App\Http\Controllers\Public;

use App\Enums\OrganizerVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Organizer;
use Inertia\Inertia;
use Inertia\Response;

class OrganizerController extends Controller
{
    public function show(string $slug): Response
    {
        $organizer = Organizer::where('slug', $slug)
            ->where('verification_status', OrganizerVerificationStatus::Approved)
            ->firstOrFail();

        $organizer->load(['events' => fn ($query) => $query->published()->orderBy('start_date'), 'venues']);

        return Inertia::render('Public/Organizers/Show', [
            'organizer' => $organizer,
        ]);
    }
}
