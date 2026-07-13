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
             * Report hanya tampil untuk admin atau Petugas yang mengelola minimal 1 mailbox (punya izin 'Edit Mailbox').
             */
            $canViewReport = false;
            
            if ($user->isAdmin()) {
                $canViewReport = true;
            } else {
                $managesAnyMailbox = \Illuminate\Support\Facades\DB::table('mailbox_user')
                    ->where('user_id', $user->id)
                    ->where('access', 'like', '%"edit"%')
                    ->exists();
                
                if ($managesAnyMailbox) {
                    $canViewReport = true;
                }
            }

            if (!$canViewReport) {
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
