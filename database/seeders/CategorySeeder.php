<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $top = [
            ['slug' => 'tantra', 'name_de' => 'Tantra', 'name_en' => 'Tantra'],
            ['slug' => 'conscious-relating', 'name_de' => 'Conscious Relating', 'name_en' => 'Conscious Relating'],
            ['slug' => 'sacred-sexuality', 'name_de' => 'Sakrale Sexualität', 'name_en' => 'Sacred Sexuality'],
            ['slug' => 'sex-positive', 'name_de' => 'Sex Positive', 'name_en' => 'Sex Positive'],
            ['slug' => 'retreat', 'name_de' => 'Retreat', 'name_en' => 'Retreat'],
            ['slug' => 'festival', 'name_de' => 'Festival', 'name_en' => 'Festival'],
            ['slug' => 'workshop', 'name_de' => 'Workshop', 'name_en' => 'Workshop'],
            ['slug' => 'bodywork', 'name_de' => 'Körperarbeit', 'name_en' => 'Bodywork'],
            ['slug' => 'mens-work', 'name_de' => 'Männerarbeit', 'name_en' => "Men's Work"],
            ['slug' => 'womens-work', 'name_de' => 'Frauenarbeit', 'name_en' => "Women's Work"],
            ['slug' => 'lgbtq', 'name_de' => 'LGBTQ+', 'name_en' => 'LGBTQ+'],
            ['slug' => 'bdsm', 'name_de' => 'BDSM', 'name_en' => 'BDSM'],
        ];

        foreach ($top as $i => $data) {
            Category::updateOrCreate(['slug' => $data['slug']], $data + ['position' => $i + 1]);
        }

        $bdsm = Category::where('slug', 'bdsm')->firstOrFail();
        foreach ([['slug' => 'shibari', 'name_de' => 'Shibari', 'name_en' => 'Shibari'], ['slug' => 'kink', 'name_de' => 'Kink', 'name_en' => 'Kink']] as $i => $child) {
            Category::updateOrCreate(['slug' => $child['slug']], $child + ['parent_id' => $bdsm->id, 'position' => $i + 1]);
        }
    }
}
