<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Achievement;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminDeleteAchievementMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'deleteAchievement',
        'description' => 'Admin: soft-delete an achievement.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the achievement to delete.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:achievements,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            Achievement::whereKey($args['id'])->firstOrFail()->delete();

            return [];
        });
    }
}
