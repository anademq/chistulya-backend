<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\Enums\PaymentMethod;
use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateSubscriptionPaymentMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'createSubscriptionPayment',
        'description' => 'Create a pending payment invoice for a subscription purchase.',
    ];

    public function type(): Type
    {
        return GraphQL::type('PaymentPayload');
    }

    public function args(): array
    {
        return [
            'subscription_id' => ['type' => Type::nonNull(Type::string())],
            'method' => ['type' => Type::string(), 'defaultValue' => PaymentMethod::DEFAULT->value],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'subscription_id' => ['required', 'uuid', 'exists:subscriptions,id'],
            'method' => ['nullable', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['payment' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            /** @var Subscription $subscription */
            $subscription = Subscription::query()
                ->whereKey((string) $args['subscription_id'])
                ->where('is_available', true)
                ->firstOrFail();

            $method = PaymentMethod::from((string) ($args['method'] ?? PaymentMethod::DEFAULT->value));

            return ['payment' => app(PaymentService::class)->createSubscriptionPayment($user, $subscription, $method)];
        });
    }
}
