<?php

$dir = __DIR__ . '/tests/Feature';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$replacements = [
    // Routes we incorrectly mapped to individual modules, they are actually all under PoliwangiPortal prefix
    "route('poliwangitimetracking.time_tracking" => "route('PoliwangiPortal.time_tracking",
    "route('poliwangisavedreply.saved_replies" => "route('PoliwangiPortal.saved_replies",
    "route('poliwangisatisfaction.satisfaction_ratings" => "route('PoliwangiPortal.satisfaction_ratings",
    "route('poliwangiportal.end_user_portal" => "route('PoliwangiPortal.end_user_portal",
    "route('poliwangicustomfield.custom_fields" => "route('PoliwangiPortal.custom_fields",
    "route('poliwangicustomfield.custom_field_values" => "route('PoliwangiPortal.custom_field_values",
    
    // Also if any laporpoliwangi are left
    "route('laporpoliwangi.time_tracking" => "route('PoliwangiPortal.time_tracking",
    "route('laporpoliwangi.saved_replies" => "route('PoliwangiPortal.saved_replies",
    "route('laporpoliwangi.satisfaction_ratings" => "route('PoliwangiPortal.satisfaction_ratings",
    "route('laporpoliwangi.end_user_portal" => "route('PoliwangiPortal.end_user_portal",
    "route('laporpoliwangi.custom_fields" => "route('PoliwangiPortal.custom_fields",
    "route('laporpoliwangi.custom_field_values" => "route('PoliwangiPortal.custom_field_values",
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
