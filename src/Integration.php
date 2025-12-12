<?php

declare(strict_types=1);

namespace Alertiqo\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Throwable;

/**
 * Integration Class
 *
 * Provides integration helpers for Laravel 11+ applications.
 */
class Integration
{
    /**
     * Register exception handling for Alertiqo.
     *
     * @param  \Illuminate\Foundation\Configuration\Exceptions  $exceptions
     * @return void
     */
    public static function handles(Exceptions $exceptions): void
    {
        $exceptions->report(function (Throwable $e) {
            if (! config('alertiqo.enabled', true)) {
                return;
            }

            // Don't report if we're in maintenance mode
            if (app()->isDownForMaintenance()) {
                return;
            }

            // Get ignored exceptions
            $ignored = config('alertiqo.ignore_exceptions', []);
            
            foreach ($ignored as $ignoredException) {
                if ($e instanceof $ignoredException) {
                    return;
                }
            }

            // Report to Alertiqo
            try {
                app('alertiqo')->captureException($e);
            } catch (Throwable $reportingError) {
                // Silently fail if Alertiqo itself errors
                if (config('app.debug')) {
                    logger()->error('Alertiqo failed to report exception', [
                        'error' => $reportingError->getMessage(),
                    ]);
                }
            }
        });
    }

    /**
     * Bootstrap Alertiqo for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public static function bootstrap(Application $app): void
    {
        if (! config('alertiqo.enabled', true)) {
            return;
        }

        // Register Alertiqo instance
        $app->singleton(Alertiqo::class, function ($app) {
            return new Alertiqo(
                config('alertiqo.api_key'),
                config('alertiqo.endpoint'),
                config('alertiqo.environment', config('app.env')),
                config('alertiqo.release')
            );
        });

        // Set global context if configured (skip during console)
        if (!$app->runningInConsole()) {
            try {
                if ($user = auth()->user()) {
                    app('alertiqo')->setUser([
                        'id' => $user->id,
                        'email' => $user->email ?? null,
                        'name' => $user->name ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                // Skip if auth not available
            }
        }
    }
}
