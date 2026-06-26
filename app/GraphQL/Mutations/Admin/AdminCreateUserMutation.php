<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Enums\UserRole;
use App\Exceptions\AuthorizationException;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminCreateUserMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'createUser',
        'description' => 'Admin: create a new user account. Only sudo_admin may assign the admin role.',
    ];

    public function type(): Type
    {
        return GraphQL::type('UserPayload');
    }

    public function args(): array
    {
        return [
            'email' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The new user\'s email address.',
            ],
            'password' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The new user\'s initial password.',
            ],
            'role' => [
                'type' => Type::string(),
                'description' => 'User role: "user" (default) or "admin" (sudo_admin only).',
            ],
            'email_verified' => [
                'type' => Type::boolean(),
                'description' => 'Mark the email as already verified. Defaults to false.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['nullable', 'string', Rule::in([UserRole::USER->value, UserRole::ADMIN->value])],
            'email_verified' => ['nullable', 'boolean'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['user' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $role = UserRole::tryFrom((string) ($args['role'] ?? UserRole::USER->value)) ?? UserRole::USER;

            if ($role === UserRole::ADMIN) {
                /** @var User $actor */
                $actor = auth()->user();

                if ($actor->role !== UserRole::SUDO_ADMIN) {
                    throw AuthorizationException::forbidden();
                }
            }

            $user = User::create([
                'email' => Str::lower((string) $args['email']),
                'password' => (string) $args['password'],
                'role' => $role,
                'email_verified_at' => ($args['email_verified'] ?? false) ? now() : null,
            ]);

            return ['user' => $user->load('profile')];
        });
    }
}
