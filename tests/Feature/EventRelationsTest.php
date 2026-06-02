<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Tag;
use App\Models\Teacher;

it('attaches categories, tags and teachers to an event', function () {
    $event = Event::factory()->create();
    $category = Category::create(['slug' => 'tantra', 'name_de' => 'Tantra', 'name_en' => 'Tantra']);
    $tag = Tag::create(['name' => 'Couples', 'slug' => 'couples']);
    $teacher = Teacher::factory()->create();

    $event->categories()->attach($category);
    $event->tags()->attach($tag);
    $event->teachers()->attach($teacher);

    expect($event->categories)->toHaveCount(1)
        ->and($event->tags)->toHaveCount(1)
        ->and($event->teachers)->toHaveCount(1)
        ->and($category->events)->toHaveCount(1);
});
