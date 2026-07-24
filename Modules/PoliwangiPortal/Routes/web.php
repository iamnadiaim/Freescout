<?php


/*
|--------------------------------------------------------------------------
| Admin Poliwangi Portal Routes
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => ['web', 'auth'],
    'namespace'  => 'Modules\PoliwangiPortal\Http\Controllers',
    'prefix'     => 'lapor-poliwangi',
], function () {



    /*
    |--------------------------------------------------------------------------
    | End User Portal Settings
    |--------------------------------------------------------------------------
    */
    Route::get('/mailboxes/{mailbox_id}/end-user-portal', [
        'uses' => 'EndUserPortalSettingController@index',
        'as'   => 'PoliwangiPortal.end_user_portal.setting',
    ]);

    Route::post('/mailboxes/{mailbox_id}/end-user-portal', [
        'uses' => 'EndUserPortalSettingController@update',
        'as'   => 'PoliwangiPortal.end_user_portal.update',
    ]);

    Route::post('/mailboxes/{mailbox_id}/end-user-portal/widget-auto-save', [
        'uses' => 'EndUserPortalSettingController@widgetAutoSave',
        'as'   => 'PoliwangiPortal.end_user_portal.widget_auto_save',
    ]);








});








/*
|--------------------------------------------------------------------------
| Public End User Portal Routes
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => ['web', 'throttle:60,1'], // 60 requests per minute for general browsing
    'namespace'  => 'Modules\PoliwangiPortal\Http\Controllers',
], function () {

    Route::get('/help/auth', [
        'uses' => 'EndUserPortalController@selectAuth',
        'as'   => 'PoliwangiPortal.end_user_portal.auth_select',
    ]);

    Route::get('/help/register', [
        'uses' => 'EndUserPortalController@registerEndUser',
        'as'   => 'PoliwangiPortal.end_user_portal.register',
    ]);

    Route::get('/help/login', [
        'uses' => 'EndUserPortalController@loginEndUser',
        'as'   => 'PoliwangiPortal.end_user_portal.login_end_user',
    ]);

    Route::get('/help/verify/{token}', [
        'uses' => 'EndUserPortalController@verifyEmail',
        'as'   => 'PoliwangiPortal.end_user_portal.verify',
    ]);

    Route::get('/help/logout', [
        'uses' => 'EndUserPortalController@logoutEndUser',
        'as'   => 'PoliwangiPortal.end_user_portal.logout',
    ]);

    Route::get('/help', [
        'uses' => 'EndUserPortalController@selectMailbox',
        'as'   => 'PoliwangiPortal.end_user_portal.select_mailbox',
    ]);

    Route::get('/help/my/tickets', [
        'uses' => 'EndUserPortalController@myTickets',
        'as'   => 'PoliwangiPortal.end_user_portal.my_ticket',
    ]);

    Route::post('/help/track', [
        'uses' => 'EndUserPortalController@trackTicketSubmit',
        'as'   => 'PoliwangiPortal.end_user_portal.track.submit',
    ]);

    Route::get('/help/track/{number}', [
        'uses' => 'EndUserPortalController@trackTicketDetail',
        'as'   => 'PoliwangiPortal.end_user_portal.track_detail',
    ]);

    Route::get('/help/track/verify/{token}', [
        'uses' => 'EndUserPortalController@verifyTrackingToken',
        'as'   => 'PoliwangiPortal.end_user_portal.track.verify',
    ]);

    Route::get('/help/{mailbox_id}', [
        'uses' => 'EndUserPortalController@showPortal',
        'as'   => 'PoliwangiPortal.end_user_portal.submit_ticket',
    ]);

    Route::get('/help/{mailbox_id}/ticket/{conversation_id}', [
        'uses' => 'EndUserPortalController@ticketDetail',
        'as'   => 'PoliwangiPortal.end_user_portal.ticket_detail',
    ]);
});

/*
|--------------------------------------------------------------------------
| Public End User Portal Routes (Form Submissions)
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => app()->runningUnitTests() ? ['web'] : ['web', 'throttle:15,1'], // 15 requests per minute for form submissions (stricter limit)
    'namespace'  => 'Modules\PoliwangiPortal\Http\Controllers',
], function () {

    Route::post('/help/register', [
        'uses' => 'EndUserPortalController@registerEndUserSubmit',
        'as'   => 'PoliwangiPortal.end_user_portal.register.submit',
    ]);

    Route::post('/help/login', [
        'uses' => 'EndUserPortalController@loginEndUserSubmit',
        'as'   => 'PoliwangiPortal.end_user_portal.login.submit',
    ]);

    Route::post('/help/{mailbox_id}', [
        'uses' => 'EndUserPortalController@submitTicket',
        'as'   => 'PoliwangiPortal.end_user_portal.submit',
    ]);

    Route::post('/help/{mailbox_id}/ticket/{conversation_id}/reply', [
        'uses' => 'EndUserPortalController@replyTicket',
        'as'   => 'PoliwangiPortal.end_user_portal.ticket_reply',
    ]);

    Route::post('/help/track/{number}/reply', [
        'uses' => 'EndUserPortalController@trackTicketReply',
        'as'   => 'PoliwangiPortal.end_user_portal.track_reply',
    ]);


});
