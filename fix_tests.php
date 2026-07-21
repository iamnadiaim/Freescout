<?php

$dir = __DIR__ . '/tests/Feature';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$replacements = [
    // Routes
    "route('laporpoliwangi.time_tracking" => "route('poliwangitimetracking.time_tracking",
    "route('laporpoliwangi.saved_replies" => "route('poliwangisavedreply.saved_replies",
    "route('laporpoliwangi.satisfaction_ratings" => "route('poliwangisatisfaction.satisfaction_ratings",
    "route('laporpoliwangi.end_user_portal" => "route('poliwangiportal.end_user_portal",
    "route('laporpoliwangi.custom_fields" => "route('poliwangicustomfield.custom_fields",
    "route('laporpoliwangi.custom_field_values" => "route('poliwangicustomfield.custom_field_values",
    
    // Views
    "'laporpoliwangi::satisfaction_ratings" => "'poliwangisatisfaction::satisfaction_ratings",
    "'laporpoliwangi::end_user_portal" => "'PoliwangiPortal::end_user_portal",
    "'laporpoliwangi::conversation.time_tracking" => "'poliwangitimetracking::conversation.time_tracking",
    
    // Namespaces
    "Modules\\LaporPoliwangi\\Http\\Controllers\\TimeTrackingController" => "Modules\\PoliwangiTimeTracking\\Http\\Controllers\\TimeTrackingController",
    "Modules\\LaporPoliwangi\\Http\\Controllers\\NotificationChannelController" => "Modules\\PoliwangiNotification\\Http\\Controllers\\NotificationChannelController",
    "Modules\\LaporPoliwangi\\Http\\Controllers\\EndUserPortalSettingController" => "Modules\\PoliwangiPortal\\Http\\Controllers\\EndUserPortalSettingController",
    "Modules\\LaporPoliwangi\\Http\\Controllers\\CustomFieldController" => "Modules\\PoliwangiCustomField\\Http\\Controllers\\CustomFieldController",
    "Modules\\LaporPoliwangi\\Http\\Controllers\\CustomFieldValueController" => "Modules\\PoliwangiCustomField\\Http\\Controllers\\CustomFieldValueController",
    "Modules\\LaporPoliwangi\\Http\\Controllers\\SatisfactionRatingController" => "Modules\\PoliwangiSatisfaction\\Http\\Controllers\\SatisfactionRatingController",
    
    // Migrations
    "Modules/LaporPoliwangi/Database/Migrations" => "Modules/PoliwangiSatisfaction/Database/Migrations", // Assuming SatisfactionRatingTest uses this for satisfaction ratings migrations
];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        // General replacements for LaporPoliwangi namespace missing above
        // $content = str_replace("Modules\\LaporPoliwangi\\Models", "Modules\\...", $content); // User already fixed models mostly?
        
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
echo "Done.\n";
