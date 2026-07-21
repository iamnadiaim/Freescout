<?php

Route::group(['middleware' => 'web', 'namespace' => 'Modules\PoliwangiSso\Http\Controllers'], function () {
    Route::get('/auth/sso/redirect', 'PoliwangiSsoController@redirectToProvider')->name('poliwangisso.redirect');
    Route::get('/auth/sso/callback', 'PoliwangiSsoController@handleProviderCallback')->name('poliwangisso.callback');
    
    // Alias untuk callback sesuai OIDC_REDIRECT_URI di production (https://uc3.poliwangi.ac.id/oidc/callback)
    Route::get('/oidc/callback', 'PoliwangiSsoController@handleProviderCallback');
});
