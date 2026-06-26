<?php

namespace App\Support\Requirements;

use App\Models\Challenge;
use App\Models\DailyTask;
use App\Support\Requirements\Contracts\JsonRequirements;
use App\Support\Requirements\Traits\InteractsWithJson;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class AchievementRequirements implements JsonRequirements
{
    use InteractsWithJson;

    protected bool $subscription = false;

    /**
     * @var Collection<int, string>
     */
    protected Collection $dailyTasks;

    /**
     * @var Collection<int, string>
     */
    protected Collection $challenges;

    public function __construct(
        bool $subscription = false,
        Collection|Arrayable|iterable|null $dailyTasks = null,
        Collection|Arrayable|iterable|null $challenges = null,
    ) {
        $this->subscription = $subscription;
        $this->dailyTasks = $dailyTasks instanceof Collection ? $dailyTasks : new Collection($dailyTasks);
        $this->challenges = $challenges instanceof Collection ? $challenges : new Collection($challenges);
    }

    public function isSubscriptionRequired(): bool
    {
        return (bool) $this->subscription ?? false;
    }

    public function hasDailyTasks(): bool
    {
        return $this->dailyTasks->isNotEmpty();
    }

    public function hasChallenges(): bool
    {
        return $this->challenges->isNotEmpty();
    }

    /**
     * @return Collection<int, string>
     */
    public function dailyTasks(): Collection
    {
        return $this->dailyTasks;
    }

    /**
     * @return Collection<int, string>
     */
    public function challenges(): Collection
    {
        return $this->challenges;
    }

    public function setSubscriptionRequirement(bool $value = false): static
    {
        $this->subscription = $value;

        return $this;
    }

    /**
     * @param  Collection<int, DailyTask|string>|Arrayable<int, DailyTask|string>|iterable<int, DailyTask|string>|null  $ids
     */
    public function setDailyTasks(Collection|Arrayable|iterable|null $ids = null): static
    {
        if (!$ids instanceof Collection) {
            $ids = new Collection($ids);
        }

        $ids = $ids
            ->map(fn(mixed $value): mixed => $value instanceof DailyTask ? $value->id : $value)
            ->filter(fn(mixed $value): bool => is_string($value) && filled($value))
            ->map(fn(string $value): string => trim($value))
            ->values();

        $this->dailyTasks = $ids;

        return $this;
    }

    /**
     * @param  Collection<int, Challenge|string>|Arrayable<int, Challenge|string>|iterable<int, Challenge|string>|null  $ids
     */
    public function setChallenges(Collection|Arrayable|iterable|null $ids = null): static
    {
        if (!$ids instanceof Collection) {
            $ids = new Collection($ids);
        }

        $ids = $ids
            ->map(fn(mixed $value): mixed => $value instanceof Challenge ? $value->id : $value)
            ->filter(fn(mixed $value): bool => is_string($value) && filled($value))
            ->map(fn(string $value): string => trim($value))
            ->values();

        $this->challenges = $ids;

        return $this;
    }

    public function addDailyTask(DailyTask|string $id): static
    {
        if ($id instanceof DailyTask) {
            $id = $id->id;
        }

        if (filled($id) && !$this->dailyTasks->contains($id)) {
            $this->dailyTasks->push($id);
        }

        return $this;
    }

    public function addChallenge(Challenge|string $id): static
    {
        if ($id instanceof Challenge) {
            $id = $id->id;
        }

        if (filled($id) && !$this->challenges->contains($id)) {
            $this->challenges->push($id);
        }

        return $this;
    }

    public function removeDailyTask(DailyTask|string $id): static
    {
        if ($id instanceof DailyTask) {
            $id = $id->id;
        }

        $this->dailyTasks = $this->dailyTasks
            ->reject(static fn(string $value): bool => $value === $id)
            ->values();

        return $this;
    }

    public function removeChallenge(Challenge|string $id): static
    {
        if ($id instanceof Challenge) {
            $id = $id->id;
        }

        $this->challenges = $this->challenges
            ->reject(static fn(string $value): bool => $value === $id)
            ->values();

        return $this;
    }

    public function flushDailyTasks(): static
    {
        $this->dailyTasks = new Collection();

        return $this;
    }

    public function flushChallenges(): static
    {
        $this->challenges = new Collection();

        return $this;
    }

    public function flush(): static
    {
        $this->setSubscriptionRequirement(false);
        $this->flushDailyTasks();
        $this->flushChallenges();

        return $this;
    }

    public function isEmpty(): bool
    {
        return !$this->subscription
            && $this->dailyTasks->isEmpty()
            && $this->challenges->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public static function fromArray(array $data): static
    {
        return new static(
            subscription: (bool) $data['subscription'] ?? false,
            dailyTasks: $data['daily_tasks'] ?? [],
            challenges: $data['challenges'] ?? [],
        );
    }

    /**
     * @return array{subscription: bool, daily_tasks: list<string>, challenges: list<string>}
     */
    public function toArray(): array
    {
        return [
            'subscription' => $this->subscription,
            'daily_tasks' => $this->dailyTasks->toArray(),
            'challenges' => $this->challenges->toArray(),
        ];
    }
}
