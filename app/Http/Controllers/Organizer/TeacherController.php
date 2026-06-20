<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Teachers are a SHARED pool (deduped globally). Organizers can browse and add
 * new ones here; they then attach them to their events on the event form.
 */
class TeacherController extends Controller
{
    public function index(Request $request): Response
    {
        $term = trim($request->string('q')->toString());

        $teachers = Teacher::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'bio']);

        return Inertia::render('Organizer/Teachers/Index', [
            'teachers' => $teachers,
            'filters' => ['q' => $term],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);

        // Shared pool: dedupe by slug so the same person isn't created twice.
        Teacher::firstOrCreate(
            ['slug' => Str::slug($data['name'])],
            ['name' => $data['name'], 'bio' => $data['bio'] ?? null],
        );

        return back();
    }
}
