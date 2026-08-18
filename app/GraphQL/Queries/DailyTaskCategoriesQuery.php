<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\DailyTaskCategory;
use App\Services\DailyTaskService;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;

class DailyTaskCategoriesQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'dailyTaskCategories',
        'description' => 'Returns all daily task categories ordered by display position.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('DailyTaskCategory'))));
    }

    public function resolve($root, array $args): Collection
    {
        // Cache plain arrays (not Eloquent objects) so a stale/cross-deploy cache
        // entry can never deserialize into __PHP_Incomplete_Class. Models are
        // re-hydrated from the cached rows on read.
        $rows = Cache::remember(
            DailyTaskService::CATEGORIES_CACHE_KEY,
            now()->addDay(),
            static fn (): array => DailyTaskCategory::query()
                ->orderBy('order_column')
                ->orderBy('title')
                ->get()
                ->toArray(),
        );

        return DailyTaskCategory::hydrate($rows);
    }
}
