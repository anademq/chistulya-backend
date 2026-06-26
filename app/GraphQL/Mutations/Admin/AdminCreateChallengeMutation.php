<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Enums\ChallengeScope;
use App\Models\Challenge;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminCreateChallengeMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'createChallenge',
        'description' => 'Admin: create a new challenge.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ChallengePayload');
    }

    public function args(): array
    {
        return [
            'scope' => ['type' => Type::nonNull(Type::string())],
            'category_id' => ['type' => Type::nonNull(Type::string())],
            'title' => ['type' => Type::nonNull(Type::string())],
            'short_description' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'reward_xp' => ['type' => Type::int(), 'defaultValue' => 0],
            'reward_coins' => ['type' => Type::int(), 'defaultValue' => 0],
            'duration_days' => ['type' => Type::int(), 'defaultValue' => 1],
            'media_id' => ['type' => Type::string()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'scope' => ['required', Rule::in(array_column(ChallengeScope::cases(), 'value'))],
            'category_id' => ['required', 'integer', 'exists:challenge_categories,id'],
            'title' => ['required', 'string', 'max:150'],
            'short_description' => ['nullable', 'string', 'max:250'],
            'description' => ['nullable', 'string'],
            'reward_xp' => ['nullable', 'integer', 'min:0'],
            'reward_coins' => ['nullable', 'integer', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'media_id' => ['nullable', 'uuid', 'exists:media,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['challenge' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $admin */
        $admin = auth()->user();

        return $this->wrapPayload(function () use ($admin, $args): array {
            $challenge = Challenge::create([
                'created_by' => $admin->id,
                'scope' => $args['scope'],
                'category_id' => (int) $args['category_id'],
                'title' => $args['title'],
                'short_description' => $args['short_description'] ?? null,
                'description' => $args['description'] ?? null,
                'reward_xp' => (int) ($args['reward_xp'] ?? 0),
                'reward_coins' => (int) ($args['reward_coins'] ?? 0),
                'duration_days' => (int) ($args['duration_days'] ?? 1),
            ]);

            if (!empty($args['media_id'])) {
                $media = Media::whereKey($args['media_id'])->firstOrFail();
                app(MediaService::class)->attachToEntity($media, $challenge);
            }

            return ['challenge' => $challenge->refresh()->load('category')];
        });
    }
}
