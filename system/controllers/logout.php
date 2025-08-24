<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Pragma: no-cache");

$csrf_token_logout = _post('csrf_token_logout');

if (!Csrf::check($csrf_token_logout)) {
    error_log('Invalid CSRF token during logout from IP ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
}

run_hook('customer_logout'); #HOOK

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
Admin::removeCookie();
User::removeCookie();
session_destroy();
_alert(Lang::T('Logout Successful'), 'warning', 'login');
