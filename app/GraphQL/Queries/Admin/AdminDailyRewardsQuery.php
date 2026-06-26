<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Admin;

use App\Models\DailyReward;
use App\Services\DailyRewardService;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminDailyRewardsQuery extends AdminQuery
{
    protected $attributes = [
        'name' => 'dailyRewards',
        'description' => 'Returns all daily reward configurations ordered by day. Day 0 is the fixed post-streak reward; days 1–N are sequential streak rewards.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('DailyReward'))));
    }

    public function resolve($root, array $args): Collection
    {
        /** @var Collection<int, DailyReward> $keyed */
        $keyed = Cache::remember(
            DailyRewardService::CACHE_KEY,
            now()->addDay(),
            static fn () => DailyReward::all()->keyBy('day'),
        );

        return $keyed->sortKeys()->values();
    }
}
