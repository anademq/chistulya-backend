<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Enums\ProfileRole;
use App\Models\User;
use App\Models\User\UserLink;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminLinkParentChildMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'linkChild',
        'description' => 'Admin: link a parent and child user directly.',
    ];

    public function type(): Type
    {
        return GraphQL::type('FamilyLinkPayload');
    }

    public function args(): array
    {
        return [
            'parent_id' => ['type' => Type::nonNull(Type::string())],
            'child_id' => ['type' => Type::nonNull(Type::string())],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'parent_id' => ['required', 'uuid', 'exists:users,id'],
            'child_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['link' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $parent = User::query()->with('profile')->whereKey((string) $args['parent_id'])->firstOrFail();
            $child = User::query()->with('profile')->whereKey((string) $args['child_id'])->firstOrFail();

            if (! $parent->profile || $parent->profile->role !== ProfileRole::PARENT) {
                throw ValidationException::withMessages([
                    'parent_id' => 'User is not a parent profile.',
                ]);
            }

            if (! $child->profile || $child->profile->role !== ProfileRole::CHILD) {
                throw ValidationException::withMessages([
                    'child_id' => 'User is not a child profile.',
                ]);
            }

            $link = UserLink::firstOrCreate([
                'parent_id' => $parent->id,
                'child_id' => $child->id,
            ]);

            return ['link' => $link->load('parent', 'child')];
        });
    }
}
