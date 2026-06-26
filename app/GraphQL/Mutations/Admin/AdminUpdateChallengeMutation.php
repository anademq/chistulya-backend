<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Enums\ChallengeScope;
use App\Models\Challenge;
use App\Models\Media;
use App\Services\MediaService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminUpdateChallengeMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'updateChallenge',
        'description' => 'Admin: update an existing challenge.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ChallengePayload');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::string())],
            'scope' => ['type' => Type::string()],
            'category_id' => ['type' => Type::string()],
            'title' => ['type' => Type::string()],
            'short_description' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'reward_xp' => ['type' => Type::int()],
            'reward_coins' => ['type' => Type::int()],
            'duration_days' => ['type' => Type::int()],
            'media_id' => ['type' => Type::string()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:challenges,id'],
            'scope' => ['nullable', Rule::in(array_column(ChallengeScope::cases(), 'value'))],
            'category_id' => ['nullable', 'integer', 'exists:challenge_categories,id'],
            'title' => ['nullable', 'string', 'max:150'],
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
        return $this->wrapPayload(function () use ($args): array {
            $challenge = Challenge::withTrashed()->whereKey((string) $args['id'])->firstOrFail();

            $fields = array_filter(
                array_intersect_key($args, array_flip(['scope', 'category_id', 'title', 'short_description', 'description', 'reward_xp', 'reward_coins', 'duration_days'])),
                static fn($v) => $v !== null,
            );

            if (!empty($fields)) {
                $challenge->forceFill($fields)->save();
            }

            if (!empty($args['media_id'])) {
                $media = Media::whereKey($args['media_id'])->firstOrFail();
                app(MediaService::class)->attachToEntity($media, $challenge);
            }

            return ['challenge' => $challenge->refresh()->load('category')];
        });
    }
}
