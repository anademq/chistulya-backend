<?php

namespace App\Support\Requirements;

use App\Support\Requirements\Contracts\JsonRequirements;
use App\Support\Requirements\Traits\InteractsWithJson;

class PetItemRequirements implements JsonRequirements
{
    use InteractsWithJson;

    protected bool $subscription = false;

    public function __construct(
        bool $subscription = false,
    ) {
        $this->subscription = $subscription;
    }

    public function isSubscriptionRequired(): bool
    {
        return (bool) $this->subscription ?? false;
    }

    public function setSubscriptionRequirement(bool $value = false): static
    {
        $this->subscription = $value;

        return $this;
    }

    public function flush(): static
    {
        $this->setSubscriptionRequirement(false);

        return $this;
    }

    public function isEmpty(): bool
    {
        return !$this->subscription;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public static function fromArray(array $data): static
    {
        return new static(
            subscription: (bool) $data['subscription'] ?? false,
        );
    }

    /**
     * @return array{subscription: bool}
     */
    public function toArray(): array
    {
        return [
            'subscription' => $this->subscription,
        ];
    }
}
