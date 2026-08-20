<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('limesurvey:importar-dados')
    ->hourly()
    ->runInBackground()
    ->withoutOverlapping()
    ->onFailure(fn () => Log::error('Falha na importação diária do LimeSurvey'));
