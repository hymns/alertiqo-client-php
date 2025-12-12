<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Alertiqo
    |--------------------------------------------------------------------------
    |
    | Set to true to enable error tracking. You might want to disable this
    | in local development environment.
    |
    */

    'enabled' => env('ALERTIQO_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your Alertiqo API key from the dashboard.
    |
    */

    'api_key' => env('ALERTIQO_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Endpoint
    |--------------------------------------------------------------------------
    |
    | The Alertiqo backend endpoint URL.
    |
    */

    'endpoint' => env('ALERTIQO_ENDPOINT', 'https://alertiqo.hamizi.net/api'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | The environment name (e.g., production, staging, development).
    |
    */

    'environment' => env('ALERTIQO_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Release
    |--------------------------------------------------------------------------
    |
    | Release version or commit hash.
    |
    */

    'release' => env('ALERTIQO_RELEASE', null),

    /*
    |--------------------------------------------------------------------------
    | Default Tags
    |--------------------------------------------------------------------------
    |
    | Tags that will be added to all error reports.
    |
    */

    'tags' => [
        'app' => env('APP_NAME', 'Laravel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | HTTP request timeout in seconds.
    |
    */

    'timeout' => 5,

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    |
    | Enable debug logging for Alertiqo itself.
    |
    */

    'debug' => env('ALERTIQO_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Use Queue
    |--------------------------------------------------------------------------
    |
    | Enable queue-based error reporting for better performance.
    | Requires Laravel queue to be configured.
    |
    */

    'use_queue' => env('ALERTIQO_USE_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | The queue name to use for error reporting jobs.
    | Leave null to use the default queue.
    |
    */

    'queue' => env('ALERTIQO_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Send Request Data
    |--------------------------------------------------------------------------
    |
    | Include request query params, body, and headers in error reports.
    |
    */

    'send_request_data' => true,

    /*
    |--------------------------------------------------------------------------
    | Log SQL Queries
    |--------------------------------------------------------------------------
    |
    | Automatically capture SQL queries as breadcrumbs.
    | Useful for debugging database-related issues.
    |
    */

    'log_sql' => env('ALERTIQO_LOG_SQL', false),

    /*
    |--------------------------------------------------------------------------
    | SQL Query Threshold
    |--------------------------------------------------------------------------
    |
    | Only log queries that take longer than this threshold (in milliseconds).
    | Set to 0 to log all queries.
    |
    */

    'sql_threshold' => env('ALERTIQO_SQL_THRESHOLD', 0),

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable automatic performance monitoring for slow requests.
    |
    */

    'performance_monitoring' => env('ALERTIQO_PERFORMANCE_MONITORING', false),

    /*
    |--------------------------------------------------------------------------
    | Performance Threshold
    |--------------------------------------------------------------------------
    |
    | Report requests that take longer than this threshold (in milliseconds).
    |
    */

    'performance_threshold' => env('ALERTIQO_PERFORMANCE_THRESHOLD', 1000),

    /*
    |--------------------------------------------------------------------------
    | Sample Rate
    |--------------------------------------------------------------------------
    |
    | Sample rate for error capturing (0.0 to 1.0).
    | 1.0 = capture all errors, 0.1 = capture 10% of errors.
    |
    */

    'sample_rate' => env('ALERTIQO_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Sensitive Keys
    |--------------------------------------------------------------------------
    |
    | Keys that should be filtered out from request data.
    |
    */

    'sensitive_keys' => [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'cvv',
        'ssn',
    ],

    /*
    |--------------------------------------------------------------------------
    | Don't Report
    |--------------------------------------------------------------------------
    |
    | Exception classes that should not be reported to Alertiqo.
    |
    */

    'dont_report' => [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
    ],

];
