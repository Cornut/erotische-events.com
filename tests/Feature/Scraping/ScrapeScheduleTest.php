<?php

use Illuminate\Console\Scheduling\Schedule;

it('schedules the events:scrape command daily', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains($e->command ?? '', 'events:scrape'));

    expect($events)->not->toBeEmpty();
});

it('schedules weekly url discovery so cached feeds stay fresh', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains($e->command ?? '', 'organizers:discover-urls'));

    expect($events)->not->toBeEmpty();
});
