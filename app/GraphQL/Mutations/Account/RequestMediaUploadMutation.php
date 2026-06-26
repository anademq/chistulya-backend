<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\GraphQL\Mutations\AuthedMutation;
use App\Models\User;
use App\Services\MediaService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class RequestMediaUploadMutation extends AuthedMutation
{
    protected $attributes = [
        'name' => 'requestMediaUpload',
        'description' => 'Step 1 of 2: request a pre-signed upload URL. Returns a media record ID and a temporary PUT URL. Upload your file directly to that URL, then pass the media ID to confirmMediaUpload or an entity mutation to finalize.',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestMediaUploadPayload');
    }

    public function args(): array
    {
        return [
            'file_name' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Original file name including extension (e.g. "avatar.jpg").',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'file_name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['media_id' => null, 'upload_url' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            return app(MediaService::class)->requestUpload(
                uploader: $user,
                fileName: (string) $args['file_name'],
            );
        });
    }
}
