<?php

namespace App\Console\Commands;

use App\Services\SystemOptions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckApplicationHealth extends Command
{
    protected $signature = 'app:health {--json : Print machine-readable JSON output} {--skip-deployment-assets : Skip generated build and storage-link checks}';

    protected $description = 'Check the application dependencies required for local and FTP deployments';

    public function handle(): int
    {
        $deploymentAssetsSkipped = $this->deploymentAssetsAreSkipped();
        $checks = [
            'database' => $this->databaseIsReachable(),
            'storage' => $this->directoriesAreWritable(),
            'storage_link' => $deploymentAssetsSkipped || is_dir(public_path('storage')),
            'build_manifest' => $deploymentAssetsSkipped || is_file(public_path('build/manifest.json')),
            'apache_front_controller' => $this->apacheRewriteIsConfigured(),
        ];
        if (app(SystemOptions::class)->smsMode() === 'live') {
            $checks['sms_provider'] = is_string(config('services.sms.url')) && trim((string) config('services.sms.url')) !== '';
        }
        $healthy = ! in_array(false, $checks, true);
        $skipped = $deploymentAssetsSkipped ? ['storage_link', 'build_manifest'] : [];

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'ok' => $healthy,
                'mode' => $deploymentAssetsSkipped ? 'portable' : 'strict',
                'checks' => $checks,
                'skipped' => $skipped,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            foreach ($checks as $name => $passed) {
                $status = in_array($name, $skipped, true) ? 'SKIP' : ($passed ? 'OK  ' : 'FAIL');
                $this->line($status.' '.$name);
            }
            $this->line($healthy ? 'Application health: OK' : 'Application health: FAIL');
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    private function databaseIsReachable(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function directoriesAreWritable(): bool
    {
        foreach ([
            storage_path('app/public'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ] as $directory) {
            if (! is_dir($directory) || ! is_writable($directory)) {
                return false;
            }
        }

        return true;
    }

    private function apacheRewriteIsConfigured(): bool
    {
        $path = public_path('.htaccess');
        $contents = is_file($path) ? file_get_contents($path) : false;

        return is_string($contents) && str_contains($contents, 'RewriteRule ^ index.php [L]');
    }

    private function deploymentAssetsAreSkipped(): bool
    {
        return (bool) $this->option('skip-deployment-assets');
    }
}
