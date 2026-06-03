<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizer = $user->organizer;

        $events = $organizer
            ? $organizer->events()->with('venue')->latest()->get()
            : collect();

        $venues = $organizer
            ? $organizer->venues()->latest()->get()
            : collect();

        return Inertia::render('Organizer/Dashboard', [
            'organizer' => $organizer,
            'events' => $events,
            'venues' => $venues,
        ]);
    }
}
