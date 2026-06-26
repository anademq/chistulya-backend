<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\GraphQL\Middleware\Contracts\HasThrottleMessage;
use App\GraphQL\Mutations\AuthedMutation;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class RequestEmailVerificationMutation extends AuthedMutation implements HasThrottleMessage
{
    public function throttleMessage(): string
    {
        return (string) __('errors.messages.throttle.request_email_verification');
    }

    public function __construct()
    {
        parent::__construct();
        $this->withMiddleware('graphql.throttle:1,1');
    }

    protected $attributes = [
        'name' => 'requestEmailVerification',
        'description' => 'Sends a new email verification link to the authenticated user. Rate-limited to once per minute.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function (): array {
            /** @var User $user */
            $user = auth()->user();

            $user->sendEmailVerificationNotification();

            return [];
        });
    }
}
