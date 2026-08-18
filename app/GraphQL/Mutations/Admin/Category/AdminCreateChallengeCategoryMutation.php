<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;

class AdminCreateChallengeCategoryMutation extends AbstractCreateCategoryMutation
{
    protected $attributes = [
        'name' => 'createChallengeCategory',
        'description' => 'Admin: create a new challenge category.',
    ];

    protected function payloadType(): string
    {
        return 'ChallengeCategoryPayload';
    }

    protected function categoriesTable(): string
    {
        return 'challenge_categories';
    }

    protected function service(): DailyTaskService|ChallengeService|PetShopService
    {
        return app(ChallengeService::class);
    }
}
