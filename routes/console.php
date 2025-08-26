<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup expired refresh tokens daily at 2 AM
Schedule::command('tokens:cleanup-expired')
    ->daily()
    ->at('02:00')
    ->description('Clean up expired refresh tokens');
