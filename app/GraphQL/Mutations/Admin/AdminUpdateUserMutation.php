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

class AdminUpdateUserMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'updateUser',
        'description' => 'Admin: update a user\'s email, password, or role. Only sudo_admin may assign the admin role. The sudo_admin role cannot be set via this API.',
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
                'description' => 'UUID of the user to update.',
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'New email address.',
            ],
            'password' => [
                'type' => Type::string(),
                'description' => 'New plain-text password.',
            ],
            'role' => [
                'type' => Type::string(),
                'description' => 'New role: "user" or "admin". Only sudo_admin may set "admin".',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($args['user_id'] ?? null)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', 'string', Rule::in([UserRole::USER->value, UserRole::ADMIN->value])],
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

            if (isset($args['email']) && $args['email'] !== null) {
                $user->forceFill(['email' => Str::lower((string) $args['email'])])->save();
            }

            if (isset($args['password']) && $args['password'] !== null) {
                $user->forceFill(['password' => (string) $args['password']])->save();
            }

            if (isset($args['role']) && $args['role'] !== null) {
                $role = UserRole::tryFrom((string) $args['role']);

                if ($role === UserRole::ADMIN) {
                    /** @var User $actor */
                    $actor = auth()->user();

                    if ($actor->role !== UserRole::SUDO_ADMIN) {
                        throw AuthorizationException::forbidden();
                    }
                }

                $user->forceFill(['role' => $role])->save();
            }

            return ['user' => $user->refresh()->load('profile')];
        });
    }
}
