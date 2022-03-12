<?php
$address = (getenv('SERVE_ADDRESS')) ?: 'localhost:8888';
exec('cd ../');
exec("php -d opcache.enable=0 -S $address tests/serve-router.php");
// PHP_CLI_SERVER_WORKERS seems to cause issues in PHP8+ with responses randomly hanging
// exec("PHP_CLI_SERVER_WORKERS=2 php -d opcache.enable=0 -S $address tests/serve-router.php");
