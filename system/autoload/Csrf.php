<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class Csrf
{
    private static $tokenExpiration = 3600; // 1 hour
    private static $maxTokens = 50; // limit stored CSRF tokens

    public static function generateToken($length = 16)
    {
        return bin2hex(random_bytes($length));
    }

    public static function validateToken($token, $storedToken)
    {
        return hash_equals($storedToken, $token);
    }

    /**
     * Retrieve the CSRF token from common transport locations without
     * applying additional sanitisation that could alter the raw token value.
     */
    public static function getTokenFromRequest()
    {
        $candidates = [];

        if (isset($_POST['csrf_token'])) {
            $candidates[] = $_POST['csrf_token'];
        }

        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $candidates[] = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if (isset($_SERVER['HTTP_X_XSRF_TOKEN'])) {
            $candidates[] = $_SERVER['HTTP_X_XSRF_TOKEN'];
        }

        if (isset($_GET['csrf_token'])) {
            $candidates[] = $_GET['csrf_token'];
        }

        foreach ($candidates as $candidate) {
            if (is_array($candidate)) {
                $candidate = reset($candidate);
            }

            if ($candidate !== null) {
                $candidate = trim((string) $candidate);
            }

            if (!empty($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    public static function check($token = null)
    {
        global $config, $isApi;
        $enabled = $config['csrf_enabled'] ?? 'yes';
        if ($enabled === 'yes' && !$isApi) {
            if ($token === null) {
                $token = self::getTokenFromRequest();
            }
            if (!empty($_SESSION['csrf_tokens']) && !empty($token)) {
                foreach ($_SESSION['csrf_tokens'] as $index => $data) {
                    if (self::validateToken($token, $data['token'])) {
                        if (time() - $data['time'] > self::$tokenExpiration) {
                            self::clearToken($token);
                            return false;
                        }
                        // Token is valid and within the allowed time window; clear and return
                        self::clearToken($token);
                        return true;
                    }
                    if (time() - $data['time'] > self::$tokenExpiration) {
                        unset($_SESSION['csrf_tokens'][$index]);
                    }
                }
                return false;
            } else {
                if (empty($token)) {
                    error_log('CSRF token not provided.');
                }
                return false;
            }
        }
        return true;
    }

    public static function generateAndStoreToken()
    {
        global $config;
        $enabled = $config['csrf_enabled'] ?? 'yes';
        if ($enabled === 'yes') {
            $token = self::generateToken();
            if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
                $_SESSION['csrf_tokens'] = [];
            }
            $_SESSION['csrf_tokens'][] = ['token' => $token, 'time' => time()];
            self::pruneTokens();
            return $token;
        }
        return '';
    }

    private static function pruneTokens()
    {
        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            return;
        }

        $now = time();
        // Remove expired tokens first
        $_SESSION['csrf_tokens'] = array_values(array_filter(
            $_SESSION['csrf_tokens'],
            function ($data) use ($now) {
                return ($now - $data['time']) <= self::$tokenExpiration;
            }
        ));

        // Trim the array to the most recent tokens if exceeding the limit
        if (count($_SESSION['csrf_tokens']) > self::$maxTokens) {
            $_SESSION['csrf_tokens'] = array_slice($_SESSION['csrf_tokens'], -self::$maxTokens);
        }
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
