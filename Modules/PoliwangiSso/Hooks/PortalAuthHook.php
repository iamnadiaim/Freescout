<?php

namespace Modules\PoliwangiSso\Hooks;

class PortalAuthHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        \Eventy::addAction('portal.auth.methods', function ($redirect) {
            echo '<a class="btn btn-sso" href="' . route('PoliwangiPortal.end_user_portal.sso.poliwangi', ['redirect' => $redirect]) . '">Masuk dengan SSO Poliwangi</a>';
        });
    }
}
