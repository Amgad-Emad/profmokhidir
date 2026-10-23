<?php

namespace Tests\Unit\Providers;

use Illuminate\Foundation\DevCommands;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `composer run dev` (php artisan dev) runs the queue worker, so queued image
 * thumbnails and mail are processed locally; Valet serves the site.
 */
class DevCommandsTest extends TestCase
{
    #[Test]
    public function composer_run_dev_starts_the_queue_worker_and_vite_but_no_server(): void
    {
        $commands = array_column(DevCommands::commands(), 'command', 'name');

        $this->assertSame('php artisan queue:listen --tries=1 --timeout=0', $commands['queue'] ?? null);
        $this->assertArrayHasKey('vite', $commands);
        $this->assertArrayNotHasKey('server', $commands);
        $this->assertContains('@php artisan dev', json_decode((string) file_get_contents(base_path('composer.json')), true)['scripts']['dev']);
    }
}
