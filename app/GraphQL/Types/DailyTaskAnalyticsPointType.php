<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class DailyTaskAnalyticsPointType extends GraphQLType
{
    protected $attributes = [
        'name' => 'DailyTaskAnalyticsPoint',
        'description' => 'A single data point in a daily task time series, representing one calendar day.',
    ];

    public function fields(): array
    {
        return [
            'date' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Calendar date in YYYY-MM-DD format.',
            ],
            'weekday' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ISO weekday number (1 = Monday, 7 = Sunday).',
            ],
            'selected_count' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of daily tasks selected on this date.',
            ],
            'completed_count' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of daily tasks completed on this date.',
            ],
        ];
    }
}
