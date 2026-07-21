<?php

/*
|--------------------------------------------------------------------------
| Notification Channels Routes
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => ['web', 'auth', 'roles'],
    'roles'      => ['admin'],
    'prefix'     => \Helper::getSubdirectory(),
    'namespace'  => 'Modules\PoliwangiNotification\Http\Controllers',
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

Route::group([
    'middleware' => [\Modules\PoliwangiNotification\Http\Middleware\VerifyWebhookToken::class],
    'prefix'     => \Helper::getSubdirectory(),
    'namespace'  => 'Modules\PoliwangiNotification\Http\Controllers',
], function () {
    Route::match(['get', 'post'], '/notification-webhook/{type}', [
        'uses' => 'NotificationChannelController@webhook',
        'as'   => 'notification_channels.webhook',
    ]);
});
