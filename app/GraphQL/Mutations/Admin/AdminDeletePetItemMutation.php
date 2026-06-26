<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\PetItem;
use App\Services\PetShopService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminDeletePetItemMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'deletePetItem',
        'description' => 'Admin: soft-delete a pet shop item.',
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
                'description' => 'UUID of the pet item to delete.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:pet_items,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            PetItem::whereKey($args['id'])->firstOrFail()->delete();

            Cache::increment(PetShopService::CATALOG_VERSION_KEY);

            return [];
        });
    }
}
