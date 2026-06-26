<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\ChallengeService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class StartChallengeMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'startChallenge',
        'description' => 'Start an active progress period for a selected challenge.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ChildChallengePayload');
    }

    public function args(): array
    {
        return [
            'challenge_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the selected challenge to start.',
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
        return ['child_challenge' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            return ['child_challenge' => app(ChallengeService::class)->start($user, $args['challenge_id'])];
        });
    }
}
