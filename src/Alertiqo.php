<?php

declare(strict_types=1);

namespace Alertiqo\Laravel;

use Alertiqo\Laravel\Jobs\SendErrorReport;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Alertiqo Client
 *
 * Main client class for capturing and sending error reports to the Alertiqo backend.
 */
class Alertiqo
{
    /**
     * Configuration array.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Breadcrumbs for tracking user actions.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $breadcrumbs = [];

    /**
     * HTTP client instance.
     *
     * @var Client
     */
    protected Client $httpClient;

    /**
     * Create a new Alertiqo instance.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'base_uri' => $config['endpoint'],
            'timeout' => $config['timeout'] ?? 5,
        ]);
    }

    /**
     * Capture and report an exception.
     *
     * @param Throwable $exception
     * @param array<string, mixed> $additionalData
     * @return void
     */
    public function captureException(Throwable $exception, array $additionalData = []): void
    {
        if (!$this->config['enabled']) {
            return;
        }
               
        // Generate a unique fingerprint for this error
        $fingerprint = md5(
            $exception->getMessage() . 
            $exception->getFile() . 
            $exception->getLine() . 
            $exception->getCode()
        );
        // Use cache to prevent duplicate reports for the same error within 5 minutes
        $cacheKey = "alertiqo:error:{$fingerprint}";
        if (cache()->has($cacheKey)) {
            return;
        }
        cache()->put($cacheKey, true, now()->addMinutes(5));
        // Check sample rate
        if (!$this->shouldCapture()) {
            return;
        }
        $report = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack' => $this->formatStackTrace($exception),
            'level' => 'error',
            'timestamp' => now()->timestamp * 1000,
            'environment' => $this->config['environment'],
            'release' => $this->config['release'] ?? null,
            'fingerprint' => $fingerprint,
            'tags' => array_merge($this->config['tags'] ?? [], $additionalData['tags'] ?? []),
            'context' => $this->getContext(),
            'breadcrumbs' => $this->breadcrumbs,
        ];
        $this->sendReport($report);
    } 

    /**
     * Capture and report a message.
     *
     * @param string $message
     * @param string $level
     * @param array<string, mixed> $additionalData
     * @return void
     */
    public function captureMessage(string $message, string $level = 'info', array $additionalData = []): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        // Check sample rate (skip for critical messages)
        if ($level !== 'error' && $level !== 'critical' && !$this->shouldCapture()) {
            return;
        }

        $report = [
            'message' => $message,
            'level' => $level,
            'timestamp' => now()->timestamp * 1000,
            'environment' => $this->config['environment'],
            'release' => $this->config['release'] ?? null,
            'tags' => array_merge($this->config['tags'] ?? [], $additionalData['tags'] ?? []),
            'context' => $this->getContext(),
            'breadcrumbs' => $this->breadcrumbs,
        ];

        $this->sendReport($report);
    }

    /**
     * Add a breadcrumb for tracking user actions.
     *
     * @param array<string, mixed> $breadcrumb
     * @return void
     */
    public function addBreadcrumb(array $breadcrumb): void
    {
        $this->breadcrumbs[] = array_merge($breadcrumb, [
            'timestamp' => now()->timestamp * 1000,
        ]);

        // Keep only last 100 breadcrumbs
        if (count($this->breadcrumbs) > 100) {
            array_shift($this->breadcrumbs);
        }
    }

    /**
     * Set the current user context.
     *
     * @param array<string, mixed> $user
     * @return void
     */
    public function setUser(array $user): void
    {
        $this->config['user'] = $user;
    }

    /**
     * Set a single tag.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setTag(string $key, string $value): void
    {
        if (!isset($this->config['tags'])) {
            $this->config['tags'] = [];
        }
        $this->config['tags'][$key] = $value;
    }

    /**
     * Set multiple tags at once.
     *
     * @param array<string, string> $tags
     * @return void
     */
    public function setTags(array $tags): void
    {
        $this->config['tags'] = array_merge($this->config['tags'] ?? [], $tags);
    }

    /**
     * Get the current request context.
     *
     * @return array<string, mixed>
     */
    protected function getContext(): array
    {
        $context = [
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'userAgent' => request()->userAgent(),
        ];
        // Add user info if authenticated
        if (Auth::check()) {
            $user = Auth::user();
            $context['user'] = [
                'id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'name' => $user->name ?? null,
            ];
        }
        // Add request data
        if ($this->config['send_request_data'] ?? true) {
            $context['request'] = [
                'query' => request()->query(),
                'body' => $this->sanitizeData(request()->all()),
                'headers' => $this->sanitizeHeaders(
                    collect(request()->header())->mapWithKeys(function ($value, $key) {
                        return [$key => $value[0] ?? null];
                    })->toArray()
                ),
            ];
        }
        return $context;
    }

    /**
     * Format the stack trace for an exception.
     *
     * @param Throwable $exception
     * @return string
     */
    protected function formatStackTrace(Throwable $exception): string
    {
        $trace = $exception->getTraceAsString();
        return sprintf(
            "%s in %s:%d\nStack trace:\n%s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $trace
        );
    }

    /**
     * Send an error report to the backend.
     *
     * @param array<string, mixed> $report
     * @return void
     */
    protected function sendReport(array $report): void
    {
        try {
            if ($this->config['use_queue'] ?? true) {
                SendErrorReport::dispatch($report, $this->config)
                    ->onQueue($this->config['queue'] ?? 'default');
            } else {
                $this->sendSync($report);
            }
        } catch (\Throwable $e) {
            // Silently fail - don't break the app
            if ($this->config['debug'] ?? false) {
                \Log::error('Alertiqo: Failed to send report', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Determine if queue should be used for sending reports.
     *
     * @return bool
     */
    protected function shouldUseQueue(): bool
    {
        return ($this->config['use_queue'] ?? false) 
            && interface_exists('Illuminate\Contracts\Queue\ShouldQueue');
    }

    /**
     * Dispatch report to queue for async processing.
     *
     * @param array<string, mixed> $report
     * @return void
     */
    protected function dispatchToQueue(array $report): void
    {
        try {
            SendErrorReport::dispatch($report, $this->config);
        } catch (\Exception $e) {
            // Fallback to sync if queue dispatch fails
            if ($this->config['debug']) {
                \Log::warning('Alertiqo: Queue dispatch failed, falling back to sync', [
                    'error' => $e->getMessage()
                ]);
            }
            $this->sendSync($report);
        }
    }

    /**
     * Send report synchronously via HTTP.
     *
     * @param array<string, mixed> $report
     * @return void
     */
    protected function sendSync(array $report): void
    {
        try {
            $this->httpClient->post('/api/errors', [
                'json' => $report,
                'headers' => [
                    'X-API-Key' => $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (\Exception $e) {
            // Silently fail - don't break the app
            if ($this->config['debug'] ?? false) {
                \Log::error('Alertiqo: API error', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Sanitize sensitive data from request.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function sanitizeData(array $data): array
    {
        $sensitiveKeys = $this->config['sensitive_keys'] ?? [
            'password', 'password_confirmation', 'token', 'api_key', 
            'secret', 'credit_card', 'cvv'
        ];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '[FILTERED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeData($value);
            }
        }

        return $data;
    }

    /**
     * Sanitize sensitive headers.
     *
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $headers[$key] = '[FILTERED]';
            }
        }
        return $headers;
    }

    /**
     * Determine if error should be captured based on sample rate.
     *
     * @return bool
     */
    protected function shouldCapture(): bool
    {
        $sampleRate = $this->config['sample_rate'] ?? 1.0;
        
        // Always capture if sample rate is 1.0 (100%)
        if ($sampleRate >= 1.0) {
            return true;
        }

        // Never capture if sample rate is 0
        if ($sampleRate <= 0) {
            return false;
        }

        // Random sampling
        return (mt_rand() / mt_getrandmax()) <= $sampleRate;
    }
}
