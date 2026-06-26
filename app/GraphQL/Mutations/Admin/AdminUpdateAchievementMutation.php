<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Achievement;
use App\Models\Media;
use App\Services\MediaService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminUpdateAchievementMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'updateAchievement',
        'description' => 'Admin: update an existing achievement.',
    ];

    public function type(): Type
    {
        return GraphQL::type('AchievementPayload');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::string())],
            'title' => ['type' => Type::string()],
            'short_description' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'is_available' => ['type' => Type::boolean()],
            'requirements' => ['type' => GraphQL::type('AchievementRequirementsInput')],
            'reward_xp' => ['type' => Type::int()],
            'reward_coins' => ['type' => Type::int()],
            'media_id' => ['type' => Type::string()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:achievements,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:250'],
            'description' => ['nullable', 'string'],
            'is_available' => ['nullable', 'boolean'],
            'reward_xp' => ['nullable', 'integer', 'min:0'],
            'reward_coins' => ['nullable', 'integer', 'min:0'],
            'media_id' => ['nullable', 'uuid', 'exists:media,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['achievement' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $achievement = Achievement::withTrashed()->whereKey((string) $args['id'])->firstOrFail();

            $fields = array_filter(
                array_intersect_key($args, array_flip(['title', 'short_description', 'description', 'is_available', 'requirements', 'reward_xp', 'reward_coins'])),
                static fn($v) => $v !== null,
            );

            if (!empty($fields)) {
                $achievement->forceFill($fields)->save();
            }

            if (!empty($args['media_id'])) {
                $media = Media::whereKey($args['media_id'])->firstOrFail();
                app(MediaService::class)->attachToEntity($media, $achievement);
            }

            return ['achievement' => $achievement->refresh()];
        });
    }
}
