<?php
$html = file_get_contents('coverage/Http/Controllers/EndUserPortalController.php.html');
preg_match_all('/<tr class="danger d-flex"><td  class="col-1 text-right"><a id="(\d+)"/i', $html, $matches);
echo "Uncovered lines: " . implode(', ', $matches[1]) . "\n";
