<?php

namespace App\Services;

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Applies the result of an Impressum scan to an organizer:
 *  - fills the organizer's Stammdaten (legal name, address, contact, VAT id),
 *  - creates/updates the organizer's primary venue from that same address,
 *  - downloads the logo into the organizer's image directory.
 *
 * The web scan/extraction itself happens upstream (subagent / command); this
 * service only applies already-extracted, structured data so it is fully testable.
 */
class ImpressumImportService
{
    /**
     * @param  array<string, mixed>  $data  Keys: legal_name, contact_name, email, phone,
     *                                      street, postal_code, city, country, vat_id, impressum_url
     */
    /**
     * Mark an organizer as rejected — used when its website / Impressum URL is
     * unreachable during the scan.
     */
    public function reject(Organizer $organizer): Organizer
    {
        $organizer->update(['verification_status' => OrganizerVerificationStatus::Rejected]);

        return $organizer;
    }

    public function apply(Organizer $organizer, array $data): Organizer
    {
        $fields = collect([
            'legal_name', 'contact_name', 'email', 'phone',
            'street', 'postal_code', 'city', 'country', 'vat_id', 'impressum_url',
        ])->mapWithKeys(fn (string $key) => [$key => $data[$key] ?? null])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if ($fields !== []) {
            $organizer->fill($fields)->save();
        }

        $this->syncPrimaryVenue($organizer);

        return $organizer->refresh();
    }

    /**
     * The organizer's address is also its first ("primary") venue. Idempotent.
     */
    public function syncPrimaryVenue(Organizer $organizer): ?Venue
    {
        if ($organizer->street === null && $organizer->city === null) {
            return null;
        }

        return Venue::updateOrCreate(
            ['slug' => $organizer->slug.'-hauptstandort'],
            [
                'organizer_id' => $organizer->id,
                'name' => $organizer->company_name,
                'street' => $organizer->street,
                'postal_code' => $organizer->postal_code,
                'city' => $organizer->city,
                'country' => $organizer->country,
            ],
        );
    }

    /**
     * Download a logo into organizers/{slug}/logo.{ext} on the public disk and
     * set it on the organizer. Returns the stored relative path, or null on failure.
     */
    public function storeLogoFromUrl(Organizer $organizer, string $url): ?string
    {
        try {
            $response = Http::timeout(20)->get($url);
            if (! $response->successful()) {
                return null;
            }

            $extension = match (true) {
                str_contains($url, '.svg') => 'svg',
                str_contains($url, '.webp') => 'webp',
                str_contains($url, '.jpg'), str_contains($url, '.jpeg') => 'jpg',
                default => 'png',
            };

            $path = $organizer->imageDirectory().'/logo.'.$extension;
            Storage::disk('public')->put($path, $response->body());

            $organizer->update(['logo' => $path]);

            return $path;
        } catch (Throwable) {
            return null;
        }
    }
}
