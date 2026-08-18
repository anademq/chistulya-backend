<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;

class AdminCreatePetItemCategoryMutation extends AbstractCreateCategoryMutation
{
    protected $attributes = [
        'name' => 'createPetItemCategory',
        'description' => 'Admin: create a new pet item category.',
    ];

    protected function payloadType(): string
    {
        return 'PetItemCategoryPayload';
    }

    protected function categoriesTable(): string
    {
        return 'pet_item_categories';
    }

    protected function service(): DailyTaskService|ChallengeService|PetShopService
    {
        return app(PetShopService::class);
    }
}
