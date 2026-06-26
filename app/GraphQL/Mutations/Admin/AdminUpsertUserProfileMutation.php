<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Enums\ProfileRole;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminUpsertUserProfileMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'upsertUserProfile',
        'description' => 'Admin: create or update a user\'s profile fields (name, sex, role, date_of_birth, city, timezone).',
    ];

    public function type(): Type
    {
        return GraphQL::type('UserPayload');
    }

    public function args(): array
    {
        return [
            'user_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the user whose profile to upsert.',
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'Display name.',
            ],
            'sex' => [
                'type' => Type::boolean(),
                'description' => 'Sex (true = male, false = female).',
            ],
            'role' => [
                'type' => Type::string(),
                'description' => 'Profile role: "child" or "parent".',
            ],
            'date_of_birth' => [
                'type' => Type::string(),
                'description' => 'Date of birth in Y-m-d format.',
            ],
            'city' => [
                'type' => Type::string(),
                'description' => 'City name.',
            ],
            'timezone' => [
                'type' => Type::string(),
                'description' => 'IANA timezone identifier.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'boolean'],
            'role' => ['nullable', 'string', Rule::in(array_column(ProfileRole::cases(), 'value'))],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'city' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['user' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $user = User::whereKey($args['user_id'])->firstOrFail();

            $profile = $user->profile()->firstOrNew(['user_id' => $user->id]);

            $fields = array_filter(
                array_intersect_key($args, array_flip(['name', 'sex', 'role', 'date_of_birth', 'city', 'timezone'])),
                static fn($v) => $v !== null,
            );

            if (!empty($fields)) {
                foreach ($fields as $key => $value) {
                    $profile->{$key} = $value;
                }

                $profile->save();
            }

            return ['user' => $user->refresh()->load('profile')];
        });
    }
}
