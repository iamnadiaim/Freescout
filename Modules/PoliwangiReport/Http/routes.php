<?php

Route::group([
    'middleware' => ['web', 'auth'],
    'namespace'  => 'Modules\PoliwangiReport\Http\Controllers',
    'prefix'     => \Helper::getSubdirectory() . 'lapor-poliwangi',
], function () {

    /*
    |--------------------------------------------------------------------------
    | Time Tracking Report
    |--------------------------------------------------------------------------
    | URL:
    | /lapor-poliwangi/time-tracking-report
    |--------------------------------------------------------------------------
    */
    Route::get('/time-tracking-report', [
        'uses' => 'ReportController@timeTracking',
        'as'   => 'PoliwangiPortal.reports.time_tracking',
    ]);
});
