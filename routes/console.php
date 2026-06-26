<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})
    ->purpose('Display an inspiring quote');

Artisan::command('maintenance:daily-reset', function () {

    $service = app(\App\Services\MaintenanceService::class);

    $removedTasks = $service->resetDailyTasksAtMidnight();
    $failedChallenges = $service->failSkippedChallenges();
    $resetFailed = $service->resetFailedChallengesAtMidnight();

    $this->info("Removed daily tasks: {$removedTasks}");
    $this->info("Failed challenges: {$failedChallenges}");
    $this->info("Reset failed challenges: {$resetFailed}");
})
    ->purpose('Run daily maintenance for tasks and challenges (timezone-aware, runs every minute)');

Artisan::command('maintenance:monthly-reset', function () {
    $service = app(\App\Services\MaintenanceService::class);
    $reset = $service->resetMonthlyChallengeResults();
    $this->info("Monthly challenge reset rows: {$reset}");
})
    ->purpose('Run monthly challenge reset');

Schedule::command('maintenance:daily-reset')->everyMinute();
Schedule::command('maintenance:monthly-reset')->monthlyOn(1, '00:10');
Schedule::command('reminders:send')->everyMinute();
