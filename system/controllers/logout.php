<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Pragma: no-cache");

if (!empty($isApi)) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        showResult(false, Lang::T('Invalid logout request.'), [], ['allowed_methods' => ['POST']]);
    }

    run_hook('customer_logout'); #HOOK

    // API logout is stateless. Best-effort clear cookies/session for this request only.
    Admin::removeCookie();
    User::removeCookie();
    if (session_status() !== PHP_SESSION_NONE) {
        $_SESSION = [];
        session_destroy();
    } else {
        $_SESSION = [];
    }

    showResult(true, Lang::T('Logout Successful'));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdminSession = !empty($_SESSION['aid']);
$redirectRoute = $isAdminSession ? 'dashboard' : (!empty($_SESSION['uid']) ? 'home' : 'login');
$logoutRedirectRoute = $isAdminSession ? 'admin' : 'login';

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
_alert(Lang::T('Logout Successful'), 'warning', $logoutRedirectRoute);
