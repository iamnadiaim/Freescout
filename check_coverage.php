<?php
$html = file_get_contents('coverage/Http/Controllers/EndUserPortalController.php.html');
preg_match_all('/<tr class="danger d-flex"><td  class="col-1 text-right"><a id="([^"]+)".*?<\/tr>/', $html, $matches);
echo "Uncovered Lines:\n";
foreach($matches[1] as $line) {
    echo $line . "\n";
}
