<?php

if (!function_exists('alertiqo')) {
    /**
     * Get the Alertiqo instance.
     *
     * @return \Alertiqo\Laravel\Alertiqo
     */
    function alertiqo()
    {
        return app('alertiqo');
    }
}

if (!function_exists('alertiqo_capture')) {
    /**
     * Capture an exception.
     *
     * @param \Throwable $exception
     * @param array $additionalData
     * @return void
     */
    function alertiqo_capture(\Throwable $exception, array $additionalData = [])
    {
        app('alertiqo')->captureException($exception, $additionalData);
    }
}

if (!function_exists('alertiqo_message')) {
    /**
     * Capture a message.
     *
     * @param string $message
     * @param string $level
     * @param array $additionalData
     * @return void
     */
    function alertiqo_message(string $message, string $level = 'info', array $additionalData = [])
    {
        app('alertiqo')->captureMessage($message, $level, $additionalData);
    }
}

if (!function_exists('alertiqo_breadcrumb')) {
    /**
     * Add a breadcrumb.
     *
     * @param string $message
     * @param string $category
     * @param string $level
     * @param array $data
     * @return void
     */
    function alertiqo_breadcrumb(string $message, string $category = 'default', string $level = 'info', array $data = [])
    {
        app('alertiqo')->addBreadcrumb([
            'message' => $message,
            'category' => $category,
            'level' => $level,
            'data' => $data,
        ]);
    }
}
