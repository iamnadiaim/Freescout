<?php

Route::group([
    'middleware' => ['web', 'auth'],
    'namespace'  => 'Modules\PoliwangiSatisfaction\Http\Controllers',
    'prefix'     => \Helper::getSubdirectory() . 'lapor-poliwangi',
], function () {

    /*
    |--------------------------------------------------------------------------
    | Satisfaction Ratings Settings & Report
    |--------------------------------------------------------------------------
    */
    Route::get('/mailboxes/{mailbox_id}/satisfaction-ratings', [
        'uses' => 'SatisfactionRatingController@index',
        'as'   => 'PoliwangiPortal.satisfaction_ratings.index',
    ]);

    Route::post('/mailboxes/{mailbox_id}/satisfaction-ratings/settings', [
        'uses' => 'SatisfactionRatingController@updateSettings',
        'as'   => 'PoliwangiPortal.satisfaction_ratings.update_settings',
    ]);

    Route::post('/mailboxes/{mailbox_id}/satisfaction-ratings/translate', [
        'uses' => 'SatisfactionRatingController@updateTranslate',
        'as'   => 'PoliwangiPortal.satisfaction_ratings.update_translate',
    ]);

    Route::post('/mailboxes/{mailbox_id}/satisfaction-ratings/settings/reset', [
        'uses' => 'SatisfactionRatingController@resetSettingsDefaults',
        'as'   => 'PoliwangiPortal.satisfaction_ratings.reset_settings',
    ]);

    Route::post('/mailboxes/{mailbox_id}/satisfaction-ratings/translate/reset', [
        'uses' => 'SatisfactionRatingController@resetTranslateDefaults',
        'as'   => 'PoliwangiPortal.satisfaction_ratings.reset_translate',
    ]);

    Route::get('/mailboxes/{mailbox_id}/satisfaction-ratings/report', [
        'uses' => 'SatisfactionRatingController@report',
        'as'   => 'PoliwangiPortal.satisfaction_ratings.report',
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Satisfaction Rating From Email
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => ['web', 'throttle:30,1'],
    'prefix'     => \Helper::getSubdirectory(),
    'namespace'  => 'Modules\PoliwangiSatisfaction\Http\Controllers',
], function () {

    Route::get('/mailbox/{mailbox_id}/satisfaction-ratings/rate/{conversation_id}/{thread_id}/{rating}', [
        'uses' => 'SatisfactionRatingController@rateFromEmail',
        'as'   => 'mailboxes.satisfaction_ratings.rate_from_email',
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Satisfaction Rating From Portal
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => app()->runningUnitTests() ? ['web'] : ['web', 'throttle:15,1'],
    'prefix'     => \Helper::getSubdirectory() . 'lapor-poliwangi',
    'namespace'  => 'Modules\PoliwangiSatisfaction\Http\Controllers',
], function () {

    Route::post('/help/{mailbox_id}/ticket/{conversation_id}/satisfaction-rating', [
        'uses' => 'SatisfactionRatingController@submitRating',
        'as'   => 'PoliwangiPortal.end_user_portal.submit_satisfaction_rating',
    ]);
});
