<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\ChallengeCategory;
use App\Services\ChallengeService;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ChallengeCategoriesQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'challengeCategories',
        'description' => 'Returns all challenge categories ordered by display position.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('ChallengeCategory'))));
    }

    public function resolve($root, array $args): Collection
    {
        // Cache plain arrays (not Eloquent objects) so a stale/cross-deploy cache
        // entry can never deserialize into __PHP_Incomplete_Class. Models are
        // re-hydrated from the cached rows on read.
        $rows = Cache::remember(
            ChallengeService::CATEGORIES_CACHE_KEY,
            now()->addDay(),
            static fn (): array => ChallengeCategory::query()
                ->orderBy('order_column')
                ->orderBy('title')
                ->get()
                ->toArray(),
        );

        return ChallengeCategory::hydrate($rows);
    }
}
