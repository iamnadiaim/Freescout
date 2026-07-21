<?php
$baseDir = __DIR__ . '/coverage';
if (!is_dir($baseDir)) die("No coverage dir\n");
$ite = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
$files = new RegexIterator($ite, '/^.+\.php\.html$/i', RecursiveRegexIterator::GET_MATCH);

foreach($files as $file) {
    $path = $file[0];
    $html = file_get_contents($path);
    preg_match_all('/<tr class="danger d-flex"><td  class="col-1 text-right"><a id="(\d+)"/i', $html, $matches);
    if(!empty($matches[1])) {
        $rel = str_replace('\\', '/', str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path));
        echo "[$rel] Uncovered lines: " . implode(', ', $matches[1]) . "\n";
    }
}
