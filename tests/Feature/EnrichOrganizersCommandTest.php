<?php

use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('applies scanned Stammdaten from a json file via the command', function () {
    Storage::fake('public');
    Http::fake(['*' => Http::response('IMG', 200)]);

    $organizer = Organizer::factory()->create(['slug' => 'demo-net']);

    $file = tempnam(sys_get_temp_dir(), 'enrich').'.json';
    file_put_contents($file, json_encode([[
        'slug' => 'demo-net',
        'legal_name' => 'Demo GmbH',
        'email' => 'hi@demo.net',
        'street' => 'Hauptstr. 3',
        'postal_code' => '50667',
        'city' => 'Köln',
        'country' => 'DE',
        'logo_url' => 'https://demo.net/logo.png',
    ]]));

    $this->artisan('organizers:enrich', ['file' => $file])->assertSuccessful();

    $organizer->refresh();
    expect($organizer->legal_name)->toBe('Demo GmbH')
        ->and($organizer->city)->toBe('Köln')
        ->and($organizer->logo)->toBe('organizers/demo-net/logo.png');
    expect(Venue::where('slug', 'demo-net-hauptstandort')->exists())->toBeTrue();

    @unlink($file);
});
