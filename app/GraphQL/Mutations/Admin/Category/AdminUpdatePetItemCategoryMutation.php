<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\Models\PetItemCategory;
use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;
use Illuminate\Support\Facades\Cache;

class AdminUpdatePetItemCategoryMutation extends AbstractUpdateCategoryMutation
{
    protected $attributes = [
        'name' => 'updatePetItemCategory',
        'description' => 'Admin: update an existing pet item category.',
    ];

    protected function payloadType(): string
    {
        return 'PetItemCategoryPayload';
    }

    protected function categoriesTable(): string
    {
        return 'pet_item_categories';
    }

    protected function categoryModel(): string
    {
        return PetItemCategory::class;
    }

    protected function service(): DailyTaskService|ChallengeService|PetShopService
    {
        return app(PetShopService::class);
    }

    protected function afterMutation(): void
    {
        Cache::increment(PetShopService::CATALOG_VERSION_KEY);
    }
}
