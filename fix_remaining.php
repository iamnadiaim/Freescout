<?php

$dir = __DIR__ . '/tests/Feature';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$replacements = [
    // Routes
    "route('laporpoliwangi.reports.time_tracking" => "route('PoliwangiPortal.reports.time_tracking",
    
    // Views
    "'laporpoliwangi::reports.time_tracking'" => "'PoliwangiReport::reports.time_tracking'",
    "'laporpoliwangi::custom_field'" => "'PoliwangiCustomField::custom_field'",
    
    // Namespaces - Services
    "Modules\\LaporPoliwangi\\Services\\Notifications" => "Modules\\PoliwangiNotification\\Services\\Notifications",
    
    // Namespaces - Models
    "Modules\\LaporPoliwangi\\Models\\EndUserPortalAccount" => "Modules\\PoliwangiPortal\\Models\\EndUserPortalAccount",
    "Modules\\LaporPoliwangi\\Models\\EndUserPortalSetting" => "Modules\\PoliwangiPortal\\Models\\EndUserPortalSetting",
    "Modules\\LaporPoliwangi\\Models\\NotificationChannel" => "Modules\\PoliwangiNotification\\Models\\NotificationChannel",
    "Modules\\LaporPoliwangi\\Models\\CustomFieldValue" => "Modules\\PoliwangiCustomField\\Models\\CustomFieldValue",
    
    // Namespaces - Controllers
    "Modules\\LaporPoliwangi\\Http\\Controllers\\EndUserPortalController" => "Modules\\PoliwangiPortal\\Http\\Controllers\\EndUserPortalController",
    "Modules\\LaporPoliwangi\\Http\\Controllers\\ConversationCustomFieldController" => "Modules\\PoliwangiCustomField\\Http\\Controllers\\ConversationCustomFieldController",
];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
echo "Done.\n";
