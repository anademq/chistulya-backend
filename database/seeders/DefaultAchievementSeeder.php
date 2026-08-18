<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Achievement;
use Database\Seeders\Concerns\SeedsPlaceholderMedia;
use Illuminate\Database\Seeder;

class DefaultAchievementSeeder extends Seeder
{
    use SeedsPlaceholderMedia;

    /**
     * @var list<array{title:string,short_description:string,description:string,reward_xp:int,reward_coins:int}>
     */
    private const DEFAULT_ITEMS = [
        [
            'title' => 'Первый шаг',
            'short_description' => 'Выполни первое задание',
            'description' => 'Отметь выполнение своего первого ежедневного задания и начни путь к полезным привычкам вместе с питомцем.',
            'reward_xp' => 30,
            'reward_coins' => 15,
        ],
        [
            'title' => 'Неделя силы',
            'short_description' => '7 дней подряд без пропусков',
            'description' => 'Выполняй задания каждый день целую неделю — это показывает настоящую дисциплину и заботу о себе.',
            'reward_xp' => 75,
            'reward_coins' => 40,
        ],
    ];

    public function run(): void
    {
        foreach (self::DEFAULT_ITEMS as $item) {
            $achievement = Achievement::updateOrCreate(
                ['title' => $item['title']],
                [
                    'short_description' => $item['short_description'],
                    'description' => $item['description'],
                    'is_available' => true,
                    'requirements' => null,
                    'reward_xp' => $item['reward_xp'],
                    'reward_coins' => $item['reward_coins'],
                ],
            );

            $this->syncPlaceholderMedia($achievement, 'achievement.png');
        }
    }
}
