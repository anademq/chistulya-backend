<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Parent;

use App\GraphQL\Support\SelectionFieldSet;
use App\Models\User;
use App\Services\FamilyService;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use App\GraphQL\Queries\ParentAuthedQuery;

class MyChildrenQuery extends ParentAuthedQuery
{
    protected $attributes = [
        'name' => 'myChildren',
        'description' => 'Returns all children linked to the currently authenticated parent.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('User'))));
    }

    public function resolve($root, array $args, $context, ResolveInfo $info): \Illuminate\Database\Eloquent\Collection
    {
        /** @var User $user */
        $user = auth()->user();
        $query = $user->children();
        $selection = SelectionFieldSet::fromInfo($info, 2);

        if (SelectionFieldSet::has($selection, 'profile')) {
            $query->with('profile');
        }

        return $query->get();
    }
}
