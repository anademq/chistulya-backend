<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Enums\DailyTaskScope;
use App\Models\DailyTask;
use App\Models\Media;
use App\Services\MediaService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminUpdateDailyTaskMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'updateDailyTask',
        'description' => 'Admin: update an existing daily task.',
    ];

    public function type(): Type
    {
        return GraphQL::type('DailyTaskPayload');
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
            'media_id' => ['type' => Type::string()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:daily_tasks,id'],
            'scope' => ['nullable', Rule::in(array_column(DailyTaskScope::cases(), 'value'))],
            'category_id' => ['nullable', 'integer', 'exists:daily_task_categories,id'],
            'title' => ['nullable', 'string', 'max:150'],
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
        return $this->wrapPayload(function () use ($args): array {
            $task = DailyTask::withTrashed()->whereKey((string) $args['id'])->firstOrFail();

            $fields = array_filter(
                array_intersect_key($args, array_flip(['scope', 'category_id', 'title', 'short_description', 'description', 'reward_xp', 'reward_coins'])),
                static fn($v) => $v !== null,
            );

            if (!empty($fields)) {
                $task->forceFill($fields)->save();
            }

            if (!empty($args['media_id'])) {
                $media = Media::whereKey($args['media_id'])->firstOrFail();
                app(MediaService::class)->attachToEntity($media, $task);
            }

            return ['daily_task' => $task->refresh()->load('category')];
        });
    }
}
