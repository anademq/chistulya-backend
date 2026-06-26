<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Child\ChildPetItem;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminRevokePetItemMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'revokePetItem',
        'description' => 'Admin: remove a specific pet item from a child\'s inventory.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => ['type' => Type::nonNull(Type::string()), 'description' => 'Child UUID.'],
            'pet_item_id' => ['type' => Type::nonNull(Type::string()), 'description' => 'UUID of the pet item to revoke.'],
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
        return [];
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

            ChildPetItem::where('child_id', $child->id)
                ->where('pet_item_id', $args['pet_item_id'])
                ->delete();

            return [];
        });
    }
}
