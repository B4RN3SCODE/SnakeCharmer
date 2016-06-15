<?php
date_default_timezone_set("America/Los_Angeles");
/*
 * Uninstall callback from BigCommerce
 */
$data = "\n------------------------------\n";
$data = "\n------------------------------\n";
$data .= var_export($_REQUEST, true);
$data .= "\n------------------------------\n";
file_put_contents("/var/www/logs/uninstall.log", $data, FILE_APPEND);
?>
