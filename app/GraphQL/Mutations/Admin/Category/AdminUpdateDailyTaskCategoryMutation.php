<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\Models\DailyTaskCategory;
use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;

class AdminUpdateDailyTaskCategoryMutation extends AbstractUpdateCategoryMutation
{
    protected $attributes = [
        'name' => 'updateDailyTaskCategory',
        'description' => 'Admin: update an existing daily task category.',
    ];

    protected function payloadType(): string
    {
        return 'DailyTaskCategoryPayload';
    }

    protected function categoriesTable(): string
    {
        return 'daily_task_categories';
    }

    protected function categoryModel(): string
    {
        return DailyTaskCategory::class;
    }

    protected function service(): DailyTaskService|ChallengeService|PetShopService
    {
        return app(DailyTaskService::class);
    }
}
