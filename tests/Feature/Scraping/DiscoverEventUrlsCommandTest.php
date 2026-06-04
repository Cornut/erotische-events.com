<?php

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use App\Scraping\UrlDiscoveryService;

it('stores discovered urls in scrape_urls for the targeted organizer', function () {
    $target = Organizer::factory()->approved()->create(['slug' => 'disc-org']);
    Organizer::factory()->create(['verification_status' => OrganizerVerificationStatus::Rejected]);

    $mock = Mockery::mock(UrlDiscoveryService::class);
    $mock->shouldReceive('discover')->andReturn([
        'https://disc-org.de/termine',
        'https://disc-org.de/cal.ics',
    ]);
    $this->app->instance(UrlDiscoveryService::class, $mock);

    $this->artisan('organizers:discover-urls', ['--organizer' => 'disc-org'])->assertSuccessful();

    expect($target->refresh()->scrapeUrls())->toBe([
        'https://disc-org.de/termine',
        'https://disc-org.de/cal.ics',
    ]);
});
