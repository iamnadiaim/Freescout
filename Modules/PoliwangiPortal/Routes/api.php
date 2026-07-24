<?php

use Illuminate\Support\Facades\Route;
use Modules\PoliwangiNotification\Http\Middleware\VerifyWebhookToken;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::group([
    'middleware' => ['open', 'throttle:120,1', VerifyWebhookToken::class],
    'namespace'  => 'Modules\PoliwangiNotification\Http\Controllers',
], function () {

    Route::post('/notification/webhook/{type}', [
        'uses' => 'NotificationChannelController@webhook',
        'as'   => 'notification_channels.webhook',
    ]);

});
