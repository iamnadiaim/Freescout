<?php
$baseDir = __DIR__ . '/coverage';
if (!is_dir($baseDir)) {
    die("Directory 'coverage' not found.\n");
}

$ite = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
$files = new RegexIterator($ite, '/^.+\.php\.html$/i', RecursiveRegexIterator::GET_MATCH);

$totalUncovered = 0;
foreach($files as $file) {
    $path = $file[0];
    $html = file_get_contents($path);
    preg_match_all('/<tr class="danger d-flex"><td  class="col-1 text-right"><a id="([^"]+)".*?<\/tr>/', $html, $matches);
    if (!empty($matches[1])) {
        $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path);
        $relativePath = str_replace('\\', '/', $relativePath);
        echo "File: $relativePath\n";
        echo "Uncovered Lines: " . implode(', ', $matches[1]) . "\n\n";
        $totalUncovered += count($matches[1]);
    }
}

if ($totalUncovered === 0) {
    echo "All lines are covered!\n";
} else {
    echo "Total uncovered lines: $totalUncovered\n";
}
