<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Child\ChildPetItem;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminClearPetItemsMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'clearPetItems',
        'description' => 'Admin: remove all pet items from a child\'s inventory.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => ['type' => Type::nonNull(Type::string()), 'description' => 'Child UUID.'],
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
        return $this->wrapPayload(function () use ($args): array {
            $child = User::whereKey($args['child_id'])->firstOrFail();

            if (! $child->isChild()) {
                throw ValidationException::withMessages([
                    'child_id' => __('validation.custom.must_be_child'),
                ]);
            }

            ChildPetItem::where('child_id', $child->id)->delete();

            return [];
        });
    }
}
