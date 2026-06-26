<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Queries\AuthedQuery;
use App\Models\User;
use App\Services\ChallengeService;
use App\Services\FamilyService;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AvailableChallengesQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'availableChallenges',
        'description' => 'Returns a paginated list of challenges available for the given child that have not yet been selected.',
    ];

    public function type(): Type
    {
        return GraphQL::paginate('Challenge');
    }

    public function args(): array
    {
        return [
            'page' => [
                'type' => Type::int(),
                'defaultValue' => 1,
                'description' => 'Page number (1-based). Defaults to 1.',
            ],
            'per_page' => [
                'type' => Type::int(),
                'defaultValue' => 10,
                'description' => 'Number of items per page (max 100). Defaults to 10.',
            ],
            'child_id' => [
                'type' => Type::string(),
                'description' => 'Child UUID. Required when called by a parent on behalf of a child; omit when called directly by the child.',
            ],
        ];
    }

    public function resolve($root, array $args): LengthAwarePaginator
    {
        $child = $this->resolveChild($args);

        return app(ChallengeService::class)->listAvailable(
            $child,
            max(1, (int) ($args['page'] ?? 1)),
            max(1, min(100, (int) ($args['per_page'] ?? 10)))
        );
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
