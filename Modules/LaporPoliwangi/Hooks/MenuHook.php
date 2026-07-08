<?php

namespace Modules\LaporPoliwangi\Hooks;

use Illuminate\Support\Facades\Route;

class MenuHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        \Eventy::addAction('menu.append', function () {
            $user = auth()->user();

            if (!$user) {
                return;
            }

            /*
             * Report hanya tampil untuk admin.
             * Kalau nanti operator juga boleh lihat report, bagian ini bisa dilonggarkan.
             */
            if (!$user->isAdmin()) {
                return;
            }

            $isActive = in_array(Route::currentRouteName(), [
                'laporpoliwangi.reports.time_tracking',
            ]);

            echo view('laporpoliwangi::partials.navbar_reports_menu', [
                'isActive' => $isActive,
            ])->render();
        }, 20);
    }
}
