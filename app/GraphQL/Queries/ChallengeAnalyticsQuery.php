<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Queries\AuthedQuery;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\FamilyService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ChallengeAnalyticsQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'challengeAnalytics',
        'description' => 'Returns challenge completion data points grouped by month for the given child over the specified time range.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('ChallengeAnalyticsPoint'))));
    }

    public function args(): array
    {
        return [
            'months' => [
                'type' => Type::int(),
                'defaultValue' => 6,
                'description' => 'Number of past months to include (1–12). Defaults to 6.',
            ],
            'category' => [
                'type' => Type::string(),
                'description' => 'Filter by challenge category slug.',
            ],
            'child_id' => [
                'type' => Type::string(),
                'description' => 'Child UUID. Required when called by a parent on behalf of a child; omit when called directly by the child.',
            ],
        ];
    }

    public function resolve($root, array $args): array
    {
        $child = $this->resolveChild($args);

        return app(AnalyticsService::class)->challengesByLastMonths(
            $child,
            max(1, min(12, (int) ($args['months'] ?? 6))),
            $args['category'] ?? null
        );
    }

    private function resolveChild(array $args): User
    {
        /** @var User $user */
        $user = auth()->user();
        $childId = (string) ($args['child_id'] ?? '');

        if (blank($childId)) {
            app(FamilyService::class)->assertChild($user);

            return $user;
        }

        app(FamilyService::class)->assertParentAccessToChild($user, $childId);

        return User::whereKey($childId)->firstOrFail();
    }
}
