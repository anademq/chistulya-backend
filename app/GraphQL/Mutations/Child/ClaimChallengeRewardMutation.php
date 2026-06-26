<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\ChallengeService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ClaimChallengeRewardMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'claimChallengeReward',
        'description' => 'Claim the reward for a completed challenge.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ClaimChallengeRewardPayload');
    }

    public function args(): array
    {
        return [
            'challenge_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the completed challenge whose reward you want to claim.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'challenge_id' => ['required', 'uuid', 'exists:challenges,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['child_challenge' => null, 'reward' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            return app(ChallengeService::class)->claim($user, $args['challenge_id']);
        });
    }
}
