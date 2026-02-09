<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

class GenieACS
{
    public const SETTING_ENABLE = 'genieacs_enable';
    public const SETTING_URL = 'genieacs_url';

    public const ATTR_DEVICE_ID = 'GenieACS Device ID';
    public const ATTR_DEVICE_LABEL = 'GenieACS Device Label';
    public const ATTR_WIFI_SSID = 'GenieACS WiFi SSID';
    public const ATTR_WIFI_PASSWORD = 'GenieACS WiFi Password';

    private static $ssidPaths = [
        'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
        'Device.WiFi.SSID.1.SSID',
    ];

    private static $passwordPaths = [
        'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase',
        'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase',
        'Device.WiFi.AccessPoint.1.Security.KeyPassphrase',
        'Device.WiFi.AccessPoint.1.Security.PreSharedKey',
    ];

    private static $pppoeUsernamePaths = [
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.2.Username',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.3.WANPPPConnection.1.Username',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.4.WANPPPConnection.1.Username',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.5.WANPPPConnection.1.Username',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.6.WANPPPConnection.1.Username',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.7.WANPPPConnection.1.Username',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.8.WANPPPConnection.1.Username',
        'Device.PPP.Interface.1.Username',
        'Device.PPP.Interface.2.Username',
        'Device.WAN.PPPConnection.1.Username',
        'Device.WAN.PPPConnection.2.Username',
    ];

    private static $pppoePasswordPaths = [
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Password',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.2.Password',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Password',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.3.WANPPPConnection.1.Password',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.4.WANPPPConnection.1.Password',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.5.WANPPPConnection.1.Password',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.6.WANPPPConnection.1.Password',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.7.WANPPPConnection.1.Password',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.8.WANPPPConnection.1.Password',
        'Device.PPP.Interface.1.Password',
        'Device.PPP.Interface.2.Password',
        'Device.WAN.PPPConnection.1.Password',
        'Device.WAN.PPPConnection.2.Password',
    ];

    private static $pppoeIpPaths = [
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.4.WANIPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANIPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.4.WANPPPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.3.WANPPPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.5.WANPPPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.6.WANPPPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.7.WANPPPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.8.WANPPPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.2.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.2.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANIPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.3.WANIPConnection.1.ExternalIPAddress',
        'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.4.WANIPConnection.1.ExternalIPAddress',
        'Device.PPP.Interface.1.IPCP.LocalIPAddress',
        'Device.PPP.Interface.1.IPCP.RemoteIPAddress',
        'Device.PPP.Interface.2.IPCP.LocalIPAddress',
        'Device.PPP.Interface.2.IPCP.RemoteIPAddress',
        'Device.WAN.PPPConnection.1.ExternalIPAddress',
        'Device.WAN.PPPConnection.2.ExternalIPAddress',
        'Device.IP.Interface.1.IPv4Address.1.IPAddress',
        'Device.IP.Interface.2.IPv4Address.1.IPAddress',
    ];

    public static function isEnabled($config)
    {
        return isset($config[self::SETTING_ENABLE]) && $config[self::SETTING_ENABLE] === 'yes';
    }

    public static function getBaseUrl($config)
    {
        $url = trim((string) ($config[self::SETTING_URL] ?? ''));
        if ($url === '') {
            return '';
        }
        return rtrim($url, '/');
    }

    public static function isConfigured($config)
    {
        return self::isEnabled($config) && self::getBaseUrl($config) !== '';
    }

    public static function getAssignedDeviceId($customerId)
    {
        return trim((string) User::getAttribute(self::ATTR_DEVICE_ID, (int) $customerId, ''));
    }

    public static function getAssignedDeviceLabel($customerId, $default = '')
    {
        return trim((string) User::getAttribute(self::ATTR_DEVICE_LABEL, (int) $customerId, $default));
    }

    public static function setAssignedDevice($customerId, $deviceId, $label = '')
    {
        $customerId = (int) $customerId;
        $deviceId = trim((string) $deviceId);
        $label = trim((string) $label);
        if ($customerId < 1) {
            return false;
        }
        if ($deviceId === '') {
            self::clearAssignedDevice($customerId);
            return true;
        }
        if ($label === '') {
            $label = $deviceId;
        }
        User::setAttribute(self::ATTR_DEVICE_ID, $deviceId, $customerId);
        User::setAttribute(self::ATTR_DEVICE_LABEL, $label, $customerId);
        return true;
    }

    public static function clearAssignedDevice($customerId)
    {
        $customerId = (int) $customerId;
        if ($customerId < 1) {
            return false;
        }
        ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $customerId)
            ->where_in('field_name', [self::ATTR_DEVICE_ID, self::ATTR_DEVICE_LABEL])
            ->delete_many();
        return true;
    }

    public static function saveWifiCache($customerId, $ssid, $password)
    {
        $customerId = (int) $customerId;
        if ($customerId < 1) {
            return false;
        }
        User::setAttribute(self::ATTR_WIFI_SSID, trim((string) $ssid), $customerId);
        User::setAttribute(self::ATTR_WIFI_PASSWORD, trim((string) $password), $customerId);
        return true;
    }

    public static function getWifiCache($customerId)
    {
        $customerId = (int) $customerId;
        if ($customerId < 1) {
            return [
                'ssid' => '',
                'password' => '',
            ];
        }
        return [
            'ssid' => trim((string) User::getAttribute(self::ATTR_WIFI_SSID, $customerId, '')),
            'password' => trim((string) User::getAttribute(self::ATTR_WIFI_PASSWORD, $customerId, '')),
        ];
    }

    public static function isEligiblePppoeBill($bill)
    {
        if (!is_array($bill)) {
            return false;
        }

        if (strtoupper((string) ($bill['type'] ?? '')) !== 'PPPOE') {
            return false;
        }

        if (strtolower((string) ($bill['status'] ?? '')) !== 'on') {
            return false;
        }

        $expirationDate = trim((string) ($bill['expiration'] ?? ''));
        if ($expirationDate === '') {
            return true;
        }

        $expirationTime = trim((string) ($bill['time'] ?? ''));
        if ($expirationTime === '') {
            $expirationTime = '23:59:59';
        }
        $expiresAt = strtotime($expirationDate . ' ' . $expirationTime);
        if ($expiresAt === false) {
            return true;
        }

        return $expiresAt >= time();
    }

    public static function hasEligiblePppoeBill($bills)
    {
        if (empty($bills)) {
            return false;
        }
        foreach ($bills as $bill) {
            if ($bill instanceof ORM) {
                $bill = $bill->as_array();
            }
            if (self::isEligiblePppoeBill($bill)) {
                return true;
            }
        }
        return false;
    }

    public static function discoverDevices($config, $search = '', $limit = 40)
    {
        $limit = (int) $limit;
        if ($limit > 0 && $limit > 5000) {
            $limit = 5000;
        }

        if (!self::isConfigured($config)) {
            return [
                'success' => false,
                'devices' => [],
                'error' => 'GenieACS integration is disabled or URL is empty.',
            ];
        }

        $baseUrl = self::getBaseUrl($config);
        $projectionFields = array_merge([
            '_id',
            '_deviceId',
            'DeviceID.SerialNumber',
            'DeviceID.ProductClass',
            'DeviceID.Manufacturer',
            'InternetGatewayDevice.DeviceInfo.SerialNumber',
            'InternetGatewayDevice.DeviceInfo.ModelName',
            'InternetGatewayDevice.DeviceInfo.Manufacturer',
            'Device.DeviceInfo.SerialNumber',
            'Device.DeviceInfo.ModelName',
            'Device.DeviceInfo.Manufacturer',
        ], self::getPppoeUsernamePaths(), self::getPppoeIpPaths());
        $projection = implode(',', array_values(array_unique($projectionFields)));
        $batchLimit = 200;
        $maxPages = 50;
        $skip = 0;
        $fetchedRows = [];
        $uniqueRows = [];
        $page = 0;
        $lastResponse = null;

        while ($page < $maxPages) {
            $page++;
            if ($limit > 0) {
                $remaining = $limit - count($uniqueRows);
                if ($remaining <= 0) {
                    break;
                }
                $requestLimit = min($batchLimit, $remaining);
            } else {
                $requestLimit = $batchLimit;
            }

            $url = $baseUrl
                . '/devices?limit=' . $requestLimit
                . '&skip=' . $skip
                . '&projection=' . rawurlencode($projection);
            $response = self::request('GET', $url);
            $lastResponse = $response;
            if (!$response['ok']) {
                return [
                    'success' => false,
                    'devices' => [],
                    'error' => self::buildErrorMessage($response, 'Failed to fetch GenieACS devices.'),
                ];
            }

            $rows = $response['json'];
            if (!is_array($rows)) {
                $rows = [];
            }
            if (!empty($rows) && self::isAssoc($rows)) {
                $rows = [$rows];
            }

            $fetchedRows = $rows;
            $addedInThisPage = 0;
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $deviceId = trim((string) ($row['_id'] ?? ''));
                if ($deviceId === '') {
                    continue;
                }
                if (!array_key_exists($deviceId, $uniqueRows)) {
                    $uniqueRows[$deviceId] = $row;
                    $addedInThisPage++;
                }
            }

            $batchCount = count($rows);
            if ($batchCount < $requestLimit) {
                break;
            }
            if ($addedInThisPage === 0) {
                break;
            }
            $skip += $batchCount;
        }

        if ($limit > 0) {
            $fetchedRows = array_slice(array_values($uniqueRows), 0, $limit);
        } else {
            $fetchedRows = array_values($uniqueRows);
        }

        if (empty($fetchedRows) && $lastResponse !== null && !empty($lastResponse['json'])) {
            $fetchedRows = is_array($lastResponse['json']) ? $lastResponse['json'] : [];
        }

        $search = strtolower(trim((string) $search));
        $items = [];
        foreach ($fetchedRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $deviceId = trim((string) ($row['_id'] ?? ''));
            if ($deviceId === '') {
                continue;
            }
            $label = self::buildDeviceLabel($row);
            if ($search !== '') {
                $haystack = strtolower($label . ' ' . $deviceId);
                if (strpos($haystack, $search) === false) {
                    continue;
                }
            }
            $items[] = [
                'id' => $deviceId,
                'text' => $label,
            ];
        }

        usort($items, function ($a, $b) {
            return strcasecmp($a['text'], $b['text']);
        });

        return [
            'success' => true,
            'devices' => $items,
            'error' => '',
        ];
    }

    public static function fetchDevice($config, $deviceId)
    {
        $deviceId = trim((string) $deviceId);
        if ($deviceId === '') {
            return [
                'success' => false,
                'device' => [],
                'error' => 'Device ID is empty.',
            ];
        }
        if (!self::isConfigured($config)) {
            return [
                'success' => false,
                'device' => [],
                'error' => 'GenieACS integration is disabled or URL is empty.',
            ];
        }

        $baseUrl = self::getBaseUrl($config);
        $query = rawurlencode(json_encode(['_id' => $deviceId]));
        $queryUrl = $baseUrl . '/devices?query=' . $query . '&limit=1';
        $queryResponse = self::request('GET', $queryUrl);
        if ($queryResponse['ok'] && is_array($queryResponse['json'])) {
            $rows = $queryResponse['json'];
            if (!empty($rows) && self::isAssoc($rows)) {
                $rows = [$rows];
            }
            if (!empty($rows) && is_array($rows[0])) {
                return [
                    'success' => true,
                    'device' => $rows[0],
                    'error' => '',
                ];
            }
        }

        // Backward-compatible fallback for deployments that still expose GET /devices/{id}.
        $legacyUrl = $baseUrl . '/devices/' . rawurlencode($deviceId);
        $legacyResponse = self::request('GET', $legacyUrl);
        if ($legacyResponse['ok'] && is_array($legacyResponse['json'])) {
            return [
                'success' => true,
                'device' => $legacyResponse['json'],
                'error' => '',
            ];
        }

        $errorSource = $queryResponse;
        if (empty($errorSource['ok']) && (int) ($queryResponse['status'] ?? 0) === 405 && !empty($legacyResponse['ok'])) {
            $errorSource = $legacyResponse;
        } elseif (!empty($legacyResponse['ok']) || (int) ($legacyResponse['status'] ?? 0) > 0) {
            $errorSource = $legacyResponse;
        }

        return [
            'success' => false,
            'device' => [],
            'error' => self::buildErrorMessage($errorSource, 'Failed to fetch GenieACS device detail.'),
        ];
    }

    public static function readWifiCredentials($config, $deviceId)
    {
        $deviceResult = self::fetchDevice($config, $deviceId);
        if (!$deviceResult['success']) {
            return [
                'success' => false,
                'ssid' => '',
                'password' => '',
                'ssid_path' => '',
                'password_path' => '',
                'device_label' => trim((string) $deviceId),
                'error' => $deviceResult['error'],
            ];
        }

        $device = $deviceResult['device'];
        $ssidPath = self::detectPath($device, self::$ssidPaths);
        $passwordPath = self::detectPath($device, self::$passwordPaths);
        $ssid = ($ssidPath !== '') ? self::getScalarPathValue($device, $ssidPath) : '';
        $password = ($passwordPath !== '') ? self::getScalarPathValue($device, $passwordPath) : '';

        $error = '';
        if ($ssidPath === '' && $passwordPath === '') {
            $error = 'WiFi parameter path not found on this device.';
        }

        return [
            'success' => ($error === ''),
            'ssid' => $ssid,
            'password' => $password,
            'ssid_path' => $ssidPath,
            'password_path' => $passwordPath,
            'device_label' => self::buildDeviceLabel($device),
            'error' => $error,
        ];
    }

    public static function updateWifiCredentials($config, $deviceId, $ssid, $password, $ssidPath = '', $passwordPath = '')
    {
        $deviceId = trim((string) $deviceId);
        $ssid = trim((string) $ssid);
        $password = trim((string) $password);
        $ssidPath = trim((string) $ssidPath);
        $passwordPath = trim((string) $passwordPath);

        if ($deviceId === '') {
            return [
                'success' => false,
                'error' => 'Device ID is empty.',
            ];
        }
        if (!self::isConfigured($config)) {
            return [
                'success' => false,
                'error' => 'GenieACS integration is disabled or URL is empty.',
            ];
        }

        if ($ssidPath === '' || $passwordPath === '') {
            $read = self::readWifiCredentials($config, $deviceId);
            if ($ssidPath === '') {
                $ssidPath = $read['ssid_path'] ?: self::$ssidPaths[0];
            }
            if ($passwordPath === '') {
                $passwordPath = $read['password_path'] ?: self::$passwordPaths[0];
            }
        }

        $device = [];
        $deviceResult = self::fetchDevice($config, $deviceId);
        if (!empty($deviceResult['success']) && is_array($deviceResult['device'])) {
            $device = $deviceResult['device'];
        }

        $parameterValues = self::buildWifiParameterValues($device, $ssidPath, $ssid, $passwordPath, $password);
        if (empty($parameterValues)) {
            $parameterValues = [
                [$ssidPath, $ssid, 'xsd:string'],
                [$passwordPath, $password, 'xsd:string'],
            ];
        }

        $payload = [
            'name' => 'setParameterValues',
            'parameterValues' => $parameterValues,
        ];

        $baseUrl = self::getBaseUrl($config);
        $url = $baseUrl . '/devices/' . rawurlencode($deviceId) . '/tasks?connection_request';
        $response = self::request('POST', $url, $payload);
        if (!$response['ok']) {
            return [
                'success' => false,
                'error' => self::buildErrorMessage($response, 'Failed to submit WiFi update task to GenieACS.'),
            ];
        }

        return [
            'success' => true,
            'error' => '',
        ];
    }

    public static function syncPppoeCredentials($config, $deviceId, $username, $password, $usernamePath = '', $passwordPath = '')
    {
        $deviceId = trim((string) $deviceId);
        $username = trim((string) $username);
        $password = trim((string) $password);
        $usernamePath = trim((string) $usernamePath);
        $passwordPath = trim((string) $passwordPath);

        if ($deviceId === '') {
            return [
                'success' => false,
                'warning' => '',
                'username_path' => '',
                'password_path' => '',
                'error' => 'Device ID is empty.',
            ];
        }

        if (!self::isConfigured($config)) {
            return [
                'success' => false,
                'warning' => '',
                'username_path' => '',
                'password_path' => '',
                'error' => 'GenieACS integration is disabled or URL is empty.',
            ];
        }

        $deviceResult = self::fetchDevice($config, $deviceId);
        if (!$deviceResult['success']) {
            return [
                'success' => false,
                'warning' => '',
                'username_path' => '',
                'password_path' => '',
                'error' => $deviceResult['error'],
            ];
        }

        $device = $deviceResult['device'];
        $pppoeUsernamePaths = self::getPppoeUsernamePaths();
        $pppoePasswordPaths = self::getPppoePasswordPaths();

        if ($usernamePath === '' && $passwordPath === '') {
            $internetPppoePaths = self::detectInternetPppoeCredentialPaths($device, $pppoeUsernamePaths, $pppoePasswordPaths);
            $usernamePath = $internetPppoePaths['username_path'];
            $passwordPath = $internetPppoePaths['password_path'];
            if ($usernamePath === '' && $passwordPath === '') {
                return [
                    'success' => false,
                    'warning' => '',
                    'username_path' => '',
                    'password_path' => '',
                    'error' => 'PPPoE username/password parameter path with connection name containing "Internet" was not found on this device.',
                ];
            }
        } else {
            if ($usernamePath === '') {
                $usernamePath = self::detectPath($device, $pppoeUsernamePaths);
            }
            if ($passwordPath === '') {
                $passwordPath = self::detectPath($device, $pppoePasswordPaths);
            }
        }
        if ($usernamePath === '' && $passwordPath === '') {
            return [
                'success' => false,
                'warning' => '',
                'username_path' => '',
                'password_path' => '',
                'error' => 'PPPoE username/password parameter path not found on this device.',
            ];
        }

        $parameterValues = [];
        if ($usernamePath !== '') {
            $parameterValues[] = [$usernamePath, $username, 'xsd:string'];
        }
        if ($passwordPath !== '') {
            $parameterValues[] = [$passwordPath, $password, 'xsd:string'];
        }
        if (empty($parameterValues)) {
            return [
                'success' => false,
                'warning' => '',
                'username_path' => $usernamePath,
                'password_path' => $passwordPath,
                'error' => 'No PPPoE credentials parameter to sync.',
            ];
        }

        $payload = [
            'name' => 'setParameterValues',
            'parameterValues' => $parameterValues,
        ];

        $baseUrl = self::getBaseUrl($config);
        $url = $baseUrl . '/devices/' . rawurlencode($deviceId) . '/tasks?connection_request';
        $response = self::request('POST', $url, $payload);
        if (!$response['ok']) {
            return [
                'success' => false,
                'warning' => '',
                'username_path' => $usernamePath,
                'password_path' => $passwordPath,
                'error' => self::buildErrorMessage($response, 'Failed to submit PPPoE credential sync task to GenieACS.'),
            ];
        }

        $warning = '';
        if ($usernamePath === '' || $passwordPath === '') {
            $warning = 'Only part of PPPoE credentials were synced because one parameter path was not found.';
        }

        return [
            'success' => true,
            'warning' => $warning,
            'username_path' => $usernamePath,
            'password_path' => $passwordPath,
            'error' => '',
        ];
    }

    private static function request($method, $url, $payload = null)
    {
        global $http_proxy, $http_proxyauth;

        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => '',
                'json' => null,
                'error' => 'cURL extension is not available.',
            ];
        }

        $method = strtoupper((string) $method);
        $headers = ['Accept: application/json'];
        $body = null;
        if ($payload !== null) {
            $body = json_encode($payload);
            $headers[] = 'Content-Type: application/json';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        if (!empty($http_proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $http_proxy);
            if (!empty($http_proxyauth)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $http_proxyauth);
            }
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = '';
        if (curl_errno($ch)) {
            $error = curl_error($ch);
        }
        curl_close($ch);

        if ($responseBody === false || $responseBody === null) {
            $responseBody = '';
        }

        $json = null;
        if ($responseBody !== '') {
            $decoded = json_decode($responseBody, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $json = $decoded;
            }
        }

        return [
            'ok' => ($status >= 200 && $status < 300) && $error === '',
            'status' => $status,
            'body' => $responseBody,
            'json' => $json,
            'error' => $error,
        ];
    }

    private static function buildErrorMessage($response, $fallback)
    {
        $error = trim((string) ($response['error'] ?? ''));
        if ($error !== '') {
            return $error;
        }
        $status = (int) ($response['status'] ?? 0);
        if ($status > 0) {
            return $fallback . ' (HTTP ' . $status . ')';
        }
        return $fallback;
    }

    private static function detectPath($source, $paths)
    {
        foreach ($paths as $path) {
            if (self::pathExists($source, $path)) {
                return $path;
            }
        }
        return '';
    }

    private static function buildWifiParameterValues($device, $ssidPath, $ssid, $passwordPath, $password)
    {
        $device = is_array($device) ? $device : [];
        $values = [];
        $seen = [];

        self::pushWifiParameter($values, $seen, $ssidPath, $ssid);
        self::pushWifiParameter($values, $seen, $passwordPath, $password);

        if (!empty($device)) {
            $ssidPathWlan5 = self::deriveWlanFivePath($ssidPath, 'ssid');
            if ($ssidPathWlan5 !== '' && self::pathExists($device, $ssidPathWlan5)) {
                self::pushWifiParameter($values, $seen, $ssidPathWlan5, $ssid);
            }

            $passwordPathWlan5 = self::deriveWlanFivePath($passwordPath, 'password');
            if ($passwordPathWlan5 !== '' && self::pathExists($device, $passwordPathWlan5)) {
                self::pushWifiParameter($values, $seen, $passwordPathWlan5, $password);
            }
        }

        return $values;
    }

    private static function pushWifiParameter(&$values, &$seen, $path, $value)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }
        if (isset($seen[$path])) {
            return;
        }
        $seen[$path] = true;
        $values[] = [$path, (string) $value, 'xsd:string'];
    }

    private static function deriveWlanFivePath($path, $kind)
    {
        $path = trim((string) $path);
        $kind = strtolower(trim((string) $kind));
        if ($path === '') {
            return '';
        }

        if ($kind === 'ssid') {
            if (strpos($path, '.WLANConfiguration.1.') !== false) {
                return str_replace('.WLANConfiguration.1.', '.WLANConfiguration.5.', $path);
            }
            if (strpos($path, '.SSID.1.') !== false) {
                return str_replace('.SSID.1.', '.SSID.5.', $path);
            }
            return '';
        }

        if ($kind === 'password') {
            if (strpos($path, '.WLANConfiguration.1.') !== false) {
                return str_replace('.WLANConfiguration.1.', '.WLANConfiguration.5.', $path);
            }
            if (strpos($path, '.AccessPoint.1.') !== false) {
                return str_replace('.AccessPoint.1.', '.AccessPoint.5.', $path);
            }
            return '';
        }

        return '';
    }

    private static function detectInternetPppoeCredentialPaths($device, $usernamePaths, $passwordPaths)
    {
        $baseIndex = [];
        $candidates = [];

        foreach ($usernamePaths as $path) {
            if (!self::pathExists($device, $path)) {
                continue;
            }
            $base = self::credentialPathBase($path);
            if ($base === '') {
                continue;
            }
            if (!isset($baseIndex[$base])) {
                $baseIndex[$base] = count($candidates);
                $candidates[] = [
                    'base' => $base,
                    'username_path' => '',
                    'password_path' => '',
                ];
            }
            $idx = $baseIndex[$base];
            if ($candidates[$idx]['username_path'] === '') {
                $candidates[$idx]['username_path'] = $path;
            }
        }

        foreach ($passwordPaths as $path) {
            if (!self::pathExists($device, $path)) {
                continue;
            }
            $base = self::credentialPathBase($path);
            if ($base === '') {
                continue;
            }
            if (!isset($baseIndex[$base])) {
                $baseIndex[$base] = count($candidates);
                $candidates[] = [
                    'base' => $base,
                    'username_path' => '',
                    'password_path' => '',
                ];
            }
            $idx = $baseIndex[$base];
            if ($candidates[$idx]['password_path'] === '') {
                $candidates[$idx]['password_path'] = $path;
            }
        }

        foreach ($candidates as $candidate) {
            if (self::connectionNameContainsKeyword($device, $candidate['base'], 'internet')) {
                return [
                    'username_path' => $candidate['username_path'],
                    'password_path' => $candidate['password_path'],
                ];
            }
        }

        return [
            'username_path' => '',
            'password_path' => '',
        ];
    }

    private static function credentialPathBase($path)
    {
        if (!is_string($path) || $path === '') {
            return '';
        }
        $suffixes = [
            '.Username',
            '.Password',
        ];
        foreach ($suffixes as $suffix) {
            if (substr($path, -strlen($suffix)) === $suffix) {
                return substr($path, 0, -strlen($suffix));
            }
        }
        return '';
    }

    private static function connectionNameContainsKeyword($device, $connectionBasePath, $keyword)
    {
        $connectionBasePath = trim((string) $connectionBasePath);
        $keyword = trim((string) $keyword);
        if ($connectionBasePath === '' || $keyword === '') {
            return false;
        }

        $connectionNamePaths = [
            $connectionBasePath . '.ConnectionName',
            $connectionBasePath . '.Name',
            $connectionBasePath . '.Alias',
            $connectionBasePath . '.X_CT-COM_ServiceList',
            $connectionBasePath . '.X_CT-COM_ConnectionName',
            $connectionBasePath . '.X_ZTE-COM_ConnectionName',
        ];

        foreach ($connectionNamePaths as $namePath) {
            $value = self::getScalarPathValue($device, $namePath);
            if ($value !== '' && stripos($value, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function pathExists($source, $path)
    {
        if (is_array($source) && array_key_exists($path, $source)) {
            return true;
        }

        $segments = explode('.', $path);
        $current = $source;
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }
        return true;
    }

    private static function getScalarPathValue($source, $path)
    {
        if (is_array($source) && array_key_exists($path, $source)) {
            return self::extractScalarNode($source[$path]);
        }

        $segments = explode('.', $path);
        $current = $source;
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return '';
            }
            $current = $current[$segment];
        }

        return self::extractScalarNode($current);
    }

    private static function buildDeviceLabel($device)
    {
        $id = trim((string) ($device['_id'] ?? ''));
        $serial = self::firstNonEmptyPathValue($device, [
            'DeviceID.SerialNumber',
            '_deviceId._SerialNumber',
            'InternetGatewayDevice.DeviceInfo.SerialNumber',
            'Device.DeviceInfo.SerialNumber',
        ]);
        $model = self::firstNonEmptyPathValue($device, [
            'DeviceID.ProductClass',
            '_deviceId._ProductClass',
            'InternetGatewayDevice.DeviceInfo.ModelName',
            'Device.DeviceInfo.ModelName',
        ]);
        $manufacturer = self::firstNonEmptyPathValue($device, [
            'DeviceID.Manufacturer',
            '_deviceId._Manufacturer',
            'InternetGatewayDevice.DeviceInfo.Manufacturer',
            'Device.DeviceInfo.Manufacturer',
        ]);
        $pppoeUsername = self::firstNonEmptyPathValue($device, self::getPppoeUsernamePaths());
        $pppoeIp = self::firstNonEmptyPathValue($device, self::getPppoeIpPaths());
        if ($pppoeIp === '0.0.0.0' || $pppoeIp === '::') {
            $pppoeIp = '';
        }

        $parts = [];
        if ($serial !== '') {
            $parts[] = $serial;
        }
        if ($model !== '') {
            $parts[] = $model;
        }
        if ($manufacturer !== '') {
            $parts[] = $manufacturer;
        }
        if ($pppoeUsername !== '') {
            $parts[] = 'PPP: ' . $pppoeUsername;
        }
        if ($pppoeIp !== '') {
            $parts[] = 'IP: ' . $pppoeIp;
        }

        if (empty($parts)) {
            return $id;
        }

        $label = implode(' | ', $parts);
        if ($id !== '') {
            $label .= ' [' . $id . ']';
        }
        return $label;
    }

    private static function firstNonEmptyPathValue($source, $paths)
    {
        foreach ($paths as $path) {
            $value = self::getScalarPathValue($source, $path);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    private static function isAssoc($array)
    {
        if (!is_array($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private static function extractScalarNode($node)
    {
        if (is_array($node)) {
            if (array_key_exists('_value', $node) && !is_array($node['_value'])) {
                return trim((string) $node['_value']);
            }
            if (array_key_exists('value', $node)) {
                $valueNode = $node['value'];
                if (is_array($valueNode)) {
                    if (isset($valueNode[0]) && !is_array($valueNode[0])) {
                        return trim((string) $valueNode[0]);
                    }
                } elseif (!is_array($valueNode)) {
                    return trim((string) $valueNode);
                }
            }
            return '';
        }

        return trim((string) $node);
    }

    private static function getPppoeUsernamePaths()
    {
        return array_values(array_unique(array_merge([
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.3.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.4.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.5.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.6.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.7.WANPPPConnection.1.Username',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.8.WANPPPConnection.1.Username',
        ], self::$pppoeUsernamePaths)));
    }

    private static function getPppoePasswordPaths()
    {
        return array_values(array_unique(array_merge([
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.3.WANPPPConnection.1.Password',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.4.WANPPPConnection.1.Password',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Password',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.Password',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.5.WANPPPConnection.1.Password',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.6.WANPPPConnection.1.Password',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.7.WANPPPConnection.1.Password',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.8.WANPPPConnection.1.Password',
        ], self::$pppoePasswordPaths)));
    }

    private static function getPppoeIpPaths()
    {
        return array_values(array_unique(array_merge([
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.4.WANIPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANIPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.4.WANPPPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.3.WANPPPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.5.WANPPPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.6.WANPPPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.7.WANPPPConnection.1.ExternalIPAddress',
            'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.8.WANPPPConnection.1.ExternalIPAddress',
        ], self::$pppoeIpPaths)));
    }
}
