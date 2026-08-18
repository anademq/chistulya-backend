<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PetItemCategory;
use Illuminate\Database\Seeder;

class PetItemCategorySeeder extends Seeder
{
    /**
     * Seed the pet item (wardrobe) categories.
     *
     * @var list<array{slug:string,title:string}>
     */
    private const CATEGORIES = [
        ['slug' => 'items', 'title' => 'Предметы'],
        ['slug' => 'backgrounds', 'title' => 'Фоны'],
        ['slug' => 'stickers', 'title' => 'Стикеры'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $index => $category) {
            PetItemCategory::updateOrCreate(
                ['slug' => $category['slug']],
                ['title' => $category['title'], 'order_column' => $index],
            );
        }
    }
}
