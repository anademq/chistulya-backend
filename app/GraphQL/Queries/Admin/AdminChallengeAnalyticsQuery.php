<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Admin;

use App\Models\User;
use App\Services\AnalyticsService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminChallengeAnalyticsQuery extends AdminQuery
{
    protected $attributes = [
        'name' => 'challengeAnalytics',
        'description' => 'Admin: returns challenge completion analytics for a given child grouped by month for the past N months (max 12).',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('ChallengeAnalyticsPoint'))));
    }

    public function args(): array
    {
        return [
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child.',
            ],
            'months' => [
                'type' => Type::int(),
                'defaultValue' => 6,
                'description' => 'Number of past months to include (1–12). Defaults to 6.',
            ],
            'category' => [
                'type' => Type::string(),
                'description' => 'Filter by challenge category slug.',
            ],
        ];
    }

    public function resolve($root, array $args): array
    {
        $child = User::whereKey($args['child_id'])->firstOrFail();

        return app(AnalyticsService::class)->challengesByLastMonths(
            $child,
            max(1, min(12, (int) ($args['months'] ?? 6))),
            $args['category'] ?? null
        );
    }
}
