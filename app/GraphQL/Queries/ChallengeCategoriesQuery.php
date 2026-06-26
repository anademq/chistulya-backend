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
        return Cache::remember(
            ChallengeService::CATEGORIES_CACHE_KEY,
            now()->addDay(),
            static fn () => ChallengeCategory::query()
                ->orderBy('order_column')
                ->orderBy('title')
                ->get(),
        );
    }
}
