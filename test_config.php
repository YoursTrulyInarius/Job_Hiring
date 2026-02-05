<?php
// Test for whitespace output from config
ob_start();
require_once 'config/config.php';
$output = ob_get_clean();

if (strlen($output) > 0) {
    echo "Config file is outputting " . strlen($output) . " bytes of data: [" . bin2hex($output) . "]";
} else {
    echo "Config file is clean.";
}
