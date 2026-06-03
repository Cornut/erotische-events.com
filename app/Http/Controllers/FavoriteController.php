<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    public function index(Request $request): Response
    {
        $favorites = $request->user()
            ->favorites()
            ->with('organizer')
            ->orderBy('start_date')
            ->get();

        return Inertia::render('Favorites/Index', [
            'favorites' => $favorites,
        ]);
    }

    public function toggle(Request $request, Event $event): RedirectResponse
    {
        $request->user()->favorites()->toggle($event->id);

        return back();
    }
}
