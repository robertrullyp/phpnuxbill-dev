<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 * This File is for API Access
 **/


if ($_SERVER['REQUEST_METHOD'] === "OPTIONS" || $_SERVER['REQUEST_METHOD'] === "HEAD") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method, Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}

$isApi = true;

include "../init.php";

// Ensure admin context is available for role checks when using API key headers.
$admin = Admin::_info();

// Dummy Class
$ui = new class($key)
{
    var $assign = [];
    function display($key)
    {
        global $req;
        showResult(true, $req, $this->getAll());
    }
    function assign($key, $value)
    {
        $this->assign[$key] = $value;
    }
    function get($key)
    {
        if (isset($this->assign[$key])) {
            return $this->assign[$key];
        }
        return '';
    }
    function getTemplateVars($key)
    {
        if (isset($this->assign[$key])) {
            return $this->assign[$key];
        }
        return '';
    }
    function getAll()
    {

        $result = [];
        foreach ($this->assign as $key => $value) {
            if($value instanceof ORM){
                $result[$key] = $value->as_array();
            }else if($value instanceof IdiormResultSet){
                $count = count($value);
                for($n=0;$n<$count;$n++){
                    foreach ($value[$n] as $k=>$v) {
                        $result[$key][$n][$k] = $v;
                    }
                }
            }else{
                $result[$key] = $value;
            }
        }
        return $result;
    }

    function fetch()
    {
        return "";
    }
};

function api_get_client_ip()
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function api_rate_limit_check($key, $max, $window)
{
    $max = (int) $max;
    $window = (int) $window;
    if ($max <= 0 || $window <= 0) {
        return ['allowed' => true];
    }

    $cacheBase = $GLOBALS['CACHE_PATH'] ?? null;
    if (!$cacheBase) {
        return ['allowed' => true];
    }

    $dir = rtrim($cacheBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'api_rate_limit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_dir($dir)) {
        return ['allowed' => true];
    }

    $now = time();
    $hash = hash('sha256', $key);
    $path = $dir . DIRECTORY_SEPARATOR . $hash . '.json';

    $data = [
        'count' => 0,
        'reset_at' => $now + $window,
    ];
    if (is_file($path)) {
        $raw = @file_get_contents($path);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['count'], $decoded['reset_at'])) {
                if ((int) $decoded['reset_at'] > $now) {
                    $data = [
                        'count' => (int) $decoded['count'],
                        'reset_at' => (int) $decoded['reset_at'],
                    ];
                }
            }
        }
    }

    $data['count']++;
    $allowed = $data['count'] <= $max;

    @file_put_contents($path, json_encode($data), LOCK_EX);

    return [
        'allowed' => $allowed,
        'count' => $data['count'],
        'reset_at' => $data['reset_at'],
    ];
}

function api_permissions_payload($admin, $customer, $config, $token = '')
{
    $role = '';
    $identity = [];
    $subject = 'guest';

    if ($admin) {
        $role = is_array($admin) ? ($admin['user_type'] ?? '') : ($admin->user_type ?? '');
        $identity = [
            'id' => is_array($admin) ? ($admin['id'] ?? null) : ($admin->id ?? null),
            'username' => is_array($admin) ? ($admin['username'] ?? '') : ($admin->username ?? ''),
            'fullname' => is_array($admin) ? ($admin['fullname'] ?? '') : ($admin->fullname ?? ''),
            'user_type' => $role,
        ];
        $subject = 'admin';
    } elseif ($customer) {
        $role = 'Customer';
        $identity = [
            'id' => is_array($customer) ? ($customer['id'] ?? null) : ($customer->id ?? null),
            'username' => is_array($customer) ? ($customer['username'] ?? '') : ($customer->username ?? ''),
            'fullname' => is_array($customer) ? ($customer['fullname'] ?? '') : ($customer->fullname ?? ''),
            'user_type' => $role,
        ];
        $subject = 'customer';
    }

    $is_superadmin = ($role === 'SuperAdmin');
    $is_admin = in_array($role, ['SuperAdmin', 'Admin'], true);
    $is_report = ($role === 'Report');
    $is_agent = ($role === 'Agent');
    $is_sales = ($role === 'Sales');

    $disable_voucher = ($config['disable_voucher'] ?? 'no') === 'yes';
    $enable_balance = ($config['enable_balance'] ?? 'no') === 'yes';
    $enable_coupons = ($config['enable_coupons'] ?? 'no') === 'yes';
    $radius_enable = !empty($config['radius_enable']);
    $payment_gateway = $config['payment_gateway'] ?? '';
    $payment_enabled = ($payment_gateway != 'none' || $payment_gateway == '');

    $permissions = [
        'subject' => $subject,
        'role' => $role,
        'is_superadmin' => $is_superadmin,
        'is_admin' => $is_admin,
        'is_report' => $is_report,
        'is_agent' => $is_agent,
        'is_sales' => $is_sales,
        'features' => [
            'disable_voucher' => $disable_voucher,
            'enable_balance' => $enable_balance,
            'enable_coupons' => $enable_coupons,
            'radius_enable' => $radius_enable,
            'payment_gateway' => $payment_gateway,
            'payment_enabled' => $payment_enabled,
        ],
        'menus' => [],
    ];

    if ($subject === 'admin') {
        $permissions['menus'] = [
            'dashboard' => true,
            'customers' => true,
            'services' => !$is_report,
            'services_items' => [
                'active_customers' => !$is_report,
                'refill' => !$is_report && !$disable_voucher,
                'vouchers' => !$is_report && !$disable_voucher,
                'coupons' => !$is_report && $enable_coupons,
                'recharge' => !$is_report,
                'deposit' => !$is_report && $enable_balance,
            ],
            'internet_plan' => $is_admin,
            'internet_plan_items' => [
                'hotspot' => $is_admin,
                'pppoe' => $is_admin,
                'vpn' => $is_admin,
                'bandwidth' => $is_admin,
                'balance' => $is_admin && $enable_balance,
            ],
            'maps' => true,
            'reports' => $is_admin || $is_report,
            'message' => $is_admin || $is_agent || $is_sales,
            'network' => $is_admin,
            'radius' => $is_admin && $radius_enable,
            'pages' => $is_admin,
            'settings' => true,
            'settings_items' => [
                'app' => $is_admin,
                'localisation' => $is_admin,
                'customfield' => $is_admin,
                'miscellaneous' => $is_admin,
                'maintenance' => $is_admin,
                'widgets' => $is_admin,
                'notifications' => $is_admin,
                'devices' => $is_admin,
                'users' => $is_admin || $is_agent,
                'dbstatus' => $is_admin,
                'paymentgateway' => $is_admin,
                'pluginmanager' => $is_admin,
            ],
            'logs' => $is_admin,
            'docs' => $is_admin,
            'community' => $is_admin,
        ];
    } elseif ($subject === 'customer') {
        $permissions['menus'] = [
            'dashboard' => true,
            'inbox' => true,
            'voucher' => !$disable_voucher,
            'buy_balance' => $payment_enabled && $enable_balance,
            'buy_package' => $payment_enabled,
            'payment_history' => $payment_enabled,
            'activation_history' => true,
        ];
    }

    $permissions['auth'] = [
        'via_api_key' => !empty($_SESSION['aid_api_key']),
        'via_token' => !empty($token),
    ];

    return [
        'identity' => $identity,
        'permissions' => $permissions,
    ];
}

$req = _get('r');
# a/c.id.time.md5
# md5(a/c.id.time.$api_secret)
$token = _req('token');
$routes = explode('/', $req);
$handler = $routes[0];

if (!empty($token)) {
    if ($token == $config['api_key']) {
        $admin = ORM::for_table('tbl_users')->where('user_type', 'SuperAdmin')->find_one();
        if (empty($admin)) {
            $admin = ORM::for_table('tbl_users')->where('user_type', 'Admin')->find_one();
            if (empty($admin)) {
                showResult(false, Lang::T("Token is invalid"));
            }
        }
        $_SESSION['aid'] = $admin->id ?? $admin['id'] ?? null;
        $_SESSION['aid_api_key'] = true;
    } else {
        # validate token
        list($tipe, $uid, $time, $sha1) = explode('.', $token);
        if (trim($sha1) != sha1($uid . '.' . $time . '.' . $api_secret)) {
            showResult(false, Lang::T("Token is invalid"));
        }

        #cek token expiration
        // 3 bulan
        if ($time != 0 && time() - $time > 7776000) {
            showResult(false, Lang::T("Token Expired"), [], ['login' => true]);
        }

        if ($tipe == 'a') {
            $_SESSION['aid'] = $uid;
            $admin = Admin::_info();
        } else if ($tipe == 'c') {
            $_SESSION['uid'] = $uid;
        } else {
            showResult(false, Lang::T("Unknown Token"), [], ['login' => true]);
        }
    }

    if (!isset($handler) || empty($handler)) {
        showResult(true, Lang::T("Token is valid"));
    }


    if ($handler == 'isValid') {
        showResult(true, Lang::T("Token is valid"));
    }

    if ($handler == 'me') {
        $admin = Admin::_info();
        if (!empty($admin['id'])) {
            showResult(true, "", $admin);
        } else {
            showResult(false, Lang::T("Token is invalid"));
        }
    }
}else{
    unset($_COOKIE);
    unset($_SESSION);
}

$admin = Admin::_info();
$customer = class_exists('User') ? User::_info() : null;
$api_key_backoff = $GLOBALS['api_key_backoff'] ?? null;
if (!$admin && !$customer && is_array($api_key_backoff)) {
    $wait = (int) ($api_key_backoff['retry_after'] ?? 0);
    if ($wait < 1) {
        $wait = 1;
    }
    if (!headers_sent()) {
        http_response_code(429);
        header('Retry-After: ' . $wait);
    }
    showResult(false, 'API key throttled', [], [
        'retry_after' => $wait,
    ]);
}

$rate_enabled = ($config['api_rate_limit_enabled'] ?? 'yes') !== 'no';
if ($rate_enabled) {
    $rate_max = (int) ($config['api_rate_limit_max'] ?? 120);
    $rate_window = (int) ($config['api_rate_limit_window'] ?? 60);
    $identity = $admin ? ('admin:' . ($admin['id'] ?? $admin->id ?? '0')) : ($customer ? ('customer:' . ($customer['id'] ?? $customer->id ?? '0')) : '');
    if ($identity === '') {
        $identity = 'ip:' . api_get_client_ip();
    }
    $rate = api_rate_limit_check($identity, $rate_max, $rate_window);
    if (empty($rate['allowed'])) {
        if (!headers_sent()) {
            http_response_code(429);
            header('Retry-After: ' . max(1, (int) ($rate['reset_at'] - time())));
        }
        showResult(false, 'Rate limit exceeded', [], [
            'retry_after' => max(1, (int) ($rate['reset_at'] - time())),
            'limit' => $rate_max,
            'window' => $rate_window,
        ]);
    }
}

if ($handler === 'whoami') {
    $sub = $routes[1] ?? '';
    if ($sub === '' || $sub === 'permissions') {
        if (!$admin && !$customer) {
            showResult(false, Lang::T('Unauthorized'), [], ['login' => true]);
        }
        $payload = api_permissions_payload($admin, $customer, $config, $token);
        showResult(true, 'ok', $payload);
    }
}

try {
    $sys_render = File::pathFixer($root_path . 'system/controllers/' . $handler . '.php');
    if (file_exists($sys_render)) {
        include($sys_render);
        if (!empty($GLOBALS['api_raw_output'])) {
            return;
        }
        showResult(true, $req, $ui->getAll());
    } else {
        showResult(false, Lang::T('Command not found'));
    }
} catch (Exception $e) {
    showResult(false, $e->getMessage());
}
