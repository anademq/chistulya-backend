<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\Enums\ProfileRole;
use App\GraphQL\Mutations\AuthedMutation;
use App\Models\Profile;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class UpsertProfileMutation extends AuthedMutation
{
    protected $attributes = [
        'name' => 'upsertProfile',
        'description' => 'Creates or updates the profile for the currently authenticated user. Safe to call multiple times.',
    ];

    public function type(): Type
    {
        return GraphQL::type('UpsertProfilePayload');
    }

    public function args(): array
    {
        return [
            'name' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Display name of the user (max 255 characters).',
            ],
            'role' => [
                'type' => Type::string(),
                'description' => 'Profile role ("parent" or "child"). Cannot be changed once set.',
            ],
            'sex' => [
                'type' => Type::boolean(),
                'description' => 'Biological sex: true = male, false = female.',
            ],
            'date_of_birth' => [
                'type' => Type::string(),
                'description' => 'Date of birth in YYYY-MM-DD format.',
            ],
            'city' => [
                'type' => Type::string(),
                'description' => 'City name (max 128 characters).',
            ],
            'timezone' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Valid IANA timezone identifier (e.g. "Europe/Moscow"). Used to send reminders at the correct local time.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(array_column(ProfileRole::cases(), 'value'))],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'city' => ['nullable', 'string', 'max:128'],
            'timezone' => ['required', 'timezone:all'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['profile' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            /** @var Profile $profile */
            $profile = $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $args['name'],
                    'role' => $args['role'] ?? null,
                    'sex' => $args['sex'] ?? null,
                    'date_of_birth' => $args['date_of_birth'] ?? null,
                    'city' => $args['city'] ?? null,
                    'timezone' => $args['timezone'] ?? null,
                ]
            );

            return ['profile' => $profile];
        });
    }
}
