<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminAdjustExpMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'adjustExp',
        'description' => 'Admin: add (or subtract) a delta amount of XP to a child. The resulting XP will not drop below 0.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ExpPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => ['type' => Type::nonNull(Type::string()), 'description' => 'Child UUID.'],
            'add_xp' => ['type' => Type::nonNull(Type::int()), 'description' => 'XP delta to apply (can be negative to subtract).'],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'child_id' => ['required', 'uuid', 'exists:users,id'],
            'add_xp' => ['required', 'integer'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['exp' => null];
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

            $exp = $child->exp()->firstOrCreate(
                ['child_id' => $child->id],
                ['level' => 1, 'xp' => 0],
            );

            $exp->xp = max(0, $exp->xp + (int) $args['add_xp']);
            $exp->save();

            return ['exp' => $exp];
        });
    }
}
