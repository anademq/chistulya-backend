<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'disk' => 's3',
            'path' => 'tmp/' . Str::uuid7(),
            'file_name' => fake()->word() . '.' . fake()->fileExtension(),
            'mime_type' => null,
            'size' => null,
            'uploaded_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_at' => null,
            'mime_type' => null,
            'size' => null,
        ]);
    }

    public function uploaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'path' => 'media/' . Str::uuid7(),
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'uploaded_at' => now(),
        ]);
    }
}
