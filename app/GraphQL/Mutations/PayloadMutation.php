<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Exceptions\GraphQLThrottleException;
use App\Exceptions\InvalidActionException;
use App\GraphQL\Errors\InvalidActionError;
use App\GraphQL\Errors\RateLimitError;
use App\GraphQL\Errors\ValidationError;
use App\GraphQL\Errors\ValidationField;
use App\GraphQL\Mutation;
use Closure;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Error\ValidationError as RebingValidationError;

abstract class PayloadMutation extends Mutation
{
    /**
     * Override Rebing's resolver closure to catch validation, rate-limit, and
     * invalid-action exceptions, converting them into payload errors[] instead
     * of top-level GraphQL errors[].
     */
    protected function getResolver(): ?Closure
    {
        $resolver = parent::getResolver();

        if ($resolver === null) {
            return null;
        }

        return function () use ($resolver): mixed {
            try {
                return $resolver(...func_get_args());
            } catch (RebingValidationError $e) {
                return $this->failurePayload($this->buildValidationErrors($e->getValidatorMessages()->getMessages()));
            } catch (ValidationException $e) {
                return $this->failurePayload($this->buildValidationErrors($e->errors()));
            } catch (GraphQLThrottleException $e) {
                return $this->failurePayload([new RateLimitError($e->getMessage(), $e->retryAfter())]);
            } catch (InvalidActionException $e) {
                return $this->failurePayload([new InvalidActionError($e->getMessage())]);
            }
        };
    }

    /**
     * @param  callable(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    protected function wrapPayload(callable $resolver): array
    {
        try {
            return [...$this->emptyPayload(), 'success' => true, 'errors' => [], ...$resolver()];
        } catch (ValidationException $e) {
            return $this->failurePayload($this->buildValidationErrors($e->errors()));
        } catch (GraphQLThrottleException $e) {
            return $this->failurePayload([new RateLimitError($e->getMessage(), $e->retryAfter())]);
        } catch (InvalidActionException $e) {
            return $this->failurePayload([new InvalidActionError($e->getMessage())]);
        }
    }

    /** @return array<string, mixed> */
    protected function emptyPayload(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    private function failurePayload(array $errors): array
    {
        return [...$this->emptyPayload(), 'success' => false, 'errors' => $errors];
    }

    /**
     * Collapses all field messages into a single ValidationError object.
     *
     * @param  array<string, array<string>>  $messages
     * @return list<ValidationError>
     */
    private function buildValidationErrors(array $messages): array
    {
        $fields = [];

        foreach ($messages as $field => $fieldMessages) {
            $fields[] = new ValidationField((string) $field, array_values(array_map('strval', (array) $fieldMessages)));
        }

        if ($fields === []) {
            return [];
        }

        return [new ValidationError((string) __('errors.messages.validation'), $fields)];
    }
}
