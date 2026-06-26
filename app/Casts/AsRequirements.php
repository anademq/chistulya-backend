<?php

namespace App\Casts;

use App\Support\Requirements\Contracts\JsonRequirements;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class AsRequirements implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<JsonRequirements>
     *
     * @throws \InvalidArgumentException
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class ($arguments) implements CastsAttributes {

            public function __construct(protected array $arguments)
            {
            }

            /**
             * Prepare the given value for storage.
             *
             * @param  array<string, mixed>  $attributes
             */
            public function get(Model $model, string $key, mixed $value, array $attributes): mixed
            {
                $class = $this->arguments[0] ?? null;

                if (blank($class)) {
                    throw new InvalidArgumentException('The requirements class must be provided.');
                }

                if (!is_a($class, JsonRequirements::class, true)) {
                    throw new InvalidArgumentException('The provided class must implement [' . JsonRequirements::class . '].');
                }

                $decoded = isset($attributes[$key])
                ? Json::decode($attributes[$key])
                : null;

                return $class::fromArray(is_array($decoded) ? $decoded : []);
            }

            /**
             * Prepare the given value for storage.
             *
             * @param  array<string, mixed>  $attributes
             */
            public function set(Model $model, string $key, mixed $value, array $attributes): mixed
            {
                return [$key => Json::encode($value)];
            }
        };
    }

    /**
     * Specify the requirements class for the cast.
     *
     * @param  class-string  $class
     * @return string
     */
    public static function using(string $class): string
    {
        return static::class . ':' . $class;
    }
}
