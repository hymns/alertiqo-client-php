# Alertiqo Client PHP - Laravel Error Tracking

Error tracking for Laravel - as simple as possible! 🚀

## Installation

Install via Composer:

```bash
composer require hymns/alertiqo-client-php
```

## Publishable Assets

```bash
# Publish config file
php artisan vendor:publish --tag=alertiqo-client-config
```

| Tag | Description | Path |
|-----|-------------|------|
| `alertiqo-client-config` | Config file | `config/alertiqo.php` |

This will create `config/alertiqo.php` for customization.

## Quick Setup (Laravel 11+)

**1. Install package:**

```bash
composer require hymns/alertiqo-client-php
```

**2. Add to `bootstrap/app.php`:**
```php
use Alertiqo\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })
    ->create();
```

**3. Add `.env` config:**
```env
ALERTIQO_API_KEY=your-api-key-here
ALERTIQO_ENDPOINT=https://alertiqo.hamizi.net
```

**That's it!** All exceptions will be automatically tracked.

**4. Test your setup:**
```bash
php artisan alertiqo:test
```

## Laravel 9/10 Setup

For Laravel 9/10, package will auto-register via composer. Just set `.env` variables and you're done!

```env
ALERTIQO_ENABLED=true
ALERTIQO_API_KEY=your-api-key-here
ALERTIQO_ENDPOINT=https://alertiqo.hamizi.net
```

**Optional - Publish config file:**

```bash
php artisan vendor:publish --tag=alertiqo-client-config
```

### Queue Configuration (Recommended)

**By default, Alertiqo uses queues** to send error reports asynchronously. This will ensures your app stay fast even when reporting errors.

**Prerequisites:**

- Laravel queue must be configured (Redis, database, etc.)
- Queue worker must be running: `php artisan queue:work`

**Features:**

- ✅ Non-blocking error reporting
- ✅ Automatic retry (3 attempts with exponential backoff)
- ✅ Fallback to sync if queue fails
- ✅ Better performance for production

**Disable queue (not recommended):**

```env
ALERTIQO_USE_QUEUE=false
```

This will send reports synchronously, which may slow down responses if the API is down or slow.

### SQL Query Logging

**Automatically capture database queries as breadcrumbs** for easier debugging!

**Enable SQL logging:**

```env
ALERTIQO_LOG_SQL=true
ALERTIQO_SQL_THRESHOLD=100  # Only log queries > 100ms (optional)
```

**Features:**

- ✅ Captures SQL, bindings, execution time, and connection name
- ✅ Threshold filtering (e.g., only slow queries)
- ✅ Automatic level detection (warning for queries > 1s)
- ✅ Included as breadcrumbs in error reports

**Example breadcrumb output:**

```json
{
  "message": "Database Query",
  "category": "query",
  "level": "info",
  "data": {
    "sql": "SELECT * FROM users WHERE id = ?",
    "bindings": [123],
    "time": "45.2ms",
    "connection": "mysql"
  }
}
```

**Best practices:**

- Use threshold in production to avoid noise
- Set `ALERTIQO_SQL_THRESHOLD=200` to only log slow queries
- Disable in high-traffic apps if too many queries

### Performance Monitoring

**Track slow requests automatically** to catch performance issues before they become errors!

**Enable performance monitoring:**

```env
ALERTIQO_PERFORMANCE_MONITORING=true
ALERTIQO_PERFORMANCE_THRESHOLD=1000  # Report requests > 1 second
```

**What gets captured:**

- Request duration (ms)
- Memory usage (MB)
- Peak memory (MB)
- Database query count
- HTTP status code
- Full URL and method

**Features:**

- ✅ Automatic slow request detection
- ✅ Non-intrusive (only reports if threshold exceeded)
- ✅ Includes all breadcrumbs for full context
- ✅ Memory profiling included

### Error Sampling

**Control error capture rate** for high-traffic applications:

```env
ALERTIQO_SAMPLE_RATE=0.1  # Capture 10% of errors
```

**Use cases:**

- **1.0** (100%) - Development/staging
- **0.5** (50%) - Medium traffic production
- **0.1** (10%) - High traffic production
- **0.01** (1%) - Very high traffic

**Smart sampling:**

- Critical errors always captured
- Random sampling for other errors
- Reduces dashboard noise
- Saves bandwidth and storage

## Testing Your Setup

**Artisan Command:**
```bash
php artisan alertiqo:test
```

This will send a test error to your dashboard. Perfect to verify setup!

**Debug Route (Development Only):**

Add this to `routes/web.php` for quick testing:

```php
// Only in development!
if (app()->environment('local')) {
    Route::get('/debug-alertiqo', function () {
        throw new Exception('My first Alertiqo error!');
    });
}
```

Visit `/debug-alertiqo` using web browser, then check your dashboard.

## Notifications

**Notifications are configured via the backend dashboard**, not in the package config. This allows centralized management across all your projects.

**Available notification channels:**
- **Slack** - Instant alerts to Slack channels
- **Email** - Send to multiple recipients
- **Webhook** - Custom HTTP webhooks

**Configure via dashboard:**
1. Go to Project Settings → Notifications
2. Add notification channel (Slack/Email/Webhook)
3. Set minimum error level (debug, info, warning, error, critical)
4. Enable/disable as needed

**Benefits:**
- ✅ Centralized config (no need to update every app)
- ✅ Smart throttling (avoid spam)
- ✅ Per-project customization
- ✅ Easy to add/remove channels
- ✅ Notification history & audit log

## Usage

### Automatic Error Tracking

This package will automatically track all exceptions in your Laravel app. No additional setup needed!

### Manual Error Capture

#### Using Facade

```php
use Alertiqo\Laravel\Facades\Alertiqo;

// Capture exception
try {
    throw new \Exception('Something went wrong');
} catch (\Exception $e) {
    Alertiqo::captureException($e);
}

// Capture message
Alertiqo::captureMessage('User logged in', 'info');

// Add breadcrumb
Alertiqo::addBreadcrumb([
    'message' => 'User clicked button',
    'category' => 'user-action',
    'level' => 'info',
    'data' => ['button_id' => 'submit']
]);

// Set user context
Alertiqo::setUser([
    'id' => auth()->id(),
    'email' => auth()->user()->email,
    'name' => auth()->user()->name,
]);

// Set tags
Alertiqo::setTag('feature', 'checkout');
Alertiqo::setTags([
    'version' => '2.0.0',
    'subscription' => 'premium'
]);
```

#### Using Helper Functions

```php
// Capture exception
alertiqo_capture($exception);

// Capture message
alertiqo_message('Payment processed', 'info');

// Add breadcrumb
alertiqo_breadcrumb('User navigated to checkout', 'navigation', 'info');

// Get instance
$client = alertiqo();
$client->setUser(['id' => 123]);
```

### Integration Examples

#### In Controllers

```php
namespace App\Http\Controllers;

use Alertiqo\Laravel\Facades\Alertiqo;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        Alertiqo::addBreadcrumb([
            'message' => 'Order creation started',
            'category' => 'order',
            'level' => 'info'
        ]);

        try {
            $order = Order::create($request->all());
            
            Alertiqo::captureMessage('Order created successfully', 'info', [
                'tags' => ['order_id' => $order->id]
            ]);
            
            return response()->json($order);
        } catch (\Exception $e) {
            Alertiqo::captureException($e, [
                'tags' => ['user_id' => auth()->id()]
            ]);
            
            return response()->json(['error' => 'Failed to create order'], 500);
        }
    }
}
```

#### In Jobs

```php
namespace App\Jobs;

use Alertiqo\Laravel\Facades\Alertiqo;

class ProcessPayment implements ShouldQueue
{
    public function handle()
    {
        Alertiqo::addBreadcrumb([
            'message' => 'Payment processing started',
            'category' => 'job',
            'level' => 'info'
        ]);

        try {
            // Process payment logic
        } catch (\Exception $e) {
            Alertiqo::captureException($e);
            throw $e;
        }
    }
}
```

#### In Event Listeners

```php
namespace App\Listeners;

use Alertiqo\Laravel\Facades\Alertiqo;

class SendWelcomeEmail
{
    public function handle($event)
    {
        try {
            // Send email logic
        } catch (\Exception $e) {
            Alertiqo::captureException($e, [
                'tags' => ['event' => 'user_registered']
            ]);
        }
    }
}
```

#### In Middleware

```php
namespace App\Http\Middleware;

use Closure;
use Alertiqo\Laravel\Facades\Alertiqo;

class TrackApiCalls
{
    public function handle($request, Closure $next)
    {
        Alertiqo::addBreadcrumb([
            'message' => 'API call: ' . $request->path(),
            'category' => 'api',
            'level' => 'info',
            'data' => [
                'method' => $request->method(),
                'path' => $request->path()
            ]
        ]);

        return $next($request);
    }
}
```

## Configuration Options

### Environment Variables

```env
# Enable/disable tracking
ALERTIQO_ENABLED=true

# API credentials
ALERTIQO_API_KEY=your-api-key

# Backend endpoint
ALERTIQO_ENDPOINT=http://localhost:3000

# Environment name
APP_ENV=production

# Release version
ALERTIQO_RELEASE=v1.0.0

# Debug mode
ALERTIQO_DEBUG=false

# Queue settings
ALERTIQO_USE_QUEUE=true
ALERTIQO_QUEUE=default

# SQL query logging
ALERTIQO_LOG_SQL=false
ALERTIQO_SQL_THRESHOLD=0

# Performance monitoring
ALERTIQO_PERFORMANCE_MONITORING=false
ALERTIQO_PERFORMANCE_THRESHOLD=1000

# Error sampling
ALERTIQO_SAMPLE_RATE=1.0
```

### Config File (`config/alertiqo.php`)

Customize behaviour in the config file:

- `enabled` - Enable/disable tracking
- `api_key` - Your API key
- `endpoint` - Backend URL
- `environment` - Environment name
- `release` - Release version
- `tags` - Default tags untuk semua errors
- `timeout` - HTTP timeout
- `use_queue` - Enable queue-based async reporting (default: true)
- `queue` - Queue name to use (default: 'default')
- `log_sql` - Capture SQL queries as breadcrumbs (default: false)
- `sql_threshold` - Only log queries above this time in ms (default: 0)
- `performance_monitoring` - Track slow requests (default: false)
- `performance_threshold` - Report requests slower than this in ms (default: 1000)
- `sample_rate` - Error sampling rate 0.0-1.0 (default: 1.0)
- `send_request_data` - Include request data in reports
- `sensitive_keys` - Keys to filter from request data
- `dont_report` - Exception classes to be excluded from reporting

## Features

✅ Automatic exception tracking  
✅ Manual error capture  
✅ Breadcrumbs tracking  
✅ User context  
✅ Custom tags  
✅ Request data capture  
✅ **SQL query logging** (with threshold filtering)  
✅ **Performance monitoring** (track slow requests & memory)  
✅ **Error sampling** (for high-traffic apps)  
✅ Sensitive data filtering  
✅ Configurable exception filtering  
✅ **Queue-based async reporting** (non-blocking, with auto-retry)  
✅ Job/Queue support  
✅ Event listener integration  

## Requirements

- PHP 8.0 or higher
- Laravel 9.x, 10.x, or 11.x
- GuzzleHTTP 7.x

## Testing

This package will be automatically disabled in testing environment. Set `ALERTIQO_ENABLED=false` in `.env.testing`.

## License

MIT
