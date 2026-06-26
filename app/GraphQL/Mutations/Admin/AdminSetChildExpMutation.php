<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminSetChildExpMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'setExp',
        'description' => 'Admin: set a child\'s XP and/or level to an absolute value.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ExpPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => ['type' => Type::nonNull(Type::string()), 'description' => 'Child UUID.'],
            'level' => ['type' => Type::int(), 'description' => 'Set level to this value (min 1).'],
            'xp' => ['type' => Type::int(), 'description' => 'Set XP to this value (min 0).'],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'child_id' => ['required', 'uuid', 'exists:users,id'],
            'level' => ['nullable', 'integer', 'min:1'],
            'xp' => ['nullable', 'integer', 'min:0'],
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

            if (isset($args['level'])) {
                $exp->level = (int) $args['level'];
            }

            if (isset($args['xp'])) {
                $exp->xp = (int) $args['xp'];
            }

            $exp->save();

            return ['exp' => $exp];
        });
    }
}
