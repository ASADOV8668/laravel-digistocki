<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckApplicationHealth extends Command
{
    protected $signature = 'app:health {--json : Print machine-readable JSON output}';

    protected $description = 'Check the application dependencies required for local and FTP deployments';

    public function handle(): int
    {
        $checks = [
            'database' => $this->databaseIsReachable(),
            'storage' => $this->directoriesAreWritable(),
            'storage_link' => is_dir(public_path('storage')),
            'build_manifest' => is_file(public_path('build/manifest.json')),
            'apache_front_controller' => $this->apacheRewriteIsConfigured(),
        ];
        $healthy = ! in_array(false, $checks, true);

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'ok' => $healthy,
                'checks' => $checks,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            foreach ($checks as $name => $passed) {
                $this->line(($passed ? 'OK  ' : 'FAIL').' '.$name);
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
}
