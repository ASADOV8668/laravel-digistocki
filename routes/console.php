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
