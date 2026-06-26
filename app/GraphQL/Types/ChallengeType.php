<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Enums\ChallengeScope;
use App\Models\Challenge;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChallengeType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Challenge',
        'description' => 'A multi-day challenge definition that children can select and complete for rewards.',
        'model' => Challenge::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique UUID identifier of the challenge.',
            ],
            'scope' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Raw scope value. Admin use only — clients should use the `source` field instead.',
                'resolve' => static fn(Challenge $challenge): string => $challenge->scope->value,
            ],
            'source' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'How this challenge was made available: "global" (system challenge for all children) or "custom" (created by a parent or admin for specific children).',
                'resolve' => static fn(Challenge $challenge): string => match ($challenge->scope) {
                    ChallengeScope::GLOBAL => 'global',
                    default => 'custom',
                },
            ],
            'creator_id' => [
                'type' => Type::string(),
                'description' => 'UUID of the parent who created this challenge. Null for system (global) challenges and admin-created challenges.',
                'resolve' => static function (Challenge $challenge): ?string {
                    if ($challenge->scope === ChallengeScope::GLOBAL || $challenge->created_by === null) {
                        return null;
                    }

                    $challenge->loadMissing('creator');

                    if ($challenge->creator?->isAdminUser()) {
                        return null;
                    }

                    return $challenge->created_by;
                },
            ],
            'category_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'ID of the challenge category.',
            ],
            'category' => [
                'type' => GraphQL::type('ChallengeCategory'),
                'description' => 'Challenge category details.',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Short display title of the challenge.',
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
                'description' => 'Uploaded media for this challenge.',
                'resolve' => static fn(Challenge $challenge): Collection => $challenge->media,
            ],
            'reward_xp' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'XP reward granted upon completion.',
            ],
            'reward_coins' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Coin reward granted upon completion.',
            ],
            'duration_days' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of consecutive days required to complete the challenge.',
            ],
            'created_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the challenge was created.',
            ],
        ];
    }
}
