<?php

use App\Enums\OrganizerVerificationStatus;
use App\Models\Event;
use App\Models\Organizer;
use App\Scraping\EventScraperService;

it('scrapes only the given organizer and skips rejected ones', function () {
    $target = Organizer::factory()->approved()->create(['slug' => 'target-org']);
    Organizer::factory()->create(['verification_status' => OrganizerVerificationStatus::Rejected]);

    // Swap the service for a mock that creates a marker event for whatever organizer it is given.
    $mock = Mockery::mock(EventScraperService::class);
    $mock->shouldReceive('scrape')->andReturnUsing(function (Organizer $o) {
        Event::factory()->create(['organizer_id' => $o->id, 'source_url' => 'https://t/'.$o->id]);

        return ['created' => 1, 'updated' => 0, 'url' => 'https://t'];
    });
    $this->app->instance(EventScraperService::class, $mock);

    $this->artisan('events:scrape', ['--organizer' => 'target-org'])->assertSuccessful();

    expect(Event::where('organizer_id', $target->id)->count())->toBe(1)
        ->and(Event::count())->toBe(1);
});
