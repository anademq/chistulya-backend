<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\AchievementService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ClaimAchievementRewardMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'claimAchievementReward',
        'description' => 'Claim the reward for a completed achievement.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ClaimAchievementRewardPayload');
    }

    public function args(): array
    {
        return [
            'achievement_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the completed achievement whose reward you want to claim.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'achievement_id' => ['required', 'uuid', 'exists:achievements,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['child_achievement' => null, 'reward' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            return app(AchievementService::class)->claim($user, $args['achievement_id']);
        });
    }
}
