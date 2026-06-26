<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminAdjustCoinsMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'adjustCoins',
        'description' => 'Admin: add (or subtract) a delta amount of coins to a child. The resulting balance will not drop below 0.',
    ];

    public function type(): Type
    {
        return GraphQL::type('WalletPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => ['type' => Type::nonNull(Type::string()), 'description' => 'Child UUID.'],
            'add_coins' => ['type' => Type::nonNull(Type::int()), 'description' => 'Coin delta to apply (can be negative to subtract).'],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'child_id' => ['required', 'uuid', 'exists:users,id'],
            'add_coins' => ['required', 'integer'],
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

            $wallet->coins = max(0, $wallet->coins + (int) $args['add_coins']);
            $wallet->save();

            return ['wallet' => $wallet];
        });
    }
}
