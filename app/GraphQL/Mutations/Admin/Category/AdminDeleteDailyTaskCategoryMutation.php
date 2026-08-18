<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\Models\DailyTaskCategory;
use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;

class AdminDeleteDailyTaskCategoryMutation extends AbstractDeleteCategoryMutation
{
    protected $attributes = [
        'name' => 'deleteDailyTaskCategory',
        'description' => 'Admin: delete a daily task category. Fails if tasks are still attached.',
    ];

    protected function categoriesTable(): string
    {
        return 'daily_task_categories';
    }

    protected function categoryModel(): string
    {
        return DailyTaskCategory::class;
    }

    protected function itemsRelation(): string
    {
        return 'dailyTasks';
    }

    protected function service(): DailyTaskService|ChallengeService|PetShopService
    {
        return app(DailyTaskService::class);
    }
}
