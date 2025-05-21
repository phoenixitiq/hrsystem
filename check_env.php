<?php
echo "APP_ENV: " . (getenv('APP_ENV') ?: 'not set') . "\n";
echo "\$_SERVER['ENV']: " . ($_SERVER['ENV'] ?: 'not set') . "\n";
?>
