<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Enums\DailyTaskScope;
use App\Models\DailyTask;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminCreateDailyTaskMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'createDailyTask',
        'description' => 'Admin: create a new daily task.',
    ];

    public function type(): Type
    {
        return GraphQL::type('DailyTaskPayload');
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
            'media_id' => ['type' => Type::string()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'scope' => ['required', Rule::in(array_column(DailyTaskScope::cases(), 'value'))],
            'category_id' => ['required', 'integer', 'exists:daily_task_categories,id'],
            'title' => ['required', 'string', 'max:150'],
            'short_description' => ['nullable', 'string', 'max:250'],
            'description' => ['nullable', 'string'],
            'reward_xp' => ['nullable', 'integer', 'min:0'],
            'reward_coins' => ['nullable', 'integer', 'min:0'],
            'media_id' => ['nullable', 'uuid', 'exists:media,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['daily_task' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $admin */
        $admin = auth()->user();

        return $this->wrapPayload(function () use ($admin, $args): array {
            $task = DailyTask::create([
                'created_by' => $admin->id,
                'scope' => $args['scope'],
                'category_id' => (int) $args['category_id'],
                'title' => $args['title'],
                'short_description' => $args['short_description'] ?? null,
                'description' => $args['description'] ?? null,
                'reward_xp' => (int) ($args['reward_xp'] ?? 0),
                'reward_coins' => (int) ($args['reward_coins'] ?? 0),
            ]);

            if (!empty($args['media_id'])) {
                $media = Media::whereKey($args['media_id'])->firstOrFail();
                app(MediaService::class)->attachToEntity($media, $task);
            }

            return ['daily_task' => $task->refresh()->load('category')];
        });
    }
}
