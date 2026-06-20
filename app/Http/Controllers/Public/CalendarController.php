<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $input = $request->string('month')->toString();

        try {
            $start = $input !== ''
                ? Carbon::createFromFormat('Y-m', $input)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Throwable) {
            $start = Carbon::now()->startOfMonth();
        }

        $end = $start->copy()->endOfMonth();

        $events = Event::published()
            ->whereBetween('start_date', [$start, $end->copy()->endOfDay()])
            ->orderBy('start_date')
            ->get(['id', 'slug', 'title', 'start_date']);

        return Inertia::render('Public/Calendar', [
            'month' => $start->format('Y-m'),
            'monthLabel' => $start->locale('de')->translatedFormat('F Y'),
            'prevMonth' => $start->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $start->copy()->addMonthNoOverflow()->format('Y-m'),
            'events' => $events->map(fn (Event $event) => [
                'id' => $event->id,
                'slug' => $event->slug,
                'title' => $event->title,
                'date' => $event->start_date->format('Y-m-d'),
                'time' => $event->start_date->format('H:i'),
            ]),
        ]);
    }
}
