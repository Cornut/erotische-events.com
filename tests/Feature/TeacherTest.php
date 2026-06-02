<?php

use App\Models\Teacher;

it('creates a teacher with links as array', function () {
    $teacher = Teacher::factory()->create(['links' => ['https://example.com']]);
    expect($teacher->links)->toBeArray()->toContain('https://example.com');
});
