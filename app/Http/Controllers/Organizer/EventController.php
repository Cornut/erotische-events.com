<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Teacher;
use App\Services\EventPublishingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        return Inertia::render('Organizer/Events/Index', [
            'events' => $request->user()->organizer->events()
                ->with('venue:id,name')
                ->orderByDesc('start_date')
                ->get(['id', 'title', 'status', 'start_date', 'venue_id']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Organizer/Events/Create', [
            'options' => $this->formOptions($request->user()->organizer),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organizer = $request->user()->organizer;
        $data = $this->validateEvent($request, $organizer);

        $event = $organizer->events()->create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'short_description' => $data['short_description'] ?? null,
            'long_description' => $data['long_description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'booking_url' => $data['booking_url'],
            'venue_id' => $data['venue_id'] ?? null,
            'currency' => $data['price_currency'] ?? 'EUR',
            'status' => EventStatus::Draft,
        ]);

        $this->syncRelations($event, $data);

        return redirect()->route('organizer.events.index');
    }

    public function edit(Request $request, Event $event): Response
    {
        $this->authorize('update', $event);
        $event->load(['categories:id,slug', 'teachers:id', 'prices']);

        return Inertia::render('Organizer/Events/Edit', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'short_description' => $event->short_description,
                'long_description' => $event->long_description,
                'start_date' => optional($event->start_date)->format('Y-m-d\TH:i'),
                'end_date' => optional($event->end_date)->format('Y-m-d\TH:i'),
                'booking_url' => $event->booking_url,
                'venue_id' => $event->venue_id,
                'status' => $event->status->value,
                'categories' => $event->categories->pluck('slug')->all(),
                'teachers' => $event->teachers->pluck('id')->all(),
                'price_amount' => optional($event->prices->first(fn ($p) => $p->type->value === 'regular'))->amount,
                'price_currency' => $event->currency ?? 'EUR',
            ],
            'options' => $this->formOptions($request->user()->organizer),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);
        $data = $this->validateEvent($request, $request->user()->organizer);

        $event->update([
            'title' => $data['title'],
            'short_description' => $data['short_description'] ?? null,
            'long_description' => $data['long_description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'booking_url' => $data['booking_url'],
            'venue_id' => $data['venue_id'] ?? null,
            'currency' => $data['price_currency'] ?? 'EUR',
        ]);

        $this->syncRelations($event, $data);

        return redirect()->route('organizer.events.index');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);
        $event->delete();

        return back();
    }

    public function submit(Event $event, EventPublishingService $service): RedirectResponse
    {
        $this->authorize('update', $event);
        $service->submit($event);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEvent(Request $request, Organizer $organizer): array
    {
        $ownVenueIds = $organizer->venues()->pluck('id')->all();

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'long_description' => ['nullable', 'string', 'max:10000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'booking_url' => ['required', 'url', 'max:255'],
            'venue_id' => ['nullable', Rule::in($ownVenueIds)],
            'categories' => ['nullable', 'array'],
            'categories.*' => [Rule::exists('categories', 'slug')],
            'teachers' => ['nullable', 'array'],
            'teachers.*' => [Rule::exists('teachers', 'id')],
            'price_amount' => ['nullable', 'numeric', 'min:0'],
            'price_currency' => ['nullable', 'string', 'size:3'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncRelations(Event $event, array $data): void
    {
        $categoryIds = Category::whereIn('slug', $data['categories'] ?? [])->pluck('id');
        $event->categories()->sync($categoryIds);
        $event->teachers()->sync($data['teachers'] ?? []);

        // Single "regular" price managed by the organizer.
        $event->prices()->where('type', 'regular')->delete();
        if (! empty($data['price_amount'])) {
            $event->prices()->create([
                'type' => 'regular',
                'amount' => $data['price_amount'],
                'currency' => $data['price_currency'] ?? 'EUR',
            ]);
        }
    }

    /**
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function formOptions(Organizer $organizer): array
    {
        return [
            'venues' => $organizer->venues()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name_de')->get(['slug', 'name_de']),
            'teachers' => Teacher::orderBy('name')->get(['id', 'name']),
        ];
    }
}
