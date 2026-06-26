<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Child\ChildChallenge;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChildChallengeType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ChildChallenge',
        'description' => 'A child\'s progress record for a selected challenge.',
        'model' => ChildChallenge::class,
    ];

    public function fields(): array
    {
        return [
            'challenge_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the challenge definition.',
            ],
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child who selected the challenge.',
            ],
            'status' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Current status (e.g. "selected", "active", "completed", "reward_claimed").',
                'resolve' => static fn(ChildChallenge $challenge): string => $challenge->status->value,
            ],
            'progress_days' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of days the child has logged progress so far.',
            ],
            'last_progress_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp of the last progress entry. Null if no progress yet.',
            ],
            'completed_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the challenge was completed. Null if not yet completed.',
            ],
            'reward_claimed_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the reward was claimed. Null if not yet claimed.',
            ],
            'challenge' => [
                'type' => GraphQL::type('Challenge'),
                'description' => 'The associated challenge definition.',
            ],
        ];
    }
}
