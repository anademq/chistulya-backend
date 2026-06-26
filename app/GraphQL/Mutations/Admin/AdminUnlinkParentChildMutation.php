<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\User\UserLink;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminUnlinkParentChildMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'unlinkChild',
        'description' => 'Admin: unlink a parent and child user.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'parent_id' => ['type' => Type::nonNull(Type::string())],
            'child_id' => ['type' => Type::nonNull(Type::string())],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'parent_id' => ['required', 'uuid', 'exists:users,id'],
            'child_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            UserLink::query()
                ->where('parent_id', (string) $args['parent_id'])
                ->where('child_id', (string) $args['child_id'])
                ->delete();

            return [];
        });
    }
}
