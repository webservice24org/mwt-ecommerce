<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Shipping Provider Recovery
|--------------------------------------------------------------------------
|
| Recover stale provider submission, tracking-sync, and reconciliation
| operations without allowing duplicate courier submissions.
|
*/
Schedule::command(
    'shipping:recover-stale-provider-operations --minutes=15',
)
    ->everyFiveMinutes()
    ->withoutOverlapping(15);
