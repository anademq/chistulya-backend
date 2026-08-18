<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\Models\PetItemCategory;
use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;
use Illuminate\Support\Facades\Cache;

class AdminDeletePetItemCategoryMutation extends AbstractDeleteCategoryMutation
{
    protected $attributes = [
        'name' => 'deletePetItemCategory',
        'description' => 'Admin: delete a pet item category. Fails if items are still attached.',
    ];

    protected function categoriesTable(): string
    {
        return 'pet_item_categories';
    }

    protected function categoryModel(): string
    {
        return PetItemCategory::class;
    }

    protected function itemsRelation(): string
    {
        return 'petItems';
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
