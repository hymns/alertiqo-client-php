<?php

// Example 1: Basic Exception Capture
use Alertiqo\Laravel\Facades\Alertiqo;

try {
    // Some risky operation
    $result = riskyOperation();
} catch (\Exception $e) {
    Alertiqo::captureException($e);
    // Handle error gracefully
}

// Example 2: Capture with Additional Context
try {
    processPayment($orderId);
} catch (\Exception $e) {
    Alertiqo::captureException($e, [
        'tags' => [
            'order_id' => $orderId,
            'payment_method' => 'credit_card'
        ]
    ]);
}

// Example 3: Manual Message Logging
Alertiqo::captureMessage('Important business event occurred', 'info', [
    'tags' => ['feature' => 'checkout']
]);

// Example 4: Using Breadcrumbs
Alertiqo::addBreadcrumb([
    'message' => 'User started checkout process',
    'category' => 'navigation',
    'level' => 'info'
]);

Alertiqo::addBreadcrumb([
    'message' => 'Added item to cart',
    'category' => 'user-action',
    'level' => 'info',
    'data' => ['product_id' => 123]
]);

// Then if an error occurs, all breadcrumbs will be sent
try {
    completeCheckout();
} catch (\Exception $e) {
    Alertiqo::captureException($e);
}

// Example 5: Set User Context (in LoginController)
public function login(Request $request)
{
    $user = Auth::user();
    
    Alertiqo::setUser([
        'id' => $user->id,
        'email' => $user->email,
        'name' => $user->name,
    ]);
    
    Alertiqo::setTags([
        'role' => $user->role,
        'subscription' => $user->subscription_type
    ]);
}

// Example 6: Helper Functions
alertiqo_capture($exception);
alertiqo_message('User uploaded file', 'info');
alertiqo_breadcrumb('Database query executed', 'database', 'debug');

// Example 7: In Artisan Command
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Alertiqo\Laravel\Facades\Alertiqo;

class ImportData extends Command
{
    protected $signature = 'data:import';

    public function handle()
    {
        Alertiqo::setTag('command', 'data:import');
        
        try {
            $this->info('Starting import...');
            // Import logic
        } catch (\Exception $e) {
            Alertiqo::captureException($e);
            $this->error('Import failed!');
        }
    }
}

// Example 8: In API Response
public function apiEndpoint()
{
    try {
        $data = fetchData();
        return response()->json($data);
    } catch (\Exception $e) {
        Alertiqo::captureException($e, [
            'tags' => ['endpoint' => 'api/data']
        ]);
        
        return response()->json([
            'error' => 'Internal server error'
        ], 500);
    }
}
