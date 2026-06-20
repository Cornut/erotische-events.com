<?php

namespace App\Http\Controllers\Public;

use App\Enums\OrganizerVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Organizer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizerController extends Controller
{
    public function index(Request $request): Response
    {
        $term = trim($request->string('q')->toString());

        $organizers = Organizer::query()
            ->select(['id', 'slug', 'company_name', 'description'])
            ->where('verification_status', OrganizerVerificationStatus::Approved)
            ->whereHas('events', fn ($query) => $query->published())
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('company_name', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%');
                });
            })
            ->withCount(['events' => fn ($query) => $query->published()])
            ->orderBy('company_name')
            ->get();

        return Inertia::render('Public/Organizers/Index', [
            'organizers' => $organizers,
            'filters' => [
                'q' => $term,
            ],
        ]);
    }

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
