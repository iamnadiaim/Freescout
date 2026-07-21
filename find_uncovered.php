<?php
$baseDir = __DIR__ . '/coverage';
if (!is_dir($baseDir)) exit;
$ite = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
$files = new RegexIterator($ite, '/^.+\.php\.html$/i', RecursiveRegexIterator::GET_MATCH);
$allLines = [];
foreach($files as $file) {
    $html = file_get_contents($file[0]);
    preg_match_all('/<tr class=\"danger d-flex\"><td  class=\"col-1 text-right\"><a id=\"(\d+)\"/', $html, $matches);
    if (!empty($matches[1])) {
        $rel = str_replace('\\', '/', str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file[0]));
        $allLines[] = $rel . ":" . implode(',', $matches[1]);
    }
}
echo implode('|', $allLines);
