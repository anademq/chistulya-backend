<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Child\ChildPetItem;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminGrantPetItemToChildMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'grantPetItem',
        'description' => 'Admin: grant a pet item to a child\'s inventory without charging coins.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ChildPetItemPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child user.',
            ],
            'pet_item_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the pet item to grant.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'child_id' => ['required', 'uuid', 'exists:users,id'],
            'pet_item_id' => ['required', 'uuid', 'exists:pet_items,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['child_pet_item' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $child = User::whereKey($args['child_id'])->firstOrFail();

            if (! $child->isChild()) {
                throw ValidationException::withMessages([
                    'child_id' => __('validation.custom.must_be_child'),
                ]);
            }

            $childPetItem = ChildPetItem::firstOrCreate([
                'child_id' => $child->id,
                'pet_item_id' => $args['pet_item_id'],
            ]);

            return ['child_pet_item' => $childPetItem->load('petItem')];
        });
    }
}
