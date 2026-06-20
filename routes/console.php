<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Refresh each organizer's cached .ics / listing URLs weekly (cheap, AI-free unless
// an Anthropic key is set), then scrape daily off those cached URLs + auto-discovery.
Schedule::command('organizers:discover-urls')->weekly();
Schedule::command('events:scrape')->daily();
