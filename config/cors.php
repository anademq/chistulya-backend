<?php

declare(strict_types=1);

/**
 * Split a comma-separated env value into a clean list.
 *
 * @return list<string>
 */
$list = static function (string $key, string $default = ''): array {
    return array_values(array_filter(
        array_map('trim', explode(',', (string) env($key, $default))),
        static fn(string $value): bool => $value !== '',
    ));
};

/**
 * Add the punycode form of every origin that carries a non-ASCII host.
 *
 * @param  list<string>  $origins
 * @return list<string>
 */
$withPunycode = static function (array $origins): array {
    $expanded = [];

    foreach ($origins as $origin) {
        $expanded[] = $origin;

        $host = parse_url($origin, PHP_URL_HOST);

        if (!is_string($host) || mb_check_encoding($host, 'ASCII')) {
            continue;
        }

        $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        if (is_string($ascii) && $ascii !== $host) {
            $expanded[] = str_replace($host, $ascii, $origin);
        }
    }

    return array_values(array_unique($expanded));
};

return [

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    */

    'paths' => $list('CORS_PATHS', 'graphql,graphql/*,broadcasting/auth,up'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    */

    'allowed_methods' => $list('CORS_ALLOWED_METHODS', '*'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    | Never combine "*" with CORS_SUPPORTS_CREDENTIALS=true — browsers reject
    | that pair outright.
    */

    'allowed_origins' => $withPunycode(
        $list('CORS_ALLOWED_ORIGINS', (string) env('APP_FRONTEND_URL', '')),
    ),

    /*
    |--------------------------------------------------------------------------
    | Allowed Origin Patterns
    |--------------------------------------------------------------------------
    | Regular expressions, useful for preview deployments, e.g.
    | CORS_ALLOWED_ORIGIN_PATTERNS="#^https://.*\.vercel\.app$#"
    */

    'allowed_origins_patterns' => $list('CORS_ALLOWED_ORIGIN_PATTERNS'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    */

    'allowed_headers' => $list('CORS_ALLOWED_HEADERS', '*'),

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    | Response headers the browser lets client JavaScript read.
    */

    'exposed_headers' => $list('CORS_EXPOSED_HEADERS'),

    /*
    |--------------------------------------------------------------------------
    | Max Age (seconds)
    |--------------------------------------------------------------------------
    | How long a browser may cache the preflight response. Default: 24 hours.
    */

    'max_age' => (int) env('CORS_MAX_AGE', 86400),

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    */

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
