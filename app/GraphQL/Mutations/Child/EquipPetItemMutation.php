<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\PetShopService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class EquipPetItemMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'equipPetItem',
        'description' => 'Equip a pet item from the child\'s inventory.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ChildPetItemPayload');
    }

    public function args(): array
    {
        return [
            'pet_item_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the pet item to equip.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'pet_item_id' => ['required', 'uuid', 'exists:pet_items,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['child_pet_item' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            return ['child_pet_item' => app(PetShopService::class)->equip($user, $args['pet_item_id'])];
        });
    }
}
