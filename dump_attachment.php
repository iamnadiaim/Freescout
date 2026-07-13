<?php
require 'vendor/autoload.php';
$rc = new ReflectionMethod('App\Attachment', 'create');
echo "Method: " . $rc->getName() . "\n";
foreach ($rc->getParameters() as $p) {
    echo $p->getName() . ($p->isOptional() ? " (optional)" : " (required)") . "\n";
}
