<?php

use App\Models\User;
use App\Services\BaleAdminMenu;
use App\Services\BaleAdminReport;
use App\Services\BaleBot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('listings:expire')->dailyAt('00:15')->withoutOverlapping();
Schedule::command('contact-otps:prune')->dailyAt('00:30')->withoutOverlapping();
Schedule::command('mobile-otps:prune')->dailyAt('00:35')->withoutOverlapping();
Schedule::command('chats:prune')->dailyAt('00:45')->withoutOverlapping();

Artisan::command('bale:register-webhook', function (BaleBot $bot) {
    $appUrl = config('services.bale.webhook_origin') ?: config('app.url');
    if (! $bot->enabled() || parse_url($appUrl, PHP_URL_SCHEME) !== 'https') {
        $this->error('Set BALE_BOT_TOKEN, a 64-character hexadecimal BALE_WEBHOOK_SECRET, and a public HTTPS app or webhook URL.');

        return 1;
    }

    $path = route('bale.webhook', ['secret' => config('services.bale.webhook_secret')], absolute: false);
    $url = rtrim($appUrl, '/').$path;
    if (! $bot->call('setWebhook', ['url' => $url])) {
        $this->error('Bale did not accept the webhook registration.');

        return 1;
    }

    $this->info('Bale webhook registered.');

    return 0;
})->purpose('Register the production webhook with Bale');

Artisan::command('bale:daily-report', function (BaleBot $bot, BaleAdminReport $report, BaleAdminMenu $menu) {
    if (! $bot->enabled()) {
        return 0;
    }

    DB::table('bale_admin_connections')->whereNull('user_id')->where('code_expires_at', '<=', now())->delete();

    $text = null;
    $failed = 0;
    foreach (DB::table('bale_admin_connections')->whereNotNull('user_id')->cursor() as $connection) {
        $user = User::find($connection->user_id);
        if ($user?->isAdmin() && $user->is_active && ! $bot->send($connection->chat_id, $text ??= $report->text(), $menu->reportButtons())) {
            $failed++;
        }
    }

    if ($failed > 0) {
        $this->error("Bale daily report could not be sent to {$failed} account(s).");
    }

    return $failed > 0 ? 1 : 0;
})->purpose('Send a daily report to connected active administrators');

Schedule::command('bale:daily-report')->dailyAt('20:00')->timezone('Asia/Tehran')->withoutOverlapping();
