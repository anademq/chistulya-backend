<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\PetItemCategory;
use App\Services\PetShopService;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PetItemCategoriesQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'petItemCategories',
        'description' => 'Returns all pet item categories ordered by display position.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('PetItemCategory'))));
    }

    public function resolve($root, array $args): Collection
    {
        return Cache::remember(
            PetShopService::CATEGORIES_CACHE_KEY,
            now()->addDay(),
            static fn () => PetItemCategory::query()
                ->orderBy('order_column')
                ->orderBy('title')
                ->get(),
        );
    }
}
