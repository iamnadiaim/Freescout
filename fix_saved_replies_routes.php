<?php

$dir = __DIR__ . '/Modules/PoliwangiSavedReply';

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($iterator as $file) {
    if ($file->isFile() && in_array($file->getExtension(), ['php'])) {
        $content = file_get_contents($file->getPathname());
        $newContent = str_replace('PoliwangiPortal.saved_replies', 'poliwangisavedreply.saved_replies', $content);
        if ($content !== $newContent) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
echo "Done.\n";
