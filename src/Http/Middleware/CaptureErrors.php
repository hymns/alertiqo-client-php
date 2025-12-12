<?php

declare(strict_types=1);

namespace Alertiqo\Laravel\Http\Middleware;

use Alertiqo\Laravel\Facades\Alertiqo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Capture Errors Middleware
 *
 * Adds request breadcrumbs for error tracking context.
 */
class CaptureErrors
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        Alertiqo::addBreadcrumb([
            'message' => sprintf('%s %s', $request->method(), $request->path()),
            'category' => 'request',
            'level' => 'info',
            'data' => [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]
        ]);

        return $next($request);
    }
}
