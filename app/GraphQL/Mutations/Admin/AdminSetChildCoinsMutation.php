<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminSetChildCoinsMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'setCoins',
        'description' => 'Admin: set a child\'s coin balance to an absolute value.',
    ];

    public function type(): Type
    {
        return GraphQL::type('WalletPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => ['type' => Type::nonNull(Type::string()), 'description' => 'Child UUID.'],
            'coins' => ['type' => Type::nonNull(Type::int()), 'description' => 'New coin balance (min 0).'],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'child_id' => ['required', 'uuid', 'exists:users,id'],
            'coins' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['wallet' => null];
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

            $wallet = $child->wallet()->firstOrCreate(
                ['child_id' => $child->id],
                ['coins' => 0],
            );

            $wallet->coins = (int) $args['coins'];
            $wallet->save();

            return ['wallet' => $wallet];
        });
    }
}
