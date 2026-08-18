<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DailyTaskScope;
use App\Models\DailyTask;
use App\Models\DailyTaskCategory;
use Database\Seeders\Concerns\SeedsPlaceholderMedia;
use Illuminate\Database\Seeder;

class DefaultDailyTaskSeeder extends Seeder
{
    use SeedsPlaceholderMedia;

    /**
     * @var list<array{title:string,short_description:string,description:string,reward_xp:int,reward_coins:int}>
     */
    private const GENERAL_ITEMS = [
        [
            'title' => 'Утренний старт',
            'short_description' => 'Начни день с полезных привычек',
            'description' => 'Выполни утренние дела: умойся, заправь постель и настройся на продуктивный день вместе с питомцем.',
            'reward_xp' => 10,
            'reward_coins' => 5,
        ],
        [
            'title' => 'Вечерний ритуал',
            'short_description' => 'Спокойно заверши день',
            'description' => 'Подготовься ко сну: приведи в порядок вещи, почисти зубы и отметь выполненные задания.',
            'reward_xp' => 10,
            'reward_coins' => 5,
        ],
    ];

    /**
     * @var array<string, list<array{title:string,short_description:string,description:string,reward_xp:int,reward_coins:int}>>
     */
    private const SUBCATEGORY_ITEMS = [
        'hygiene' => [
            [
                'title' => 'Почистить зубы',
                'short_description' => 'Утренний и вечерний уход',
                'description' => 'Почисти зубы минимум 2 минуты. Это помогает сохранить здоровье и свежесть на весь день.',
                'reward_xp' => 10,
                'reward_coins' => 5,
            ],
            [
                'title' => 'Принять душ',
                'short_description' => 'Свежесть и бодрость',
                'description' => 'Прими душ или ванну, чтобы чувствовать себя чистым и комфортно после активного дня.',
                'reward_xp' => 10,
                'reward_coins' => 5,
            ],
        ],
        'order' => [
            [
                'title' => 'Заправить постель',
                'short_description' => 'Аккуратная комната с утра',
                'description' => 'Заправь постель после сна — маленький шаг, который задаёт порядок на весь день.',
                'reward_xp' => 10,
                'reward_coins' => 5,
            ],
            [
                'title' => 'Убрать игрушки',
                'short_description' => 'Порядок на полке и столе',
                'description' => 'Сложи игрушки и вещи на место, чтобы в комнате было приятно играть и отдыхать.',
                'reward_xp' => 10,
                'reward_coins' => 5,
            ],
        ],
        'food' => [
            [
                'title' => 'Позавтракать',
                'short_description' => 'Энергия для активного дня',
                'description' => 'Не пропускай завтрак — полноценный приём пищи помогает лучше учиться и играть.',
                'reward_xp' => 10,
                'reward_coins' => 5,
            ],
            [
                'title' => 'Выпить стакан воды',
                'short_description' => 'Пей достаточно жидкости',
                'description' => 'Выпей стакан чистой воды в течение дня, чтобы чувствовать себя бодрым и здоровым.',
                'reward_xp' => 10,
                'reward_coins' => 5,
            ],
        ],
        'study' => [
            [
                'title' => 'Сделать домашнее задание',
                'short_description' => 'Уроки без спешки',
                'description' => 'Выдели время на домашнюю работу: разбей задания на части и выполни их спокойно и внимательно.',
                'reward_xp' => 15,
                'reward_coins' => 8,
            ],
            [
                'title' => 'Почитать 15 минут',
                'short_description' => 'Книга — лучший друг',
                'description' => 'Почитай любимую книгу хотя бы 15 минут — это развивает воображение и помогает отдыхать.',
                'reward_xp' => 10,
                'reward_coins' => 5,
            ],
        ],
    ];

    public function run(): void
    {
        $generalCategory = DailyTaskCategory::query()->where('slug', 'hygiene')->firstOrFail();

        foreach (self::GENERAL_ITEMS as $item) {
            $task = DailyTask::updateOrCreate(
                [
                    'scope' => DailyTaskScope::GLOBAL,
                    'category_id' => $generalCategory->id,
                    'title' => $item['title'],
                ],
                [
                    'short_description' => $item['short_description'],
                    'description' => $item['description'],
                    'reward_xp' => $item['reward_xp'],
                    'reward_coins' => $item['reward_coins'],
                ],
            );

            $this->syncPlaceholderMedia($task, 'daily-task.png');
        }

        foreach (self::SUBCATEGORY_ITEMS as $slug => $items) {
            $category = DailyTaskCategory::query()->where('slug', $slug)->firstOrFail();

            foreach ($items as $item) {
                $task = DailyTask::updateOrCreate(
                    [
                        'scope' => DailyTaskScope::GLOBAL,
                        'category_id' => $category->id,
                        'title' => $item['title'],
                    ],
                    [
                        'short_description' => $item['short_description'],
                        'description' => $item['description'],
                        'reward_xp' => $item['reward_xp'],
                        'reward_coins' => $item['reward_coins'],
                    ],
                );

                $this->syncPlaceholderMedia($task, 'daily-task.png');
            }
        }
    }
}
