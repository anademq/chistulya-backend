<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\User;
use App\Services\FamilyService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class UnlinkChildMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'unlinkChild',
        'description' => 'Removes the family link between this parent and the specified child.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => ['type' => Type::nonNull(Type::string())],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'child_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            app(FamilyService::class)->unlinkChild($user, $args['child_id']);

            return [];
        });
    }
}
