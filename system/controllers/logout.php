<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$redirectRoute = !empty($_SESSION['aid']) ? 'dashboard' : (!empty($_SESSION['uid']) ? 'home' : 'login');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Logout attempted with invalid request method from IP ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    http_response_code(405);
    _alert(Lang::T('Invalid logout request.'), 'danger', $redirectRoute);
}

$csrf_token_logout = _post('csrf_token_logout');

if ($csrf_token_logout === '') {
    error_log('CSRF token not provided during logout from IP ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    http_response_code(400);
    _alert(Lang::T('CSRF token missing, logout aborted.'), 'danger', $redirectRoute);
}

if (!Csrf::check($csrf_token_logout)) {
    error_log('Invalid CSRF token during logout from IP ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    http_response_code(400);
    _alert(Lang::T('Invalid logout request.'), 'danger', $redirectRoute);
}

run_hook('customer_logout'); #HOOK

Admin::removeCookie();
User::removeCookie();
session_destroy();
_alert(Lang::T('Logout Successful'), 'warning', 'login');
