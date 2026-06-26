<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Achievement;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class AchievementType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Achievement',
        'description' => 'An achievement definition with unlock requirements and rewards.',
        'model' => Achievement::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique UUID identifier of the achievement.',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Display title of the achievement.',
            ],
            'short_description' => [
                'type' => Type::string(),
                'description' => 'Brief one-line description. Null if not set.',
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'Full detailed description. Null if not set.',
            ],
            'media' => [
                'type' => Type::listOf(GraphQL::type('Media')),
                'description' => 'Uploaded media for this achievement.',
                'resolve' => static fn(Achievement $achievement): Collection => $achievement->media,
            ],
            'is_available' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether this achievement is currently active and can be unlocked.',
            ],
            'requirements' => [
                'type' => GraphQL::type('AchievementRequirements'),
                'description' => 'Conditions that must be met to unlock the achievement.',
                'resolve' => static fn(Achievement $achievement): ?array => $achievement->requirements?->toArray(),
            ],
            'reward_xp' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'XP reward granted upon completion.',
            ],
            'reward_coins' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Coin reward granted upon completion.',
            ],
            'created_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the achievement was created.',
            ],
        ];
    }
}
