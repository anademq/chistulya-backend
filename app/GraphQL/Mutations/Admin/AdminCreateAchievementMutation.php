<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Achievement;
use App\Models\Media;
use App\Services\MediaService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminCreateAchievementMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'createAchievement',
        'description' => 'Admin: create a new achievement.',
    ];

    public function type(): Type
    {
        return GraphQL::type('AchievementPayload');
    }

    public function args(): array
    {
        return [
            'title' => ['type' => Type::nonNull(Type::string())],
            'short_description' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'is_available' => ['type' => Type::boolean(), 'defaultValue' => false],
            'requirements' => ['type' => GraphQL::type('AchievementRequirementsInput')],
            'reward_xp' => ['type' => Type::int(), 'defaultValue' => 0],
            'reward_coins' => ['type' => Type::int(), 'defaultValue' => 0],
            'media_id' => ['type' => Type::string()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
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
            $achievement = Achievement::create([
                'title' => $args['title'],
                'short_description' => $args['short_description'] ?? null,
                'description' => $args['description'] ?? null,
                'is_available' => (bool) ($args['is_available'] ?? false),
                'requirements' => $args['requirements'] ?? null,
                'reward_xp' => (int) ($args['reward_xp'] ?? 0),
                'reward_coins' => (int) ($args['reward_coins'] ?? 0),
            ]);

            if (!empty($args['media_id'])) {
                $media = Media::whereKey($args['media_id'])->firstOrFail();
                app(MediaService::class)->attachToEntity($media, $achievement);
            }

            return ['achievement' => $achievement->refresh()];
        });
    }
}
