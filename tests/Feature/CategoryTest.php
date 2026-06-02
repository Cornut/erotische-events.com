<?php

use App\Models\Category;
use Database\Seeders\CategorySeeder;

it('creates a parent category with children', function () {
    $bdsm = Category::create(['slug' => 'bdsm', 'name_de' => 'BDSM', 'name_en' => 'BDSM', 'position' => 12]);
    $shibari = Category::create(['slug' => 'shibari', 'name_de' => 'Shibari', 'name_en' => 'Shibari', 'parent_id' => $bdsm->id, 'position' => 1]);

    expect($shibari->parent->is($bdsm))->toBeTrue()
        ->and($bdsm->children->pluck('slug')->all())->toContain('shibari');
});

it('seeds the full taxonomy with bdsm as parent of shibari and kink', function () {
    $this->seed(CategorySeeder::class);

    expect(Category::whereNull('parent_id')->count())->toBe(12);
    $bdsm = Category::where('slug', 'bdsm')->firstOrFail();
    expect(Category::where('parent_id', $bdsm->id)->pluck('slug')->all())
        ->toEqualCanonicalizing(['shibari', 'kink']);
    expect(Category::where('slug', 'tantra')->exists())->toBeTrue();
});
