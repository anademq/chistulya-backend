<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Challenge;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminDeleteChallengeMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'deleteChallenge',
        'description' => 'Admin: soft-delete a challenge.',
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
                'description' => 'UUID of the challenge to delete.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:challenges,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            Challenge::whereKey($args['id'])->firstOrFail()->delete();

            return [];
        });
    }
}
