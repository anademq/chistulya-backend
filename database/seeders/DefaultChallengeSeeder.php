<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ChallengeScope;
use App\Models\Challenge;
use App\Models\ChallengeCategory;
use Database\Seeders\Concerns\SeedsPlaceholderMedia;
use Illuminate\Database\Seeder;

class DefaultChallengeSeeder extends Seeder
{
    use SeedsPlaceholderMedia;

    /**
     * @var list<array{title:string,short_description:string,description:string,reward_xp:int,reward_coins:int,duration_days:int}>
     */
    private const GENERAL_ITEMS = [
        [
            'title' => 'Неделя без пропусков',
            'short_description' => '7 дней подряд выполняй задания',
            'description' => 'Отмечай выполнение ежедневных заданий каждый день в течение недели. Пропуск дня — испытание начинается заново.',
            'reward_xp' => 50,
            'reward_coins' => 30,
            'duration_days' => 7,
        ],
        [
            'title' => 'Супер-старт',
            'short_description' => 'Первые 7 дней с питомцем',
            'description' => 'Познакомься с сервисом и выполняй хотя бы одно задание каждый день первую неделю.',
            'reward_xp' => 40,
            'reward_coins' => 25,
            'duration_days' => 7,
        ],
    ];

    /**
     * @var array<string, list<array{title:string,short_description:string,description:string,reward_xp:int,reward_coins:int,duration_days:int}>>
     */
    private const SUBCATEGORY_ITEMS = [
        'hygiene' => [
            [
                'title' => 'Чистюля-неделя',
                'short_description' => '7 дней следи за гигиеной',
                'description' => 'Каждый день выполняй задания из категории «Гигиена»: чистка зубов, душ и другие полезные привычки.',
                'reward_xp' => 45,
                'reward_coins' => 25,
                'duration_days' => 7,
            ],
            [
                'title' => 'Белоснежная улыбка',
                'short_description' => 'Чисти зубы каждый день',
                'description' => 'Не пропускай чистку зубов утром и вечером в течение всей недели.',
                'reward_xp' => 35,
                'reward_coins' => 20,
                'duration_days' => 7,
            ],
        ],
        'order' => [
            [
                'title' => 'Порядок каждый день',
                'short_description' => '7 дней чистой комнаты',
                'description' => 'Поддерживай порядок в комнате: заправляй постель, убирай вещи и игрушки каждый день.',
                'reward_xp' => 45,
                'reward_coins' => 25,
                'duration_days' => 7,
            ],
            [
                'title' => 'Мастер аккуратности',
                'short_description' => 'Убирайся без напоминаний',
                'description' => 'Самостоятельно наводи порядок в своей комнате каждый день в течение недели.',
                'reward_xp' => 40,
                'reward_coins' => 22,
                'duration_days' => 7,
            ],
        ],
        'food' => [
            [
                'title' => 'Полезная неделя',
                'short_description' => '7 дней здоровых привычек',
                'description' => 'Следи за режимом питания: не пропускай завтрак и выбирай полезные перекусы.',
                'reward_xp' => 40,
                'reward_coins' => 22,
                'duration_days' => 7,
            ],
            [
                'title' => 'Водный баланс',
                'short_description' => 'Пей воду каждый день',
                'description' => 'Выпивай достаточно воды каждый день недели — это важная привычка для здоровья.',
                'reward_xp' => 35,
                'reward_coins' => 20,
                'duration_days' => 7,
            ],
        ],
        'study' => [
            [
                'title' => 'Умная неделя',
                'short_description' => '7 дней регулярных занятий',
                'description' => 'Выполняй домашние задания и уделяй время учёбе каждый день в течение недели.',
                'reward_xp' => 50,
                'reward_coins' => 30,
                'duration_days' => 7,
            ],
            [
                'title' => 'Книжный герой',
                'short_description' => 'Читай каждый день',
                'description' => 'Читай хотя бы 15 минут в день всю неделю — книги развивают мышление и фантазию.',
                'reward_xp' => 40,
                'reward_coins' => 25,
                'duration_days' => 7,
            ],
        ],
    ];

    public function run(): void
    {
        $generalCategory = ChallengeCategory::query()->where('slug', 'hygiene')->firstOrFail();

        foreach (self::GENERAL_ITEMS as $item) {
            $challenge = Challenge::updateOrCreate(
                [
                    'scope' => ChallengeScope::GLOBAL,
                    'category_id' => $generalCategory->id,
                    'title' => $item['title'],
                ],
                [
                    'short_description' => $item['short_description'],
                    'description' => $item['description'],
                    'reward_xp' => $item['reward_xp'],
                    'reward_coins' => $item['reward_coins'],
                    'duration_days' => $item['duration_days'],
                ],
            );

            $this->syncPlaceholderMedia($challenge, 'challenge.png');
        }

        foreach (self::SUBCATEGORY_ITEMS as $slug => $items) {
            $category = ChallengeCategory::query()->where('slug', $slug)->firstOrFail();

            foreach ($items as $item) {
                $challenge = Challenge::updateOrCreate(
                    [
                        'scope' => ChallengeScope::GLOBAL,
                        'category_id' => $category->id,
                        'title' => $item['title'],
                    ],
                    [
                        'short_description' => $item['short_description'],
                        'description' => $item['description'],
                        'reward_xp' => $item['reward_xp'],
                        'reward_coins' => $item['reward_coins'],
                        'duration_days' => $item['duration_days'],
                    ],
                );

                $this->syncPlaceholderMedia($challenge, 'challenge.png');
            }
        }
    }
}
