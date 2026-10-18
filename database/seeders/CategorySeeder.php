<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                'name' => 'Elektronik',
                'slug' => 'elektronik',
                'icon' => 'laptop',
            ],
            [
                'name' => 'Kamera & Fotografi',
                'slug' => 'kamera-fotografi',
                'icon' => 'camera',
            ],
            [
                'name' => 'Pakaian Formal',
                'slug' => 'pakaian-formal',
                'icon' => 'shirt',
            ],
            [
                'name' => 'Outdoor & Camping',
                'slug' => 'outdoor-camping',
                'icon' => 'campground',
            ],
            [
                'name' => 'Audio & Sound',
                'slug' => 'audio-sound',
                'icon' => 'headphones',
            ],
            [
                'name' => 'Olahraga',
                'slug' => 'olahraga',
                'icon' => 'futbol',
            ],
            [
                'name' => 'Buku & Alat Tulis',
                'slug' => 'buku-alat-tulis',
                'icon' => 'book',
            ],
            [
                'name' => 'Lainnya',
                'slug' => 'lainnya',
                'icon' => 'boxes-stacked',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
