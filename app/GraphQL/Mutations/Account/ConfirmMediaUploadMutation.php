<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\GraphQL\Mutations\AuthedMutation;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ConfirmMediaUploadMutation extends AuthedMutation
{
    protected $attributes = [
        'name' => 'confirmMediaUpload',
        'description' => 'Confirm that a file has been successfully uploaded to S3. Call this after completing the PUT request to the pre-signed URL returned by requestMediaUpload.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ConfirmMediaUploadPayload');
    }

    public function args(): array
    {
        return [
            'media_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the pending media record returned by requestMediaUpload.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'media_id' => ['required', 'uuid', 'exists:media,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['media' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            $media = Media::whereKey($args['media_id'])->firstOrFail();

            if ($media->created_by !== $user->id && ! $user->isAdminUser()) {
                throw ValidationException::withMessages([
                    'media_id' => __('validation.exists', ['attribute' => 'media_id']),
                ]);
            }

            return ['media' => app(MediaService::class)->confirmUpload($media)];
        });
    }
}
