<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\Models\ChallengeCategory;
use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;

class AdminDeleteChallengeCategoryMutation extends AbstractDeleteCategoryMutation
{
    protected $attributes = [
        'name' => 'deleteChallengeCategory',
        'description' => 'Admin: delete a challenge category. Fails if challenges are still attached.',
    ];

    protected function categoriesTable(): string
    {
        return 'challenge_categories';
    }

    protected function categoryModel(): string
    {
        return ChallengeCategory::class;
    }

    protected function itemsRelation(): string
    {
        return 'challenges';
    }

    protected function service(): DailyTaskService|ChallengeService|PetShopService
    {
        return app(ChallengeService::class);
    }
}
