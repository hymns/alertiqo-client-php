<?php

declare(strict_types=1);

namespace Alertiqo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Performance Monitoring Middleware
 *
 * Tracks request duration, memory usage, and query count for slow request detection.
 */
class PerformanceMonitoring
{
    /**
     * Request start time.
     *
     * @var float
     */
    protected float $startTime;

    /**
     * Memory usage at request start.
     *
     * @var int
     */
    protected int $startMemory;

    /**
     * Number of database queries executed.
     *
     * @var int
     */
    protected int $queryCount = 0;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('alertiqo.performance_monitoring', false)) {
            return $next($request);
        }

        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
        $this->queryCount = 0;

        // Count queries
        DB::listen(function ($query) {
            $this->queryCount++;
        });

        $response = $next($request);

        $this->capturePerformanceMetrics($request, $response);

        return $response;
    }

    /**
     * Capture and report performance metrics.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function capturePerformanceMetrics(Request $request, Response $response): void
    {
        $duration = (microtime(true) - $this->startTime) * 1000; // Convert to ms
        $memoryUsed = (memory_get_usage() - $this->startMemory) / 1024 / 1024; // Convert to MB
        $peakMemory = memory_get_peak_usage() / 1024 / 1024;

        $threshold = config('alertiqo.performance_threshold', 1000); // Default 1 second

        // Only report if request is slow
        if ($duration > $threshold) {
            app('alertiqo')->captureMessage('Slow request detected', 'warning', [
                'tags' => [
                    'type' => 'performance',
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status' => $response->status(),
                ],
            ]);

            app('alertiqo')->addBreadcrumb([
                'message' => 'Performance Metrics',
                'category' => 'performance',
                'level' => 'warning',
                'data' => [
                    'duration_ms' => round($duration, 2),
                    'memory_used_mb' => round($memoryUsed, 2),
                    'peak_memory_mb' => round($peakMemory, 2),
                    'query_count' => $this->queryCount,
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'status_code' => $response->status(),
                ],
            ]);
        }

        // Always add basic metrics as breadcrumb for context
        app('alertiqo')->addBreadcrumb([
            'message' => 'Request completed',
            'category' => 'http',
            'level' => 'info',
            'data' => [
                'duration_ms' => round($duration, 2),
                'query_count' => $this->queryCount,
                'status' => $response->status(),
            ],
        ]);
    }
}
