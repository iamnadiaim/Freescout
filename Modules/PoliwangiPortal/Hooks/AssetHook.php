<?php

namespace Modules\PoliwangiPortal\Hooks;

class AssetHook
{
    public static function register()
    {
        \Eventy::addFilter('stylesheets', function ($styles) {
            $styles[] = '/css/customfield.css';
            $styles[] = '/css/notification-channels.css';
            $styles[] = '/css/PoliwangiPortal-conversation.css';
            $styles[] = '/css/saved-replies.css';
            $styles[] = '/css/rich-editor.css';

            return $styles;
        });

        \Eventy::addFilter('javascripts', function ($scripts) {
            $scripts[] = '/js/conversation-extensions.js';
            $scripts[] = '/js/saved-replies-editor.js';

            return $scripts;
        });
    }
}
