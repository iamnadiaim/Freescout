<?php


/*
|--------------------------------------------------------------------------
| Admin Lapor Poliwangi Routes
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => ['web', 'auth'],
    'namespace'  => 'Modules\LaporPoliwangi\Http\Controllers',
    'prefix'     => 'lapor-poliwangi',
], function () {

    /*
    |--------------------------------------------------------------------------
    | Custom Fields
    |--------------------------------------------------------------------------
    */
    Route::get('/mailboxes/{mailbox_id}/custom-fields', [
        'uses' => 'CustomFieldController@index',
        'as'   => 'laporpoliwangi.custom_fields',
    ]);

    Route::post('/mailboxes/{mailbox_id}/custom-fields', [
        'uses' => 'CustomFieldController@store',
        'as'   => 'laporpoliwangi.custom_fields.store',
    ]);

    Route::put('/mailboxes/{mailbox_id}/custom-fields/{field_id}', [
        'uses' => 'CustomFieldController@update',
        'as'   => 'laporpoliwangi.custom_fields.update',
    ]);

    Route::delete('/mailboxes/{mailbox_id}/custom-fields/{field_id}', [
        'uses' => 'CustomFieldController@destroy',
        'as'   => 'laporpoliwangi.custom_fields.destroy',
    ]);

    Route::get('/mailboxes/{mailbox_id}/custom-fields/json', [
        'uses' => 'CustomFieldController@getByMailbox',
        'as'   => 'laporpoliwangi.custom_fields.json',
    ]);

    Route::post('/conversations/custom-field-value/update', [
        'uses' => 'ConversationCustomFieldController@update',
        'as'   => 'laporpoliwangi.conversations.custom_field_value.update',
    ]);

    /*
    |--------------------------------------------------------------------------
    | End User Portal Settings
    |--------------------------------------------------------------------------
    */
    Route::get('/mailboxes/{mailbox_id}/end-user-portal', [
        'uses' => 'EndUserPortalSettingController@index',
        'as'   => 'laporpoliwangi.end_user_portal.setting',
    ]);

    Route::post('/mailboxes/{mailbox_id}/end-user-portal', [
        'uses' => 'EndUserPortalSettingController@update',
        'as'   => 'laporpoliwangi.end_user_portal.update',
    ]);

    Route::post('/mailboxes/{mailbox_id}/end-user-portal/widget-auto-save', [
        'uses' => 'EndUserPortalSettingController@widgetAutoSave',
        'as'   => 'laporpoliwangi.end_user_portal.widget_auto_save',
    ]);

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
        'as'   => 'laporpoliwangi.reports.time_tracking',
    ]);

    /*
|--------------------------------------------------------------------------
| Time Tracking AJAX
|--------------------------------------------------------------------------
*/
    Route::post('/time-tracking/status', [
        'uses' => 'TimeTrackingController@status',
        'as'   => 'laporpoliwangi.time_tracking.status',
    ]);

    Route::post('/time-tracking/stop', [
        'uses' => 'TimeTrackingController@stop',
        'as'   => 'laporpoliwangi.time_tracking.stop',
    ]);
    /*
|--------------------------------------------------------------------------
| Time Tracking Status
|--------------------------------------------------------------------------
| Timer berjalan otomatis dari hook.
| Endpoint ini hanya untuk membaca status timer di tampilan conversation.
|--------------------------------------------------------------------------
*/
    Route::post('/time-tracking/status', [
        'uses' => 'TimeTrackingController@status',
        'as'   => 'laporpoliwangi.time_tracking.status',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Satisfaction Ratings Settings & Report
    |--------------------------------------------------------------------------
    */
    Route::get('/mailboxes/{mailbox_id}/satisfaction-ratings', [
        'uses' => 'SatisfactionRatingController@index',
        'as'   => 'laporpoliwangi.satisfaction_ratings.index',
    ]);

    Route::post('/mailboxes/{mailbox_id}/satisfaction-ratings/settings', [
        'uses' => 'SatisfactionRatingController@updateSettings',
        'as'   => 'laporpoliwangi.satisfaction_ratings.update_settings',
    ]);

    Route::post('/mailboxes/{mailbox_id}/satisfaction-ratings/translate', [
        'uses' => 'SatisfactionRatingController@updateTranslate',
        'as'   => 'laporpoliwangi.satisfaction_ratings.update_translate',
    ]);

    Route::post('/mailboxes/{mailbox_id}/satisfaction-ratings/settings/reset', [
        'uses' => 'SatisfactionRatingController@resetSettingsDefaults',
        'as'   => 'laporpoliwangi.satisfaction_ratings.reset_settings',
    ]);

    Route::post('/mailboxes/{mailbox_id}/satisfaction-ratings/translate/reset', [
        'uses' => 'SatisfactionRatingController@resetTranslateDefaults',
        'as'   => 'laporpoliwangi.satisfaction_ratings.reset_translate',
    ]);

    Route::get('/mailboxes/{mailbox_id}/satisfaction-ratings/report', [
        'uses' => 'SatisfactionRatingController@report',
        'as'   => 'laporpoliwangi.satisfaction_ratings.report',
    ]);

    /*
|--------------------------------------------------------------------------
| Saved Replies
|--------------------------------------------------------------------------
| URL:
| /lapor-poliwangi/mailboxes/{id}/saved-replies
|--------------------------------------------------------------------------
*/
    Route::get('/mailboxes/{id}/saved-replies', [
        'uses' => 'SavedRepliesController@index',
        'as'   => 'laporpoliwangi.saved_replies',
    ]);

    Route::post('/mailboxes/{id}/saved-replies', [
        'uses' => 'SavedRepliesController@store',
        'as'   => 'laporpoliwangi.saved_replies.store',
    ]);

    Route::put('/mailboxes/{id}/saved-replies/{reply_id}', [
        'uses' => 'SavedRepliesController@update',
        'as'   => 'laporpoliwangi.saved_replies.update',
    ]);

    Route::delete('/mailboxes/{id}/saved-replies/{reply_id}', [
        'uses' => 'SavedRepliesController@destroy',
        'as'   => 'laporpoliwangi.saved_replies.destroy',
    ]);
});


/*
|--------------------------------------------------------------------------
| Notification Channels Routes
|--------------------------------------------------------------------------
| Sengaja tidak memakai prefix lapor-poliwangi supaya route lama:
| notification_channels.index, store, update, destroy, test, toggle_active
| tetap aman.
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => ['web', 'auth'],
    'namespace'  => 'Modules\LaporPoliwangi\Http\Controllers',
], function () {

    Route::get('/notification-channels', [
        'uses' => 'NotificationChannelController@index',
        'as'   => 'notification_channels.index',
    ]);

    Route::post('/notification-channels', [
        'uses' => 'NotificationChannelController@store',
        'as'   => 'notification_channels.store',
    ]);

    Route::put('/notification-channels/{id}', [
        'uses' => 'NotificationChannelController@update',
        'as'   => 'notification_channels.update',
    ]);

    Route::delete('/notification-channels/{id}', [
        'uses' => 'NotificationChannelController@destroy',
        'as'   => 'notification_channels.destroy',
    ]);

    Route::post('/notification-channels/{id}/test', [
        'uses' => 'NotificationChannelController@test',
        'as'   => 'notification_channels.test',
    ]);

    Route::post('/notification-channels/{id}/toggle-active', [
        'uses' => 'NotificationChannelController@toggleActive',
        'as'   => 'notification_channels.toggle_active',
    ]);
});


/*
|--------------------------------------------------------------------------
| Public Satisfaction Rating From Email
|--------------------------------------------------------------------------
| Tidak pakai auth karena customer membuka link rating dari email.
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => ['web'],
    'namespace'  => 'Modules\LaporPoliwangi\Http\Controllers',
], function () {

    Route::get('/mailbox/{mailbox_id}/satisfaction-ratings/rate/{conversation_id}/{thread_id}/{rating}', [
        'uses' => 'SatisfactionRatingController@rateFromEmail',
        'as'   => 'mailboxes.satisfaction_ratings.rate_from_email',
    ]);
});


/*
|--------------------------------------------------------------------------
| Public End User Portal Routes
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => ['web'],
    'namespace'  => 'Modules\LaporPoliwangi\Http\Controllers',
], function () {

    Route::get('/help/auth', [
        'uses' => 'EndUserPortalController@selectAuth',
        'as'   => 'laporpoliwangi.end_user_portal.auth_select',
    ]);

    Route::get('/help/register', [
        'uses' => 'EndUserPortalController@registerEndUser',
        'as'   => 'laporpoliwangi.end_user_portal.register',
    ]);

    Route::post('/help/register', [
        'uses' => 'EndUserPortalController@registerEndUserSubmit',
        'as'   => 'laporpoliwangi.end_user_portal.register.submit',
    ]);

    Route::get('/help/login', [
        'uses' => 'EndUserPortalController@loginEndUser',
        'as'   => 'laporpoliwangi.end_user_portal.login_end_user',
    ]);

    Route::post('/help/login', [
        'uses' => 'EndUserPortalController@loginEndUserSubmit',
        'as'   => 'laporpoliwangi.end_user_portal.login.submit',
    ]);

    Route::get('/help/logout', [
        'uses' => 'EndUserPortalController@logoutEndUser',
        'as'   => 'laporpoliwangi.end_user_portal.logout',
    ]);

    Route::get('/help/sso/poliwangi', [
        'uses' => 'EndUserPortalController@redirectToPoliwangiSso',
        'as'   => 'laporpoliwangi.end_user_portal.sso.poliwangi',
    ]);

    Route::get('/help/sso/poliwangi/callback', [
        'uses' => 'EndUserPortalController@handlePoliwangiSsoCallback',
        'as'   => 'laporpoliwangi.end_user_portal.sso.poliwangi.callback',
    ]);

    Route::get('/help', [
        'uses' => 'EndUserPortalController@selectMailbox',
        'as'   => 'laporpoliwangi.end_user_portal.select_mailbox',
    ]);

    Route::get('/help/my/tickets', [
        'uses' => 'EndUserPortalController@myTickets',
        'as'   => 'laporpoliwangi.end_user_portal.my_ticket',
    ]);

    Route::get('/help/{mailbox_id}', [
        'uses' => 'EndUserPortalController@showPortal',
        'as'   => 'laporpoliwangi.end_user_portal.submit_ticket',
    ]);

    Route::post('/help/{mailbox_id}', [
        'uses' => 'EndUserPortalController@submitTicket',
        'as'   => 'laporpoliwangi.end_user_portal.submit',
    ]);

    Route::get('/help/{mailbox_id}/ticket/{conversation_id}', [
        'uses' => 'EndUserPortalController@ticketDetail',
        'as'   => 'laporpoliwangi.end_user_portal.ticket_detail',
    ]);

    Route::post('/help/{mailbox_id}/ticket/{conversation_id}/reply', [
        'uses' => 'EndUserPortalController@replyTicket',
        'as'   => 'laporpoliwangi.end_user_portal.ticket_reply',
    ]);

    Route::post('/help/{mailbox_id}/ticket/{conversation_id}/satisfaction-rating', [
        'uses' => 'SatisfactionRatingController@submitRating',
        'as'   => 'laporpoliwangi.end_user_portal.submit_satisfaction_rating',
    ]);
});


/*
|--------------------------------------------------------------------------
| Public Notification Webhook Routes
|--------------------------------------------------------------------------
| Tidak pakai auth karena Telegram/WhatsApp mengirim request dari luar.
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => ['web'],
    'namespace'  => 'Modules\LaporPoliwangi\Http\Controllers',
], function () {

    Route::post('/notification/webhook/{type}', [
        'uses' => 'NotificationWebhookController@handle',
        'as'   => 'notification_channels.webhook',
    ]);
});
