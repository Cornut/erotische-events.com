<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function index(Request $request): Response
    {
        $term = trim($request->string('q')->toString());

        $teachers = Teacher::query()
            ->select(['id', 'slug', 'name', 'bio'])
            ->whereHas('events', fn ($query) => $query->published())
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', '%'.$term.'%')
                        ->orWhere('bio', 'like', '%'.$term.'%');
                });
            })
            ->withCount(['events' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->get();

        return Inertia::render('Public/Teachers/Index', [
            'teachers' => $teachers,
            'filters' => [
                'q' => $term,
            ],
        ]);
    }

    public function show(string $slug): Response
    {
        $teacher = Teacher::where('slug', $slug)->firstOrFail();

        $teacher->load([
            'events' => fn ($query) => $query->published()
                ->orderBy('start_date')
                ->with(['organizer', 'venue']),
        ]);

        return Inertia::render('Public/Teachers/Show', [
            'teacher' => $teacher,
        ]);
    }
}
