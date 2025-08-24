<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class Csrf
{
    private static $tokenExpiration = 1800; // 30 minutes

    public static function generateToken($length = 16)
    {
        return bin2hex(random_bytes($length));
    }

    public static function validateToken($token, $storedToken)
    {
        return hash_equals($token, $storedToken);
    }

    public static function check($token)
    {
        global $config, $isApi;
        if ($config['csrf_enabled'] == 'yes' && !$isApi) {
            if (!empty($_SESSION['csrf_tokens']) && isset($token)) {
                foreach ($_SESSION['csrf_tokens'] as $index => $data) {
                    if (self::validateToken($token, $data['token'])) {
                        if (time() - $data['time'] > self::$tokenExpiration) {
                            self::clearToken($token);
                            return false;
                        }
                        // Token is valid and within the allowed time window; keep it
                        return true;
                    }
                    if (time() - $data['time'] > self::$tokenExpiration) {
                        unset($_SESSION['csrf_tokens'][$index]);
                    }
                }
            }
            return false;
        }
        return true;
    }

    public static function generateAndStoreToken()
    {
        $token = self::generateToken();
        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        $_SESSION['csrf_tokens'][] = ['token' => $token, 'time' => time()];
        return $token;
    }

    public static function clearToken($token = null)
    {
        // When no token is provided, remove all stored tokens (e.g., during logout)
        if ($token === null) {
            unset($_SESSION['csrf_tokens']);
            return;
        }

        if (!empty($_SESSION['csrf_tokens'])) {
            foreach ($_SESSION['csrf_tokens'] as $index => $data) {
                if (self::validateToken($token, $data['token'])) {
                    unset($_SESSION['csrf_tokens'][$index]);
                    break;
                }
            }

            if (empty($_SESSION['csrf_tokens'])) {
                unset($_SESSION['csrf_tokens']);
            }
        }
    }
}
