<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Queries\AuthedQuery;
use App\Models\Child\ChildPetItem;
use App\Models\User;
use App\Services\FamilyService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MyPetItemsQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'myPetItems',
        'description' => 'Returns all pet items owned by the given child, sorted by purchase date descending.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('ChildPetItem'))));
    }

    public function args(): array
    {
        return [
            'child_id' => [
                'type' => Type::string(),
                'description' => 'Child UUID. Required when called by a parent on behalf of a child; omit when called directly by the child.',
            ],
        ];
    }

    public function resolve($root, array $args): \Illuminate\Database\Eloquent\Collection
    {
        $child = $this->resolveChild($args);

        return ChildPetItem::query()
            ->where('child_id', $child->id)
            ->with('petItem')
            ->orderByDesc('purchased_at')
            ->get();
    }

    private function resolveChild(array $args): User
    {
        /** @var User $user */
        $user = auth()->user();
        $childId = (string) ($args['child_id'] ?? '');

        if (blank($childId)) {
            app(FamilyService::class)->assertChild($user);

            return $user;
        }

        app(FamilyService::class)->assertParentAccessToChild($user, $childId);

        return User::whereKey($childId)->firstOrFail();
    }
}
