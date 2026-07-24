<?php

Route::group(['middleware' => 'web', 'namespace' => 'Modules\PoliwangiSso\Http\Controllers'], function () {
    Route::get('/auth/sso/redirect', 'PoliwangiSsoController@redirectToProvider')->name('poliwangisso.redirect');
    Route::get('/auth/sso/callback', 'PoliwangiSsoController@handleProviderCallback')->name('poliwangisso.callback');
    
    // Alias untuk callback sesuai OIDC_REDIRECT_URI di production (https://laporpoliwangi.my.id/oidc/callback)
    Route::get('/oidc/callback', 'PoliwangiSsoController@handleProviderCallback');

    // End User Portal SSO Routes
    Route::get('/help/sso/poliwangi', [
        'uses' => 'SsoController@redirectToPoliwangiSso',
        'as'   => 'PoliwangiPortal.end_user_portal.sso.poliwangi',
    ]);

    Route::get('/help/sso/poliwangi/callback', [
        'uses' => 'SsoController@handlePoliwangiSsoCallback',
        'as'   => 'PoliwangiPortal.end_user_portal.sso.poliwangi.callback',
    ]);
});
