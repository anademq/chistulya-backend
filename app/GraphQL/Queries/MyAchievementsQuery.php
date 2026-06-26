<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Queries\AuthedQuery;
use App\Models\Achievement;
use App\Models\Child\ChildAchievement;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\FamilyService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MyAchievementsQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'myAchievements',
        'description' => 'Returns all achievements for the given child, syncing progress before returning.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('ChildAchievement'))));
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

    public function resolve($root, array $args): Collection
    {
        $child = $this->resolveChild($args);
        $achievements = app(AchievementService::class)->syncAndList($child);

        return $achievements
            ->flatMap(
                fn(Achievement $achievement) => $achievement->childAchievements
                    ->map(fn(ChildAchievement $ca) => $ca->setRelation('achievement', $achievement))
            )
            ->sortByDesc('completed_at')
            ->values();
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
