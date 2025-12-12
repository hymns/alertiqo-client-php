<?php

declare(strict_types=1);

namespace Alertiqo\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Alertiqo Facade
 *
 * Provides static access to the Alertiqo client instance.
 *
 * @method static void captureException(\Throwable $exception, array $additionalData = [])
 * @method static void captureMessage(string $message, string $level = 'info', array $additionalData = [])
 * @method static void addBreadcrumb(array $breadcrumb)
 * @method static void setUser(array $user)
 * @method static void setTag(string $key, string $value)
 * @method static void setTags(array $tags)
 *
 * @see \Alertiqo\Laravel\Alertiqo
 */
class Alertiqo extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'alertiqo';
    }
}
