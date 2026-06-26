<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Account;

use App\GraphQL\Queries\AuthedQuery;
use App\GraphQL\Support\SelectionFieldSet;
use App\Models\User;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MeQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'me',
        'description' => 'Returns the currently authenticated user.',
    ];

    public function type(): Type
    {
        return GraphQL::type('User');
    }

    public function resolve($root, array $args, $context, ResolveInfo $info): User
    {
        /** @var User $user */
        $user = auth()->user();
        $selection = SelectionFieldSet::fromInfo($info, 2);
        $relations = [];

        if (SelectionFieldSet::has($selection, 'profile')) {
            $relations[] = 'profile';
        }

        if (SelectionFieldSet::has($selection, 'wallet')) {
            $relations[] = 'wallet';
        }

        if (SelectionFieldSet::has($selection, 'exp')) {
            $relations[] = 'exp';
        }

        if (SelectionFieldSet::has($selection, 'parents')) {
            $relations[] = 'parents.profile';
        }

        if (SelectionFieldSet::has($selection, 'children')) {
            $relations[] = 'children.profile';
        }

        if ($relations !== []) {
            $user->loadMissing($relations);
        }

        return $user;
    }
}
