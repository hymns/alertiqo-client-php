<?php

declare(strict_types=1);

namespace Alertiqo\Laravel\Console;

use Alertiqo\Laravel\Facades\Alertiqo;
use Exception;
use Illuminate\Console\Command;

/**
 * Test Command
 *
 * Artisan command for testing Alertiqo error tracking integration.
 */
class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alertiqo:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Alertiqo error tracking by sending a test exception';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Testing Alertiqo error tracking...');
        $this->newLine();

        try {
            // Send test breadcrumb
            Alertiqo::addBreadcrumb([
                'message' => 'Alertiqo test command executed',
                'category' => 'console',
                'level' => 'info',
            ]);

            // Throw test exception
            throw new \Exception('This is a test exception from Alertiqo! If you see this in your dashboard, everything is working correctly. 🎉');
        } catch (\Exception $e) {
            // Capture the exception
            Alertiqo::captureException($e, [
                'tags' => [
                    'test' => true,
                    'command' => 'alertiqo:test',
                ],
            ]);

            $this->components->task('Sending test error to Alertiqo', function () {
                // Simulate sending
                usleep(500000); // 0.5 second delay
                return true;
            });

            $this->newLine();
            $this->components->info('Test error sent successfully!');
            $this->components->info('Check your Alertiqo dashboard at: ' . config('alertiqo.endpoint', 'https://alertiqo.io'));
            $this->newLine();
            
            $this->components->twoColumnDetail('Error Message', $e->getMessage());
            $this->components->twoColumnDetail('Environment', config('alertiqo.environment', config('app.env')));
            $this->components->twoColumnDetail('Release', config('alertiqo.release', 'Not set'));
            
            return Command::SUCCESS;
        }
    }
}
