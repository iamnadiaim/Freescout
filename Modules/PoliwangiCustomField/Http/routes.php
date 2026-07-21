<?php

Route::group([
    'middleware' => ['web', 'auth'],
    'namespace'  => 'Modules\PoliwangiCustomField\Http\Controllers',
    'prefix'     => \Helper::getSubdirectory() . 'lapor-poliwangi',
], function () {

    /*
    |--------------------------------------------------------------------------
    | Custom Fields
    |--------------------------------------------------------------------------
    */
    Route::get('/mailboxes/{mailbox_id}/custom-fields', [
        'uses' => 'CustomFieldController@index',
        'as'   => 'PoliwangiPortal.custom_fields',
    ]);

    Route::post('/mailboxes/{mailbox_id}/custom-fields', [
        'uses' => 'CustomFieldController@store',
        'as'   => 'PoliwangiPortal.custom_fields.store',
    ]);

    Route::put('/mailboxes/{mailbox_id}/custom-fields/{field_id}', [
        'uses' => 'CustomFieldController@update',
        'as'   => 'PoliwangiPortal.custom_fields.update',
    ]);

    Route::delete('/mailboxes/{mailbox_id}/custom-fields/{field_id}', [
        'uses' => 'CustomFieldController@destroy',
        'as'   => 'PoliwangiPortal.custom_fields.destroy',
    ]);

    Route::get('/mailboxes/{mailbox_id}/custom-fields/json', [
        'uses' => 'CustomFieldController@getByMailbox',
        'as'   => 'PoliwangiPortal.custom_fields.json',
    ]);

    Route::post('/conversations/custom-field-value/update', [
        'uses' => 'ConversationCustomFieldController@update',
        'as'   => 'PoliwangiPortal.conversations.custom_field_value.update',
    ]);
});
