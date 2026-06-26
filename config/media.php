<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    | MIME types accepted for media uploads. Validated server-side after the
    | file reaches storage.
    */

    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/svg+xml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size (bytes)
    |--------------------------------------------------------------------------
    | Default: 10 MB.
    */

    'max_size' => (int) env('MEDIA_MAX_SIZE', 10 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Storage Path Prefixes
    |--------------------------------------------------------------------------
    | tmp_prefix  — pending uploads awaiting confirmation or entity attachment.
    | media_prefix — finalized files.
    */

    'tmp_prefix' => 'tmp/',

    'media_prefix' => 'media/',

];
