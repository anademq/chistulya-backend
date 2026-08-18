<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;

class AdminCreateDailyTaskCategoryMutation extends AbstractCreateCategoryMutation
{
    protected $attributes = [
        'name' => 'createDailyTaskCategory',
        'description' => 'Admin: create a new daily task category.',
    ];

    protected function payloadType(): string
    {
        return 'DailyTaskCategoryPayload';
    }

    protected function categoriesTable(): string
    {
        return 'daily_task_categories';
    }

    protected function service(): DailyTaskService|ChallengeService|PetShopService
    {
        return app(DailyTaskService::class);
    }
}
