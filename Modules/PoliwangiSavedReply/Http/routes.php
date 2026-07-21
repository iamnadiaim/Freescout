<?php

Route::group([
    'middleware' => ['web', 'auth'],
    'namespace'  => 'Modules\PoliwangiSavedReply\Http\Controllers',
    'prefix'     => \Helper::getSubdirectory() . 'lapor-poliwangi',
], function () {

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
        'as'   => 'poliwangisavedreply.saved_replies',
    ]);

    Route::post('/mailboxes/{id}/saved-replies', [
        'uses' => 'SavedRepliesController@store',
        'as'   => 'poliwangisavedreply.saved_replies.store',
    ]);

    Route::put('/mailboxes/{id}/saved-replies/{reply_id}', [
        'uses' => 'SavedRepliesController@update',
        'as'   => 'poliwangisavedreply.saved_replies.update',
    ]);

    Route::delete('/mailboxes/{id}/saved-replies/{reply_id}', [
        'uses' => 'SavedRepliesController@destroy',
        'as'   => 'poliwangisavedreply.saved_replies.destroy',
    ]);

});
