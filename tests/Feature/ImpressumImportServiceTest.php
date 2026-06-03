<?php

use App\Models\Organizer;
use App\Models\Venue;
use App\Services\ImpressumImportService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->service = app(ImpressumImportService::class));

it('fills organizer Stammdaten and creates the primary venue from the address', function () {
    $organizer = Organizer::factory()->create(['slug' => 'no-guru-net', 'company_name' => 'No-Guru Institut Tantra']);

    $this->service->apply($organizer, [
        'legal_name' => 'No-Guru Institut GmbH',
        'contact_name' => 'Max Muster',
        'email' => 'info@no-guru.net',
        'phone' => '+49 40 1234567',
        'street' => 'Beispielstraße 1',
        'postal_code' => '20095',
        'city' => 'Hamburg',
        'country' => 'DE',
        'vat_id' => 'DE123456789',
    ]);

    $organizer->refresh();
    expect($organizer->legal_name)->toBe('No-Guru Institut GmbH')
        ->and($organizer->city)->toBe('Hamburg')
        ->and($organizer->vat_id)->toBe('DE123456789')
        ->and($organizer->email)->toBe('info@no-guru.net');

    $venue = Venue::where('slug', 'no-guru-net-hauptstandort')->firstOrFail();
    expect($venue->organizer_id)->toBe($organizer->id)
        ->and($venue->street)->toBe('Beispielstraße 1')
        ->and($venue->city)->toBe('Hamburg')
        ->and($venue->country)->toBe('DE');
});

it('does not create a venue when no address is present', function () {
    $organizer = Organizer::factory()->create();
    $this->service->apply($organizer, ['email' => 'x@example.com']);

    expect(Venue::count())->toBe(0);
});

it('is idempotent for the primary venue', function () {
    $organizer = Organizer::factory()->create(['slug' => 'demo-org']);
    $data = ['street' => 'Weg 2', 'postal_code' => '10115', 'city' => 'Berlin', 'country' => 'DE'];

    $this->service->apply($organizer, $data);
    $this->service->apply($organizer, $data);

    expect(Venue::where('slug', 'demo-org-hauptstandort')->count())->toBe(1);
});

it('downloads a logo into the organizer image directory', function () {
    Storage::fake('public');
    Http::fake(['*' => Http::response('PNGDATA', 200)]);

    $organizer = Organizer::factory()->create(['slug' => 'logo-org']);
    $path = $this->service->storeLogoFromUrl($organizer, 'https://logo-org.de/logo.png');

    expect($path)->toBe('organizers/logo-org/logo.png');
    Storage::disk('public')->assertExists('organizers/logo-org/logo.png');
    expect($organizer->refresh()->logo)->toBe('organizers/logo-org/logo.png');
});
