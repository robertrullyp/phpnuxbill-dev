<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class Admin
{

    protected static function parseUserData($raw)
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

    protected static function hashApiKey($apiKey)
    {
        $apiKey = trim((string) $apiKey);
        if ($apiKey === '') {
            return '';
        }
        $secret = '';
        if (isset($GLOBALS['api_secret'])) {
            $secret = (string) $GLOBALS['api_secret'];
        }
        if ($secret === '') {
            $secret = __FILE__;
        }
        return hash_hmac('sha256', $apiKey, $secret);
    }

    protected static function getApiKeyFromRequest()
    {
        $candidates = [];

        if (!empty($_SERVER['HTTP_X_ADMIN_API_KEY'])) {
            $candidates[] = $_SERVER['HTTP_X_ADMIN_API_KEY'];
        }
        if (!empty($_SERVER['HTTP_X_API_KEY'])) {
            $candidates[] = $_SERVER['HTTP_X_API_KEY'];
        }
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $candidates[] = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $candidates[] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }
            if (stripos($candidate, 'bearer ') === 0) {
                $candidate = trim(substr($candidate, 7));
            }
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    protected static function getClientIp()
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

    protected static function parseIpAllowlist($raw)
    {
        if (!is_string($raw)) {
            return [];
        }
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $items = preg_split('/[\r\n,]+/', $raw);
        $list = [];
        foreach ($items as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            $list[$item] = true;
        }
        return $list;
    }

    protected static function ipInCidr($ip, $cidr)
    {
        $cidr = trim($cidr);
        if ($cidr === '') {
            return false;
        }
        if (strpos($cidr, '/') === false) {
            return $ip === $cidr;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }
        [$subnet, $mask] = explode('/', $cidr, 2);
        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }
        $mask = (int) $mask;
        if ($mask < 0 || $mask > 32) {
            return false;
        }
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        $maskLong = $maskLong & 0xFFFFFFFF;
        return (($ipLong & $maskLong) === ($subnetLong & $maskLong));
    }

    protected static function isIpAllowlisted($ip)
    {
        $config = $GLOBALS['config'] ?? [];
        $raw = $config['admin_api_key_allowlist'] ?? '';
        $list = self::parseIpAllowlist($raw);
        if (empty($list)) {
            return false;
        }
        foreach ($list as $entry => $enabled) {
            if (self::ipInCidr($ip, $entry)) {
                return true;
            }
        }
        return false;
    }

    protected static function apiKeyGuardConfig()
    {
        $config = $GLOBALS['config'] ?? [];
        $enabled = ($config['admin_api_key_backoff_enabled'] ?? 'yes') !== 'no';
        if (!$enabled) {
            return ['enabled' => false];
        }

        $attempts_max = (int) ($config['admin_api_key_attempts_max'] ?? 5);
        if ($attempts_max < 1) {
            $attempts_max = 1;
        }
        $attempts_window = (int) ($config['admin_api_key_attempts_window'] ?? 300);
        if ($attempts_window < 60) {
            $attempts_window = 60;
        }

        $base_delay = (int) ($config['admin_api_key_backoff_base_delay'] ?? 5);
        $max_delay = (int) ($config['admin_api_key_backoff_max_delay'] ?? 3600);
        $reset_window = (int) ($config['admin_api_key_backoff_reset_window'] ?? 900);

        if ($base_delay < 1) {
            $base_delay = 1;
        }
        if ($max_delay < $base_delay) {
            $max_delay = $base_delay;
        }
        if ($reset_window < 60) {
            $reset_window = 60;
        }

        return [
            'enabled' => true,
            'attempts_max' => $attempts_max,
            'attempts_window' => $attempts_window,
            'base_delay' => $base_delay,
            'max_delay' => $max_delay,
            'reset_window' => $reset_window,
        ];
    }

    protected static function apiKeyGuardPath($identity)
    {
        $cacheBase = $GLOBALS['CACHE_PATH'] ?? null;
        if (!$cacheBase) {
            return '';
        }
        $dir = rtrim($cacheBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'admin_api_key_backoff';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir)) {
            return '';
        }
        return $dir . DIRECTORY_SEPARATOR . hash('sha256', $identity) . '.json';
    }

    protected static function apiKeyGuardLoad($identity, $ip)
    {
        $path = self::apiKeyGuardPath($identity);
        $state = [
            'ip' => $ip,
            'fail_count' => 0,
            'fail_window_start' => 0,
            'backoff_attempts' => 0,
            'blocked_until' => 0,
            'last_at' => 0,
        ];
        if ($path === '' || !is_file($path)) {
            return [$state, $path];
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return [$state, $path];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $state['ip'] = is_string($decoded['ip'] ?? '') ? $decoded['ip'] : $ip;
            $state['fail_count'] = (int) ($decoded['fail_count'] ?? 0);
            $state['fail_window_start'] = (int) ($decoded['fail_window_start'] ?? 0);
            $state['backoff_attempts'] = (int) ($decoded['backoff_attempts'] ?? 0);
            $state['blocked_until'] = (int) ($decoded['blocked_until'] ?? 0);
            $state['last_at'] = (int) ($decoded['last_at'] ?? 0);
            if (empty($state['blocked_until']) && isset($decoded['next_allowed_at'])) {
                $state['blocked_until'] = (int) $decoded['next_allowed_at'];
            }
            if (empty($state['backoff_attempts']) && isset($decoded['attempts'])) {
                $state['backoff_attempts'] = (int) $decoded['attempts'];
            }
        }
        return [$state, $path];
    }

    protected static function apiKeyGuardSave($path, array $state)
    {
        if ($path === '') {
            return;
        }
        @file_put_contents($path, json_encode($state), LOCK_EX);
    }

    protected static function apiKeyGuardClear($identity)
    {
        $path = self::apiKeyGuardPath($identity);
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
    }

    protected static function apiKeyGuardCheck($ip)
    {
        if (self::isIpAllowlisted($ip)) {
            self::apiKeyGuardClear('ip:' . $ip);
            return ['allowed' => true, 'skip' => true];
        }

        $cfg = self::apiKeyGuardConfig();
        if (empty($cfg['enabled'])) {
            return ['allowed' => true, 'skip' => true];
        }

        $identity = 'ip:' . $ip;
        [$state, $path] = self::apiKeyGuardLoad($identity, $ip);
        $now = time();
        if ($state['ip'] === '' && $path !== '') {
            $state['ip'] = $ip;
            self::apiKeyGuardSave($path, $state);
        }

        if ($state['blocked_until'] > $now) {
            return [
                'allowed' => false,
                'retry_after' => $state['blocked_until'] - $now,
                'state' => $state,
                'path' => $path,
                'config' => $cfg,
                'identity' => $identity,
            ];
        }

        return [
            'allowed' => true,
            'state' => $state,
            'path' => $path,
            'config' => $cfg,
            'identity' => $identity,
        ];
    }

    protected static function apiKeyGuardRegisterFailure($ip, array $check)
    {
        if (!empty($check['skip']) || empty($check['config']) || empty($check['path'])) {
            return ['blocked' => false];
        }

        $cfg = $check['config'];
        $state = $check['state'];
        $path = $check['path'];
        $now = time();

        if ($state['fail_window_start'] === 0 || ($now - $state['fail_window_start']) > $cfg['attempts_window']) {
            $state['fail_window_start'] = $now;
            $state['fail_count'] = 0;
        }

        $state['fail_count']++;
        $state['ip'] = $ip;

        if ($state['fail_count'] < $cfg['attempts_max']) {
            $state['last_at'] = $now;
            self::apiKeyGuardSave($path, $state);
            return [
                'blocked' => false,
                'state' => $state,
            ];
        }

        $last_at = (int) ($state['last_at'] ?? 0);
        if ($last_at === 0 || ($now - $last_at) > $cfg['reset_window']) {
            $state['backoff_attempts'] = 0;
        }

        $state['backoff_attempts']++;
        $delay = $cfg['base_delay'] * (2 ** max(0, $state['backoff_attempts'] - 1));
        if ($delay > $cfg['max_delay']) {
            $delay = $cfg['max_delay'];
        }

        $state['blocked_until'] = $now + $delay;
        $state['last_at'] = $now;
        self::apiKeyGuardSave($path, $state);

        return [
            'blocked' => true,
            'retry_after' => $delay,
            'state' => $state,
        ];
    }

    public static function clearApiKeyBlock($ip)
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return false;
        }
        $identity = 'ip:' . $ip;
        self::apiKeyGuardClear($identity);
        return true;
    }

    protected static function getAdminIdByApiKey($apiKey)
    {
        $apiKey = trim((string) $apiKey);
        if ($apiKey === '') {
            return 0;
        }

        $hashedKey = self::hashApiKey($apiKey);
        if ($hashedKey === '') {
            return 0;
        }

        static $cache = null;
        if ($cache === null) {
            $cache = [];
            $admins = ORM::for_table('tbl_users')->select_many('id', 'data')->find_many();
            foreach ($admins as $admin) {
                $data = self::parseUserData($admin->data ?? '');
                $hash = $data['admin_api_key_hash'] ?? '';
                if (!is_string($hash) || trim($hash) === '') {
                    $hash = $data['ai_chatbot_api_key_hash'] ?? '';
                }
                $hash = is_string($hash) ? trim($hash) : '';
                if ($hash !== '' && !isset($cache[$hash])) {
                    $cache[$hash] = (int) $admin->id;
                    continue;
                }

                $legacy = $data['admin_api_key'] ?? '';
                if (!is_string($legacy) || trim($legacy) === '') {
                    $legacy = $data['ai_chatbot_api_key'] ?? '';
                }
                $legacy = is_string($legacy) ? trim($legacy) : '';
                if ($legacy !== '') {
                    $legacyHash = self::hashApiKey($legacy);
                    if ($legacyHash !== '' && !isset($cache[$legacyHash])) {
                        $cache[$legacyHash] = (int) $admin->id;
                    }
                }
            }
        }

        return $cache[$hashedKey] ?? 0;
    }

    public static function getID()
    {
        global $db_pass, $config, $isApi;

        $enable_session_timeout = $config['enable_session_timeout'] == 1;
        $session_timeout_duration = $config['session_timeout_duration'] ? intval($config['session_timeout_duration'] * 60) : intval(60 * 60); // Convert minutes to seconds
        if ($isApi) {
            $enable_session_timeout = false;
        }
        $isApiKeySession = !empty($_SESSION['aid_api_key']);
        if ($enable_session_timeout && !empty($_SESSION['aid']) && !empty($_SESSION['aid_expiration'])) {
            if ($_SESSION['aid_expiration'] > time()) {
                if (!$isApiKeySession) {
                    $isValid = self::validateToken($_SESSION['aid'], $_COOKIE['aid'] ?? '');
                    if (!$isValid) {
                        self::removeCookie();
                        _alert(Lang::T('Token has expired. Please log in again.'), 'danger', "admin");
                        return 0;
                    }
                }
                // extend timeout duration
                $_SESSION['aid_expiration'] = time() + $session_timeout_duration;

                return $_SESSION['aid'];
            } else {
                // Session expired, log out the user
                self::removeCookie();
                _alert(Lang::T('Session has expired. Please log in again.'), 'danger', "admin");
                return 0;
            }
        } else if (!empty($_SESSION['aid'])) {
            if (!empty($isApi)) {
                return $_SESSION['aid'];
            }
            if (!$isApiKeySession) {
                $isValid = self::validateToken($_SESSION['aid'], $_COOKIE['aid'] ?? '');
                if (!$isValid) {
                    self::removeCookie();
                    _alert(Lang::T('Token has expired. Please log in again.') . '.' . $_SESSION['aid'], 'danger', "admin");
                    return 0;
                }
            }
            return $_SESSION['aid'];
        }
        // Check if the cookie is set and valid
        elseif (isset($_COOKIE['aid'])) {
            $tmp = explode('.', $_COOKIE['aid']);
            if (sha1("$tmp[0].$tmp[1].$db_pass") == $tmp[2]) {
                // Validate the token in the cookie
                $isValid = self::validateToken($tmp[0], $_COOKIE['aid']);
                if ($isApi) {
                    // For now API need to always return true, next need to add revoke token API
                    $isValid = true;
                }
                if (!empty($_COOKIE['aid']) && !$isValid) {
                    self::removeCookie();
                    _alert(Lang::T('Token has expired. Please log in again.') . '..', 'danger', "admin");
                    return 0;
                } else {
                    if (time() - $tmp[1] < 86400 * 7) {
                        $_SESSION['aid'] = $tmp[0];
                        if ($enable_session_timeout) {
                            $_SESSION['aid_expiration'] = time() + $session_timeout_duration;
                        }
                        return $tmp[0];
                    }
                }
            }
        }

        $apiKey = self::getApiKeyFromRequest();
        if ($apiKey !== '') {
            $isApi = $GLOBALS['isApi'] ?? false;
            $ip = self::getClientIp();
            $guard = self::apiKeyGuardCheck($ip);
            if ($isApi && empty($guard['allowed'])) {
                $wait = (int) ($guard['retry_after'] ?? 0);
                if ($wait < 1) {
                    $wait = 1;
                }
                _log('API key blocked for IP ' . $ip . ' (retry after ' . $wait . 's)', 'Security');
                $GLOBALS['api_key_backoff'] = ['retry_after' => $wait];
                return 0;
            }
            $apiAdminId = self::getAdminIdByApiKey($apiKey);
            if ($apiAdminId > 0) {
                if ($isApi && !empty($guard['identity'])) {
                    self::apiKeyGuardClear($guard['identity']);
                }
                $_SESSION['aid'] = $apiAdminId;
                $_SESSION['aid_api_key'] = true;
                if ($enable_session_timeout) {
                    $_SESSION['aid_expiration'] = time() + $session_timeout_duration;
                }
                return $apiAdminId;
            }
            if ($isApi) {
                $result = self::apiKeyGuardRegisterFailure($ip, $guard);
                if (!empty($result['blocked'])) {
                    $wait = (int) ($result['retry_after'] ?? 0);
                    if ($wait < 1) {
                        $wait = 1;
                    }
                    _log('API key blocked for IP ' . $ip . ' (retry after ' . $wait . 's)', 'Security');
                    $GLOBALS['api_key_backoff'] = ['retry_after' => $wait];
                }
            }
        }

        return 0;
    }

    public static function setCookie($aid)
    {
        global $db_pass, $config;
        $enable_session_timeout = $config['enable_session_timeout'];
        $session_timeout_duration = intval($config['session_timeout_duration']) * 60; // Convert minutes to seconds

        if (isset($aid)) {
            $time = time();
            $token = $aid . '.' . $time . '.' . sha1("$aid.$time.$db_pass");

            // Detect the current protocol
            $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            // Set cookie with security flags
            setcookie('aid', $token, [
                'expires' => time() + 86400 * 7, // 7 days
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax', // or Strict
            ]);

            $_SESSION['aid'] = $aid;
            unset($_SESSION['aid_api_key']);

            if ($enable_session_timeout) {
                $_SESSION['aid_expiration'] = $time + $session_timeout_duration;
            }

            self::upsertToken($aid, $token);

            return $token;
        }

        return '';
    }

    public static function removeCookie()
    {
        global $_app_stage;
        if (isset($_COOKIE['aid'])) {
            $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie('aid', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_destroy();
            session_unset();
            session_start();
            unset($_COOKIE['aid'], $_SESSION['aid']);
        }
    }

    public static function _info($id = 0)
    {
        if (empty($id) && $id == 0) {
            $id = Admin::getID();
        }
        if ($id) {
            return ORM::for_table('tbl_users')->find_one($id);
        } else {
            return null;
        }
    }

    public static function upsertToken($aid, $token)
    {
        $query = ORM::for_table('tbl_users')->findOne($aid);
        $query->login_token = sha1($token);
        $query->save();
    }

    public static function validateToken($aid, $cookieToken)
    {
        global $config;
        $query =  ORM::for_table('tbl_users')->select('login_token')->findOne($aid);
        if ($config['single_session'] != 'yes') {
            return true; // For multi-session, any token is valid
        }
        if (empty($query)) {
            return true;
        }
        return $query->login_token === sha1($cookieToken);
    }
}
