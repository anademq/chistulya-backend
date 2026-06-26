<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Admin;

use App\Models\User;
use App\Services\AnalyticsService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminDailyTaskAnalyticsQuery extends AdminQuery
{
    protected $attributes = [
        'name' => 'dailyTaskAnalytics',
        'description' => 'Admin: returns daily task completion analytics for a given child over the past N days (max 90). Supports optional filtering by category slug.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('DailyTaskAnalyticsPoint'))));
    }

    public function args(): array
    {
        return [
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child.',
            ],
            'days' => [
                'type' => Type::int(),
                'defaultValue' => 30,
                'description' => 'Number of past days to include (1–90). Defaults to 30.',
            ],
            'category' => [
                'type' => Type::string(),
                'description' => 'Filter by task category slug.',
            ],
        ];
    }

    public function resolve($root, array $args): array
    {
        $child = User::whereKey($args['child_id'])->firstOrFail();

        return app(AnalyticsService::class)->dailyTasksByLastDays(
            $child,
            max(1, min(90, (int) ($args['days'] ?? 30))),
            $args['category'] ?? null
        );
    }
}
