<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChallengeCategory;
use Illuminate\Database\Seeder;

class ChallengeCategorySeeder extends Seeder
{
    /**
     * Seed the challenge categories.
     *
     * @var list<array{slug:string,title:string}>
     */
    private const CATEGORIES = [
        ['slug' => 'hygiene', 'title' => 'Гигиена'],
        ['slug' => 'order', 'title' => 'Порядок'],
        ['slug' => 'food', 'title' => 'Еда'],
        ['slug' => 'study', 'title' => 'Учеба'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $index => $category) {
            ChallengeCategory::updateOrCreate(
                ['slug' => $category['slug']],
                ['title' => $category['title'], 'order_column' => $index],
            );
        }
    }
}
