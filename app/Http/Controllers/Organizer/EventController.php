<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EventPublishingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use AuthorizesRequests;

    public function create(): Response
    {
        return Inertia::render('Organizer/Events/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $organizer = $request->user()->organizer;
        abort_if($organizer === null, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'booking_url' => ['required', 'url'],
            'start_date' => ['required', 'date'],
            'short_description' => ['nullable', 'string', 'max:255'],
        ]);

        $organizer->events()->create([
            ...$data,
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'status' => EventStatus::Draft,
        ]);

        return redirect()->route('organizer.dashboard');
    }

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);

        return Inertia::render('Organizer/Events/Edit', [
            'event' => $event,
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'booking_url' => ['required', 'url'],
            'start_date' => ['required', 'date'],
            'short_description' => ['nullable', 'string', 'max:255'],
        ]);

        $event->update($data);

        return redirect()->route('organizer.dashboard');
    }

    public function submit(Event $event, EventPublishingService $service): RedirectResponse
    {
        $this->authorize('update', $event);
        $service->submit($event);

        return back();
    }
}
