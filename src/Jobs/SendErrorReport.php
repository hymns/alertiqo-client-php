<?php

declare(strict_types=1);

namespace Alertiqo\Laravel\Jobs;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Send Error Report Job
 *
 * Queued job for sending error reports to the Alertiqo backend asynchronously.
 */
class SendErrorReport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The error report data.
     *
     * @var array<string, mixed>
     */
    protected array $report;

    /**
     * The configuration array.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 30;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     *
     * @param array<string, mixed> $report
     * @param array<string, mixed> $config
     */
    public function __construct(array $report, array $config)
    {
        $this->report = $report;
        $this->config = $config;

        if (isset($config['queue'])) {
            $this->onQueue($config['queue']);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $client = new Client([
                'base_uri' => $this->config['endpoint'],
                'timeout' => $this->config['timeout'] ?? 5,
            ]);

            $client->post('/api/errors', [
                'json' => $this->report,
                'headers' => [
                    'X-API-Key' => $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (Exception $e) {
            if ($this->config['debug'] ?? false) {
                Log::error('Alertiqo: Failed to send error report from queue', [
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                ]);
            }

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 60);
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        if ($this->config['debug'] ?? false) {
            Log::error('Alertiqo: Error report failed after all retries', [
                'error' => $exception->getMessage(),
                'report_id' => $this->report['id'] ?? 'unknown',
            ]);
        }
    }
}
