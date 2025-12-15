<?php

declare(strict_types=1);

namespace Alertiqo\Laravel;

use Alertiqo\Laravel\Http\Middleware\CaptureErrors;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Alertiqo Service Provider
 *
 * Registers and bootstraps the Alertiqo client for Laravel applications.
 */
class AlertiqoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/alertiqo.php', 'alertiqo');

        $this->app->singleton('alertiqo', function ($app) {
            return new Alertiqo(config('alertiqo'));
        });

        $this->app->alias('alertiqo', Alertiqo::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/alertiqo.php' => config_path('alertiqo.php'),
            ], 'alertiqo-client-config');
        }

        if (!$this->app->runningInConsole()) {
            $this->app['router']->aliasMiddleware('alertiqo.capture', CaptureErrors::class);
        }

        // Auto-register exception handler for Laravel 11+
        $this->registerExceptionReporting();
    }

    /**
     * Register exception reporting for all Laravel versions.
     *
     * @return void
     */
    protected function registerExceptionReporting(): void
    {
        if (!config('alertiqo.enabled', true)) {
            return;
        }

        $handler = $this->app->make(ExceptionHandler::class);

        // Use reportable() which works in Laravel 9, 10, 11, 12
        $handler->reportable(function (Throwable $e) {
            if ($this->shouldReport($e)) {
                try {
                    app('alertiqo')->captureException($e);
                } catch (Throwable $reportingError) {
                    if (config('alertiqo.debug', false)) {
                        \Log::error('Alertiqo: Failed to capture exception', [
                            'error' => $reportingError->getMessage()
                        ]);
                    }
                }
            }
        })->stop(false); // Don't stop default reporting
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param Throwable $e
     * @return bool
     */
    protected function shouldReport(Throwable $e): bool
    {
        $dontReport = config('alertiqo.dont_report', []);

        foreach ($dontReport as $type) {
            if ($e instanceof $type) {
                return false;
            }
        }

        return true;
    }

    /**
     * Register the query listener for SQL breadcrumbs.
     *
     * @return void
     */
    protected function registerQueryListener(): void
    {
        DB::listen(function (QueryExecuted $query): void {
            $threshold = config('alertiqo.sql_threshold', 0);

            if ($threshold > 0 && $query->time < $threshold) {
                return;
            }

            app('alertiqo')->addBreadcrumb([
                'message' => 'Database Query',
                'category' => 'query',
                'level' => $query->time > 1000 ? 'warning' : 'info',
                'data' => [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'connection' => $query->connectionName,
                ],
            ]);
        });
    }
}
