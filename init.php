<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    header('location: ../');
    die();
}
$root_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
if (!isset($isApi)) {
    $isApi = false;
}
if (file_exists($root_path . 'system/vendor/autoload.php')) {
    require_once $root_path . 'system/vendor/autoload.php';
}
// on some server, it getting error because of slash is backwards
function _autoloader($class)
{
    global $root_path;
    if (strpos($class, '_') !== false) {
        $class = str_replace('_', DIRECTORY_SEPARATOR, $class);
        if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    } else {
        if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    }
}
spl_autoload_register('_autoloader');

if (!file_exists($root_path . 'config.php')) {
    $root_path .= '..' . DIRECTORY_SEPARATOR;
    if (!file_exists($root_path . 'config.php')) {
        r2('./install');
    }
}

if (!file_exists($root_path .  File::pathFixer('system/orm.php'))) {
    echo $root_path . "orm.php file not found";
    die();
}

$DEVICE_PATH = $root_path . File::pathFixer('system/devices');
$UPLOAD_PATH = $root_path . File::pathFixer('system/uploads');
$CACHE_PATH = $root_path . File::pathFixer('system/cache');
$PAGES_PATH = $root_path . File::pathFixer('pages');
$PLUGIN_PATH = $root_path . File::pathFixer('system/plugin');
$WIDGET_PATH = $root_path . File::pathFixer('system/widgets');
$PAYMENTGATEWAY_PATH = $root_path . File::pathFixer('system/paymentgateway');
$UI_PATH = 'ui';

if (function_exists('imagecreatetruecolor') && function_exists('imagejpeg')) {
    $defaultAvatarFiles = [
        'admin.default.png',
        'user.default.jpg',
    ];

    foreach ($defaultAvatarFiles as $avatarFile) {
        $sourceFile = $UPLOAD_PATH . File::pathFixer('/' . ltrim($avatarFile, '/'));
        $thumbFile = $UPLOAD_PATH . File::pathFixer('/' . ltrim($avatarFile, '/') . '.thumb.jpg');
        if (file_exists($sourceFile) && !file_exists($thumbFile)) {
            File::makeThumb($sourceFile, $thumbFile, 200);
        }
    }
}

if (!file_exists($UPLOAD_PATH . File::pathFixer('/notifications.default.json'))) {
    echo $UPLOAD_PATH . File::pathFixer("/notifications.default.json file not found");
    die();
}

require_once $root_path . 'config.php';
require_once $root_path . File::pathFixer('system/orm.php');
require_once $root_path . File::pathFixer('system/autoload/PEAR2/Autoload.php');
include $root_path . File::pathFixer('system/autoload/Hookers.php');

if ($db_password != null && ($db_pass == null || empty($db_pass))) {
    // compability for old version
    $db_pass = $db_password;
}
if ($db_pass != null) {
    // compability for old version
    $db_password = $db_pass;
}
ORM::configure("mysql:host=$db_host;dbname=$db_name");
ORM::configure('username', $db_user);
ORM::configure('password', $db_pass);
ORM::configure('return_result_sets', true);
if ($_app_stage != 'Live') {
    ORM::configure('logging', true);
}
if ($isApi) {
    define('U', APP_URL . '/system/api.php?r=');
} else {
    define('U', APP_URL . '/?_route=');
}

// notification message
if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . "notifications.json")) {
    $_notifmsg = json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.json'), true);
}
$_notifmsg_default = json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.default.json'), true);

//register all plugin
foreach (glob(File::pathFixer($PLUGIN_PATH . DIRECTORY_SEPARATOR . '*.php')) as $filename) {
    try {
        include $filename;
    } catch (Throwable $e) {
        //ignore plugin error
    } catch (Exception $e) {
        //ignore plugin error
    }
}

$result = ORM::for_table('tbl_appconfig')->find_many();
foreach ($result as $value) {
    $config[$value['setting']] = $value['value'];
}

if (empty($config['otp_wait']))  $config['otp_wait']  = 600;
if (empty($config['otp_expiry'])) $config['otp_expiry'] = 1200;

if(empty($config['dashboard_Admin'])){
    $config['dashboard_Admin'] = "12.7,5.12";
}

if(empty($config['dashboard_Agent'])){
    $config['dashboard_Agent'] = "12.7,5.12";
}

if(empty($config['dashboard_Sales'])){
    $config['dashboard_Sales'] = "12.7,5.12";
}

if(empty($config['dashboard_Customer'])){
    $config['dashboard_Customer'] = "6,6";
}


$_c =  $config;
if (empty($http_proxy) && !empty($config['http_proxy'])) {
    $http_proxy = $config['http_proxy'];
    if (empty($http_proxyauth) && !empty($config['http_proxyauth'])) {
        $http_proxyauth = $config['http_proxyauth'];
    }
}
date_default_timezone_set($config['timezone']);

if ((!empty($radius_user) && $config['radius_enable']) || _post('radius_enable')) {
    if (!empty($radius_password)) {
        // compability for old version
        $radius_pass = $radius_password;
    }
    ORM::configure("mysql:host=$radius_host;dbname=$radius_name", null, 'radius');
    ORM::configure('username', $radius_user, 'radius');
    ORM::configure('password', $radius_pass, 'radius');
    ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'), 'radius');
    ORM::configure('return_result_sets', true, 'radius');
}


// Check if the user has selected a language
if (!empty($_SESSION['user_language'])) {
    $config['language'] = $_SESSION['user_language'];
} else if (!empty($_COOKIE['user_language'])) {
    $config['language'] = $_COOKIE['user_language'];
} else if (User::getID() > 0) {
    $lang = User::getAttribute("Language");
    if (!empty($lang)) {
        $config['language'] = $lang;
    }
}

if (empty($config['language'])) {
    $config['language'] = 'english';
}
$lan_file = $root_path . File::pathFixer('system/lan/' . $config['language'] . '.json');
if (file_exists($lan_file)) {
    $_L = json_decode(file_get_contents($lan_file), true);
} else {
    $_L['author'] = 'Auto Generated by PHPNuxBill Script';
    file_put_contents($lan_file, json_encode($_L));
}

function safedata($value)
{
    $value = trim($value);
    return $value;
}

function _post($param, $defvalue = '')
{
    if (!isset($_POST[$param])) {
        return $defvalue;
    } else {
        return safedata($_POST[$param]);
    }
}

function _get($param, $defvalue = '')
{
    if (!isset($_GET[$param])) {
        return $defvalue;
    } else {
        return safedata($_GET[$param]);
    }
}

function _req($param, $defvalue = '')
{
    if (!isset($_REQUEST[$param])) {
        return $defvalue;
    } else {
        return safedata($_REQUEST[$param]);
    }
}


function _auth($login = true)
{
    if (User::getID()) {
        return true;
    } else {
        if ($login) {
            r2(getUrl('login'));
        } else {
            return false;
        }
    }
}

function _admin($login = true)
{
    if (Admin::getID()) {
        return true;
    } else {
        if ($login) {
            r2(getUrl('admin/'));
        } else {
            return false;
        }
    }
}

function _router_access_user_row($user)
{
    if ($user instanceof ORM) {
        return $user->as_array();
    }
    if (is_array($user)) {
        return $user;
    }
    return [];
}

function _router_access_parse_user_data($raw)
{
    if (is_array($raw)) {
        return $raw;
    }
    if (!is_string($raw)) {
        return [];
    }
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function _router_access_normalize_ids($value)
{
    if (is_string($value)) {
        $value = trim($value);
        if ($value === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $value = $decoded;
        } else {
            $value = preg_split('/[\s,]+/', $value);
        }
    }
    if (!is_array($value)) {
        return [];
    }
    $ids = [];
    foreach ($value as $item) {
        $id = (int) $item;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

function _router_access_all_router_rows($enabledOnly = false)
{
    static $cache = [];
    $key = $enabledOnly ? 'enabled' : 'all';
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $query = ORM::for_table('tbl_routers')->order_by_asc('name');
    if ($enabledOnly) {
        $query->where('enabled', '1');
    }
    $cache[$key] = $query->find_array();
    return $cache[$key];
}

function _router_access_router_maps($enabledOnly = false)
{
    static $cache = [];
    $key = $enabledOnly ? 'enabled' : 'all';
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $idToName = [];
    $nameToId = [];
    foreach (_router_access_all_router_rows($enabledOnly) as $row) {
        $id = (int) ($row['id'] ?? 0);
        $name = trim((string) ($row['name'] ?? ''));
        if ($id > 0 && $name !== '') {
            $idToName[$id] = $name;
            $nameToId[$name] = $id;
        }
    }
    $cache[$key] = [$idToName, $nameToId];
    return $cache[$key];
}

function _router_access_all_router_ids($enabledOnly = false)
{
    [$idToName, $nameToId] = _router_access_router_maps($enabledOnly);
    return array_keys($idToName);
}

function _router_access_user_by_id($id)
{
    static $cache = [];
    $id = (int) $id;
    if ($id < 1) {
        return null;
    }
    if (array_key_exists($id, $cache)) {
        return $cache[$id];
    }
    $row = ORM::for_table('tbl_users')->find_one($id);
    $cache[$id] = $row ? $row->as_array() : null;
    return $cache[$id];
}

function _router_access_assignment($user)
{
    $user = _router_access_user_row($user);
    $data = _router_access_parse_user_data($user['data'] ?? '');

    $mode = strtolower(trim((string) ($data['router_assignment_mode'] ?? ($data['router_access_mode'] ?? 'all'))));
    if (!in_array($mode, ['all', 'list'], true)) {
        $mode = 'all';
    }

    $ids = _router_access_normalize_ids($data['router_assignment_ids'] ?? ($data['router_access_ids'] ?? []));

    return [
        'mode' => $mode,
        'ids' => $ids,
    ];
}

function _router_access_parent_user($user)
{
    $user = _router_access_user_row($user);
    $root = (int) ($user['root'] ?? 0);
    $id = (int) ($user['id'] ?? 0);
    if ($root < 1 || ($id > 0 && $root === $id)) {
        return null;
    }
    return _router_access_user_by_id($root);
}

function _router_access_allowed_ids_for_user($user, $enabledOnly = true, $depth = 0, &$stack = [])
{
    $row = _router_access_user_row($user);
    if (empty($row)) {
        return [];
    }

    $uid = (int) ($row['id'] ?? 0);
    $role = trim((string) ($row['user_type'] ?? ''));
    $allIds = _router_access_all_router_ids($enabledOnly);

    if ($role === 'SuperAdmin') {
        return $allIds;
    }
    if ($depth > 8) {
        return [];
    }

    $memoSource = [
        'id' => $uid,
        'role' => $role,
        'root' => (int) ($row['root'] ?? 0),
        'assignment' => _router_access_assignment($row),
        'enabled' => $enabledOnly ? 1 : 0,
    ];
    $memoKey = md5(json_encode($memoSource));
    static $memo = [];
    if (isset($memo[$memoKey])) {
        return $memo[$memoKey];
    }

    if ($uid > 0) {
        if (isset($stack[$uid])) {
            return [];
        }
        $stack[$uid] = true;
    }

    $parent = _router_access_parent_user($row);
    if ($parent) {
        $parentIds = _router_access_allowed_ids_for_user($parent, $enabledOnly, $depth + 1, $stack);
    } else {
        // Hardened behavior: non-super users without valid parent have no router scope.
        $parentIds = [];
    }

    $assignment = _router_access_assignment($row);
    if ($assignment['mode'] === 'list') {
        $allowed = array_values(array_intersect($parentIds, $assignment['ids']));
    } else {
        $allowed = $parentIds;
    }

    if ($uid > 0) {
        unset($stack[$uid]);
    }

    $memo[$memoKey] = $allowed;
    return $allowed;
}

function _router_get_accessible_router_ids($admin = null, $enabledOnly = true)
{
    $user = _router_access_user_row($admin);
    if (empty($user)) {
        $current = Admin::_info();
        $user = _router_access_user_row($current);
    }
    if (empty($user)) {
        return [];
    }
    $stack = [];
    return _router_access_allowed_ids_for_user($user, $enabledOnly, 0, $stack);
}

function _router_get_accessible_router_names($admin = null, $enabledOnly = true)
{
    [$idToName, $nameToId] = _router_access_router_maps($enabledOnly);
    $ids = _router_get_accessible_router_ids($admin, $enabledOnly);
    $names = [];
    foreach ($ids as $id) {
        $id = (int) $id;
        if (isset($idToName[$id])) {
            $names[] = $idToName[$id];
        }
    }
    return $names;
}

function _router_get_accessible_routers($admin = null, $enabledOnly = true)
{
    $user = _router_access_user_row($admin);
    if (empty($user)) {
        $current = Admin::_info();
        $user = _router_access_user_row($current);
    }

    if (empty($user)) {
        return [];
    }

    $query = ORM::for_table('tbl_routers')->order_by_asc('name');
    if ($enabledOnly) {
        $query->where('enabled', '1');
    }

    if (($user['user_type'] ?? '') === 'SuperAdmin') {
        return $query->find_many();
    }

    $ids = _router_get_accessible_router_ids($user, $enabledOnly);
    if (empty($ids)) {
        return [];
    }
    return $query->where_in('id', $ids)->find_many();
}

function _router_can_access_router($router, $admin = null, $allowSpecial = [])
{
    $router = trim((string) $router);
    if ($router === '') {
        return false;
    }

    $allow = [];
    foreach ((array) $allowSpecial as $special) {
        $special = trim((string) $special);
        if ($special !== '') {
            $allow[$special] = true;
        }
    }
    if (isset($allow[$router])) {
        return true;
    }

    if (ctype_digit($router)) {
        $allowedIds = _router_get_accessible_router_ids($admin, false);
        return in_array((int) $router, $allowedIds, true);
    }

    $allowedNames = _router_get_accessible_router_names($admin, false);
    return in_array($router, $allowedNames, true);
}

function _router_filter_allowed_names($routers, $admin = null, $allowSpecial = [])
{
    $routers = is_array($routers) ? $routers : [];
    $result = [];
    foreach ($routers as $router) {
        $router = trim((string) $router);
        if ($router === '') {
            continue;
        }
        if (_router_can_access_router($router, $admin, $allowSpecial)) {
            $result[$router] = $router;
        }
    }
    return array_values($result);
}

function _customer_access_user_row($user)
{
    if ($user instanceof ORM) {
        return $user->as_array();
    }
    if (is_array($user)) {
        return $user;
    }
    return [];
}

function _customer_am_column_exists()
{
    static $checked = false;
    static $exists = false;
    if ($checked) {
        return $exists;
    }

    try {
        $db = ORM::get_db();
        if ($db) {
            $stmt = $db->query("SHOW COLUMNS FROM `tbl_customers` LIKE 'account_manager_id'");
            $exists = (bool) ($stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false);
        }
    } catch (Throwable $e) {
        $exists = false;
    }

    $checked = true;
    return $exists;
}

function _customer_am_allowed_roles()
{
    return ['SuperAdmin', 'Admin', 'Agent', 'Sales'];
}

function _customer_am_user_by_id($id)
{
    static $cache = [];
    $id = (int) $id;
    if ($id < 1) {
        return null;
    }
    if (array_key_exists($id, $cache)) {
        return $cache[$id];
    }

    $row = ORM::for_table('tbl_users')->find_one($id);
    $cache[$id] = $row ? $row->as_array() : null;
    return $cache[$id];
}

function _customer_am_rows()
{
    static $rows = null;
    if ($rows !== null) {
        return $rows;
    }

    $rows = ORM::for_table('tbl_users')
        ->select_many('id', 'username', 'fullname', 'user_type', 'status')
        ->where_in('user_type', _customer_am_allowed_roles())
        ->order_by_asc('user_type')
        ->order_by_asc('username')
        ->find_array();

    return $rows;
}

function _customer_am_label_map()
{
    static $labels = null;
    if ($labels !== null) {
        return $labels;
    }

    $labels = [];
    foreach (_customer_am_rows() as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id < 1) {
            continue;
        }
        $fullname = trim((string) ($row['fullname'] ?? ''));
        $username = trim((string) ($row['username'] ?? ''));
        $role = trim((string) ($row['user_type'] ?? ''));
        $label = $username;
        if ($fullname !== '') {
            $label = $fullname . ' (' . $username . ')';
        }
        if ($role !== '') {
            $label .= ' [' . $role . ']';
        }
        $labels[$id] = $label;
    }

    return $labels;
}

function _customer_am_resolve($rawId, &$error = '')
{
    $error = '';
    $id = (int) $rawId;
    if ($id < 1) {
        return 0;
    }
    if (!_customer_am_column_exists()) {
        return 0;
    }

    $row = _customer_am_user_by_id($id);
    if (empty($row) || !in_array((string) ($row['user_type'] ?? ''), _customer_am_allowed_roles(), true)) {
        $error = Lang::T('Invalid account manager');
        return 0;
    }

    return $id;
}

function _customer_am_id($customer)
{
    if (!_customer_am_column_exists()) {
        return 0;
    }
    $customer = _customer_access_user_row($customer);
    return max(0, (int) ($customer['account_manager_id'] ?? 0));
}

function _customer_can_edit_assignment($admin = null)
{
    $actor = _customer_access_user_row($admin);
    if (empty($actor)) {
        $actor = _customer_access_user_row(Admin::_info());
    }
    $role = trim((string) ($actor['user_type'] ?? ''));
    return in_array($role, ['SuperAdmin', 'Admin'], true);
}

function _customer_can_access($customer, $admin = null)
{
    if (!_customer_am_column_exists()) {
        return true;
    }

    $customerRow = _customer_access_user_row($customer);
    if (empty($customerRow)) {
        return false;
    }

    $actor = _customer_access_user_row($admin);
    if (empty($actor)) {
        $actor = _customer_access_user_row(Admin::_info());
    }
    if (empty($actor)) {
        return false;
    }

    $role = trim((string) ($actor['user_type'] ?? ''));
    if (in_array($role, ['SuperAdmin', 'Admin'], true)) {
        return true;
    }

    $amId = _customer_am_id($customerRow);
    if ($amId < 1) {
        return true;
    }

    return $amId === (int) ($actor['id'] ?? 0);
}

function _customer_apply_scope($query, $admin = null, $tableAlias = 'tbl_customers')
{
    if (!_customer_am_column_exists()) {
        return $query;
    }

    $actor = _customer_access_user_row($admin);
    if (empty($actor)) {
        $actor = _customer_access_user_row(Admin::_info());
    }
    if (empty($actor)) {
        return $query->where('id', -1);
    }

    $role = trim((string) ($actor['user_type'] ?? ''));
    if (in_array($role, ['SuperAdmin', 'Admin'], true)) {
        return $query;
    }

    $uid = (int) ($actor['id'] ?? 0);
    if ($uid < 1) {
        return $query->where('id', -1);
    }

    $tableAlias = trim((string) $tableAlias);
    $tableAlias = str_replace('`', '', $tableAlias);
    $column = ($tableAlias !== '') ? ('`' . $tableAlias . '`.`account_manager_id`') : '`account_manager_id`';
    $query->where_raw('(' . $column . ' = 0 OR ' . $column . ' = ?)', [$uid]);
    return $query;
}


function _log($description, $type = '', $userid = '0')
{
    $d = ORM::for_table('tbl_logs')->create();
    $d->date = date('Y-m-d H:i:s');
    $d->type = $type;
    $d->description = $description;
    $d->userid = $userid;
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']))   //to check ip is pass from cloudflare tunnel
    {
        $d->ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
        $d->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP']))   //to check ip from share internet
    {
        $d->ip = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER["REMOTE_ADDR"])) {
        $d->ip = $_SERVER["REMOTE_ADDR"];
    } else if (php_sapi_name() == 'cli') {
        $d->ip = 'CLI';
    } else {
        $d->ip = 'Unknown';
    }
    $d->save();
}

function Lang($key)
{
    return Lang::T($key);
}

function alphanumeric($str, $tambahan = "")
{
    return Text::alphanumeric($str, $tambahan);
}

function showResult($success, $message = '', $result = [], $meta = [])
{
    header("Content-Type: Application/json");
    $json = json_encode(['success' => $success, 'message' => $message, 'result' => $result, 'meta' => $meta]);
    echo $json;
    die();
}

/**
 * make url canonical or standar
 */
function getUrl($url)
{
    return Text::url($url);
}

function generateUniqueNumericVouchers($totalVouchers, $length = 8)
{
    // Define characters allowed in the voucher code
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $vouchers = array();

    // Attempt to generate unique voucher codes
    for ($j = 0; $j < $totalVouchers; $j++) {
        do {
            $voucherCode = '';
            // Generate the voucher code
            for ($i = 0; $i < $length; $i++) {
                $voucherCode .= $characters[rand(0, $charactersLength - 1)];
            }
            // Check if the generated voucher code already exists in the array
            $isUnique = !in_array($voucherCode, $vouchers);
        } while (!$isUnique);

        $vouchers[] = $voucherCode;
    }

    return $vouchers;
}

function sendTelegram($txt)
{
    Message::sendTelegram($txt);
}

function sendSMS($phone, $txt)
{
    Message::sendSMS($phone, $txt);
}

function sendWhatsapp($phone, $txt)
{
    Message::sendWhatsapp($phone, $txt);
}

function r2($to, $ntype = 'e', $msg = '')
{
    global $isApi;
    // Generate a fresh CSRF token for subsequent requests
    // so that each POST action will require a new token.
    Csrf::generateAndStoreToken();
    if ($isApi) {
        showResult(
            ($ntype == 's') ? true : false,
            $msg
        );
    }
    if ($msg == '') {
        header("location: $to");
        exit;
    }
    $_SESSION['ntype'] = $ntype;
    $_SESSION['notify'] = $msg;
    header("location: $to");
    exit;
}

function _alert($text, $type = 'success', $url = "home", $time = 3)
{
    global $ui, $isApi;
    if ($isApi) {
        showResult(
            ($type == 'success') ? true : false,
            $text
        );
    }
    if (!isset($ui)) return;
    if (strlen($url) > 4) {
        if (substr($url, 0, 4) != "http") {
            $url = getUrl($url);
        }
    } else {
        $url = getUrl($url);
    }
    $ui->assign('text', $text);
    $ui->assign('type', $type);
    $ui->assign('time', $time);
    $ui->assign('url', $url);
    $ui->display('admin/alert.tpl');
    die();
}


if (!isset($api_secret)) {
    $api_secret = $db_pass;
}

// Admin API keys are stored hashed (HMAC). Historically the hashing secret was tied to
// `$api_secret` which defaults to `$db_pass` (and can be empty), and some code paths
// used different fallbacks when `$api_secret` was empty. To avoid breaking admin API
// keys when DB password is empty/rotated, keep a dedicated secret for admin API key
// hashing with a stable fallback.
if (!isset($admin_api_key_secret)) {
    $admin_api_key_secret = $api_secret;
}
$admin_api_key_secret = trim((string) $admin_api_key_secret);
if ($admin_api_key_secret === '') {
    $admin_api_key_secret = __FILE__;
}
$GLOBALS['admin_api_key_secret'] = $admin_api_key_secret;

function displayMaintenanceMessage(): void
{
    global $config, $ui;
    $date = $config['maintenance_date'];
    if ($date) {
        $ui->assign('date', $date);
    }
    http_response_code(503);
    $ui->assign('companyName', $config['CompanyName']);
    $ui->display('admin/maintenance.tpl');
    die();
}

function isTableExist($table)
{
    try {
        $record = ORM::forTable($table)->find_one();
        return $record !== false;
    } catch (Exception $e) {
        return false;
    }
}
