<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChallengeAnalyticsPointType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ChallengeAnalyticsPoint',
        'description' => 'A single data point in a challenge time series, representing one calendar month.',
    ];

    public function fields(): array
    {
        return [
            'month' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Calendar month in YYYY-MM format.',
            ],
            'selected_count' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of challenges selected in this month.',
            ],
            'completed_count' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of challenges completed in this month.',
            ],
            'failed_count' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of challenges failed in this month.',
            ],
        ];
    }
}
