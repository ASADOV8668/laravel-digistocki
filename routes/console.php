<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('listings:expire')->dailyAt('00:15')->withoutOverlapping();
Schedule::command('contact-otps:prune')->dailyAt('00:30')->withoutOverlapping();
Schedule::command('mobile-otps:prune')->dailyAt('00:35')->withoutOverlapping();
Schedule::command('chats:prune')->dailyAt('00:45')->withoutOverlapping();

Artisan::command('admin:grant-senior {user : Existing user ID}', function () {
    $user = \App\Models\User::query()->find($this->argument('user'));

    if (! $user || ! $user->is_active || ! $user->mobile) {
        $this->error('Choose an active existing account with a mobile number.');

        return 1;
    }

    if ($user->isSeniorAdmin()) {
        $this->info('This account is already a senior administrator.');

        return 0;
    }

    if (! $this->confirm("Grant senior administrator permissions to account #{$user->id} ({$user->name})?")) {
        return 1;
    }

    $user->forceFill(['role' => 'admin', 'is_senior_admin' => true])->save();
    $this->info('Senior administrator permissions granted.');

    return 0;
})->purpose('Grant senior administrator permissions to an existing account');
