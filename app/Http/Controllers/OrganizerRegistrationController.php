<?php

namespace App\Http\Controllers;

use App\Enums\OrganizerVerificationStatus;
use App\Enums\UserRole;
use App\Models\Organizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizerRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Organizer/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url'],
        ]);

        $user = $request->user();

        Organizer::create([
            'owner_user_id' => $user->id,
            'company_name' => $data['company_name'],
            'contact_name' => $data['contact_name'] ?? null,
            'website' => $data['website'] ?? null,
            'slug' => Str::slug($data['company_name']).'-'.Str::lower(Str::random(6)),
            'verification_status' => OrganizerVerificationStatus::Pending,
        ]);

        if ($user->role === UserRole::User) {
            $user->update(['role' => UserRole::Organizer]);
        }

        return redirect()->route('organizer.dashboard');
    }
}
