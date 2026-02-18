<?php

$isHttps = false;
if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
    $isHttps = true;
} elseif (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
    $isHttps = true;
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $forwardedProto = strtolower(trim((string) explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
    $isHttps = ($forwardedProto === 'https');
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL'])) {
    $isHttps = (strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on');
} elseif (!empty($_SERVER['HTTP_CF_VISITOR'])) {
    $cfVisitor = json_decode((string) $_SERVER['HTTP_CF_VISITOR'], true);
    if (is_array($cfVisitor) && isset($cfVisitor['scheme'])) {
        $isHttps = (strtolower((string) $cfVisitor['scheme']) === 'https');
    }
}
$protocol = $isHttps ? "https://" : "http://";

// Check if HTTP_HOST is set, otherwise use a default value or SERVER_NAME
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');

$baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
define('APP_URL', $protocol . $host . $baseDir);


$_app_stage = 'Live'; # Do not change this

$db_host    = "localhost"; # Database Host
$db_port    = "";   # Database Port. Keep it blank if you are un sure.
$db_user    = "root"; # Database Username
$db_pass    = ""; # Database Password
$db_name    = "phpnuxbill"; # Database Name




//error reporting
if($_app_stage!='Live'){
    error_reporting(E_ERROR);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}else{
    error_reporting(E_ERROR);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}
