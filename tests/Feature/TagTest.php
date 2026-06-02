<?php

use App\Models\Tag;

it('creates a tag with a unique slug', function () {
    $tag = Tag::create(['name' => 'Beginner Friendly', 'slug' => 'beginner-friendly']);
    expect($tag->slug)->toBe('beginner-friendly');
});
