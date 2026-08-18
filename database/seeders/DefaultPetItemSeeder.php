<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PetItem;
use App\Models\PetItemCategory;
use Database\Seeders\Concerns\SeedsPlaceholderMedia;
use Illuminate\Database\Seeder;

class DefaultPetItemSeeder extends Seeder
{
    use SeedsPlaceholderMedia;

    /**
     * @var array<string, list<array{title:string,short_description:string,description:string,price:int}>>
     */
    private const CATEGORY_ITEMS = [
        'items' => [
            [
                'title' => 'Красная кепка',
                'short_description' => 'Стильный аксессуар для питомца',
                'description' => 'Яркая кепка для комнаты питомца — награда за старание и аккуратность.',
                'price' => 0,
            ],
            [
                'title' => 'Уютный шарф',
                'short_description' => 'Тёплый образ на каждый день',
                'description' => 'Мягкий шарф, который можно надеть на питомца и порадовать его новым образом.',
                'price' => 50,
            ],
        ],
        'backgrounds' => [
            [
                'title' => 'Солнечная комната',
                'short_description' => 'Светлый и уютный фон',
                'description' => 'Тёплая комната с мягким светом — идеальное место для отдыха после выполненных заданий.',
                'price' => 0,
            ],
            [
                'title' => 'Звёздная ночь',
                'short_description' => 'Спокойный вечерний фон',
                'description' => 'Ночной фон со звёздами создаёт атмосферу уюта перед сном.',
                'price' => 80,
            ],
        ],
        'stickers' => [
            [
                'title' => 'Золотая звезда',
                'short_description' => 'За отличную работу',
                'description' => 'Наклей звезду в комнате питомца, когда выполнил все задания за день.',
                'price' => 0,
            ],
            [
                'title' => 'Сердечко',
                'short_description' => 'От благодарного питомца',
                'description' => 'Стикер-сердечко — знак того, что питомец гордится твоими успехами.',
                'price' => 30,
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORY_ITEMS as $slug => $items) {
            $category = PetItemCategory::query()->where('slug', $slug)->firstOrFail();

            foreach ($items as $item) {
                $petItem = PetItem::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'title' => $item['title'],
                    ],
                    [
                        'short_description' => $item['short_description'],
                        'description' => $item['description'],
                        'is_available' => true,
                        'requirements' => null,
                        'price' => $item['price'],
                    ],
                );

                $this->syncPlaceholderMedia($petItem, 'pet-item.png');
            }
        }
    }
}
