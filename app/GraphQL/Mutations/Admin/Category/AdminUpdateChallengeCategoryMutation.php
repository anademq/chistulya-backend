<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\Models\ChallengeCategory;
use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;

class AdminUpdateChallengeCategoryMutation extends AbstractUpdateCategoryMutation
{
    protected $attributes = [
        'name' => 'updateChallengeCategory',
        'description' => 'Admin: update an existing challenge category.',
    ];

    protected function payloadType(): string
    {
        return 'ChallengeCategoryPayload';
    }

    protected function categoriesTable(): string
    {
        return 'challenge_categories';
    }

    protected function categoryModel(): string
    {
        return ChallengeCategory::class;
    }

    protected function service(): DailyTaskService|ChallengeService|PetShopService
    {
        return app(ChallengeService::class);
    }
}
