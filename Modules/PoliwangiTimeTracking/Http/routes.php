<?php

Route::group([
    'middleware' => ['web', 'auth'],
    'namespace'  => 'Modules\PoliwangiTimeTracking\Http\Controllers',
    'prefix'     => \Helper::getSubdirectory() . 'lapor-poliwangi',
], function () {

    /*
    |--------------------------------------------------------------------------
    | Time Tracking Status
    |--------------------------------------------------------------------------
    */
    Route::post('/time-tracking/status', [
        'uses' => 'TimeTrackingController@status',
        'as'   => 'PoliwangiPortal.time_tracking.status',
    ]);

});
