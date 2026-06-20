<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets an organizer edit their own master data (Stammdaten). Sensitive fields
 * (owner, slug, verification_status, scrape settings) are intentionally not
 * editable here — those stay with the admin.
 */
class OrganizerProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $organizer = $request->user()->organizer;

        return Inertia::render('Organizer/Profile/Edit', [
            'organizer' => [
                'id' => $organizer->id,
                'company_name' => $organizer->company_name,
                'legal_name' => $organizer->legal_name,
                'contact_name' => $organizer->contact_name,
                'email' => $organizer->email,
                'phone' => $organizer->phone,
                'website' => $organizer->website,
                'description' => $organizer->description,
                'street' => $organizer->street,
                'postal_code' => $organizer->postal_code,
                'city' => $organizer->city,
                'country' => $organizer->country,
                'vat_id' => $organizer->vat_id,
                'slug' => $organizer->slug,
                'verification_status' => $organizer->verification_status,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $organizer = $request->user()->organizer;

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'street' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'vat_id' => ['nullable', 'string', 'max:50'],
        ]);

        $organizer->update($data);

        return back();
    }
}
