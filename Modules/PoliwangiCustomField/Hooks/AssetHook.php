<?php

namespace Modules\PoliwangiCustomField\Hooks;

class AssetHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        \Eventy::addFilter('stylesheets', function ($styles) {
            $styles[] = '/css/conversation-extensions.css';
            
            return $styles;
        });

        \Eventy::addFilter('javascripts', function ($scripts) {
            $scripts[] = '/js/conversation-extensions.js';
            
            return $scripts;
        });
    }
}
