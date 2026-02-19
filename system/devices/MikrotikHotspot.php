<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 * This is Core, don't modification except you want to contribute
 * better create new plugin
 **/

use PEAR2\Net\RouterOS;

class MikrotikHotspot
{

    // show Description
    function description()
    {
        return [
            'title' => 'Mikrotik Hotspot',
            'description' => 'To handle connection between PHPNuxBill with Mikrotik Hotspot',
            'author' => 'ibnux',
            'url' => [
                'Github' => 'https://github.com/hotspotbilling/phpnuxbill/',
                'Telegram' => 'https://t.me/phpnuxbill',
                'Donate' => 'https://paypal.me/ibnux'
            ]
        ];
    }


    function add_customer($customer, $plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
		$isExp = ORM::for_table('tbl_plans')->select("id")->where('plan_expired', $plan['id'])->find_one();
        $this->removeHotspotUser($client, $customer['username']);
		if ($isExp){
        $this->removeHotspotActiveUser($client, $customer['username']);
		}
        $this->addHotspotUser($client, $plan, $customer);
    }
	
	function sync_customer($customer, $plan)
	{
		$mikrotik = $this->info($plan['routers']);
		$client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
		$t = ORM::for_table('tbl_user_recharges')->where('username', $customer['username'])->where('status', 'on')->find_one();
		if ($t) {
			$printRequest = new RouterOS\Request('/ip/hotspot/user/print');
			$printRequest->setArgument('.proplist', '.id,limit-uptime,limit-bytes-total');
			$printRequest->setQuery(RouterOS\Query::where('name', $customer['username']));
			$userInfo = $client->sendSync($printRequest);
			$id = $userInfo->getProperty('.id');
			$uptime = $userInfo->getProperty('limit-uptime');
			$data = $userInfo->getProperty('limit-bytes-total');
			if (!empty($id) && (!empty($uptime) || !empty($data))) {
				$setRequest = new RouterOS\Request('/ip/hotspot/user/set');
				$setRequest->setArgument('numbers', $id);
				$setRequest->setArgument('profile', $t['namebp']);
				$client->sendSync($setRequest);
			} else {
				$this->add_customer($customer, $plan);
			}
		}
	}


    function remove_customer($customer, $plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            if($p){
                $this->add_customer($customer, $p);
                $this->removeHotspotActiveUser($client, $customer['username']);
                return;
            }
        }
        $this->removeHotspotUser($client, $customer['username']);
        $this->removeHotspotActiveUser($client, $customer['username']);
    }

    // customer change username
    public function change_username($plan, $from, $to)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        //check if customer exists
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $from));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        if (!empty($cid)) {
            $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
            $setRequest->setArgument('numbers', $id);
            $setRequest->setArgument('name', $to);
            $client->sendSync($setRequest);
            //disconnect then
            $this->removeHotspotActiveUser($client, $from);
        }
    }

    function add_plan($plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $bw = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
        if ($bw['rate_down_unit'] == 'Kbps') {
            $unitdown = 'K';
        } else {
            $unitdown = 'M';
        }
        if ($bw['rate_up_unit'] == 'Kbps') {
            $unitup = 'K';
        } else {
            $unitup = 'M';
        }
        $rate = $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown;
        if (!empty(trim($bw['burst']))) {
            $rate .= ' ' . $bw['burst'];
        }
		if ($bw['rate_up'] == '0' || $bw['rate_down'] == '0') {
			$rate = '';
		}
        $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $plan['name_plan'])
                ->setArgument('shared-users', $plan['shared_users'])
                ->setArgument('rate-limit', $rate)
        );
    }

    function online_customer($customer, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $customer['username'])
        );
        $id =  $client->sendSync($printRequest)->getProperty('.id');
        return $id;
    }

    function connect_customer($customer, $ip, $mac_address, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $addRequest = new RouterOS\Request('/ip/hotspot/active/login');
        $client->sendSync(
            $addRequest
                ->setArgument('user', $customer['username'])
                ->setArgument('password', $customer['password'])
                ->setArgument('ip', $ip)
                ->setArgument('mac-address', $mac_address)
        );
    }

    function disconnect_customer($customer, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $customer['username'])
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }


    function update_plan($old_plan, $new_plan)
    {
        $mikrotik = $this->info($new_plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);

        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $old_plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            $this->add_plan($new_plan);
        } else {
            $bw = ORM::for_table("tbl_bandwidth")->find_one($new_plan['id_bw']);
            if ($bw['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
            } else {
                $unitdown = 'M';
            }
            if ($bw['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
            } else {
                $unitup = 'M';
            }
            $rate = $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown;
            if (!empty(trim($bw['burst']))) {
                $rate .= ' ' . $bw['burst'];
            }
			if ($bw['rate_up'] == '0' || $bw['rate_down'] == '0') {
				$rate = '';
			}
            $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('name', $new_plan['name_plan'])
                    ->setArgument('shared-users', $new_plan['shared_users'])
                    ->setArgument('rate-limit', $rate)
                    ->setArgument('on-login', $new_plan['on_login'])
                    ->setArgument('on-logout', $new_plan['on_logout'])
            );
        }
    }

    function remove_plan($plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    function info($name)
    {
        return ORM::for_table('tbl_routers')->where('name', $name)->find_one();
    }

    function getClient($ip, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $iport = explode(":", $ip);
        $host = trim((string) ($iport[0] ?? ''));
        $port = !empty($iport[1]) ? (int) $iport[1] : null;
        $cacheKey = strtolower($host) . '|' . strtolower(trim((string) $user)) . '|' . (string) $port . '|' . md5((string) $pass);

        static $clientPool = [];
        if (isset($clientPool[$cacheKey]) && $clientPool[$cacheKey] instanceof RouterOS\Client) {
            return $clientPool[$cacheKey];
        }

        $clientPool[$cacheKey] = new RouterOS\Client($host, $user, $pass, $port);
        return $clientPool[$cacheKey];
    }

    protected function resolveHotspotUsageUsername($customer)
    {
        if (is_array($customer)) {
            return trim((string) ($customer['username'] ?? ''));
        }
        if (is_object($customer)) {
            if (isset($customer->username)) {
                return trim((string) $customer->username);
            }
            if (method_exists($customer, 'as_array')) {
                $row = (array) $customer->as_array();
                return trim((string) ($row['username'] ?? ''));
            }
        }
        return trim((string) $customer);
    }

    // Keep this method name for compatibility with existing usage collector flow.
    // For Hotspot we map:
    // bytes-out (router -> client) = Download (tx_byte)
    // bytes-in  (client -> router) = Upload   (rx_byte)
    public function getPppoeBindingCounters($customer, $plan, &$warning = '', $bindingName = '')
    {
        $warning = '';
        $username = $this->resolveHotspotUsageUsername($customer);
        if ($username === '') {
            $warning = 'Hotspot username is empty for usage counter read.';
            return false;
        }

        $routerName = trim((string) ($plan['routers'] ?? ''));
        if ($routerName === '' || strtolower($routerName) === 'radius') {
            $warning = 'Hotspot usage counter read requires direct MikroTik router.';
            return false;
        }

        $mikrotik = $this->info($routerName);
        if (!$mikrotik) {
            $warning = 'Router not found for usage counter read: ' . $routerName;
            return false;
        }

        try {
            $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        } catch (Throwable $e) {
            $warning = 'Error connecting to RouterOS for hotspot usage counter read: ' . $e->getMessage();
            return false;
        }
        if (!$client) {
            $warning = 'RouterOS client is unavailable for hotspot usage counter read.';
            return false;
        }

        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id,name,bytes-in,bytes-out');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));

        try {
            $responses = $client->sendSync($printRequest);
        } catch (Throwable $e) {
            $warning = 'Failed reading hotspot usage counters: ' . $e->getMessage();
            return false;
        }

        $userId = '';
        $foundName = $username;
        $bytesIn = 0;
        $bytesOut = 0;
        if ($responses instanceof Traversable || is_array($responses)) {
            foreach ($responses as $response) {
                if (!($response instanceof RouterOS\Response)) {
                    continue;
                }
                $rowId = trim((string) $response->getProperty('.id'));
                if ($rowId === '') {
                    continue;
                }
                $userId = $rowId;
                $foundName = trim((string) $response->getProperty('name'));
                if ($foundName === '') {
                    $foundName = $username;
                }
                $bytesIn = max(0, (int) $response->getProperty('bytes-in'));
                $bytesOut = max(0, (int) $response->getProperty('bytes-out'));
                break;
            }
        }

        if ($userId === '') {
            $warning = 'Hotspot user counter source not found: ' . $username;
            return false;
        }

        // Some RouterOS setups keep user bytes-in/out at zero while active session
        // counters are increasing. Fallback to active counters in that case.
        $activeBytesIn = 0;
        $activeBytesOut = 0;
        try {
            $activeRequest = new RouterOS\Request('/ip/hotspot/active/print');
            $activeRequest->setArgument('.proplist', '.id,user,bytes-in,bytes-out');
            $activeRequest->setQuery(RouterOS\Query::where('user', $username));
            $activeResponses = $client->sendSync($activeRequest);
            if ($activeResponses instanceof Traversable || is_array($activeResponses)) {
                foreach ($activeResponses as $activeResponse) {
                    if (!($activeResponse instanceof RouterOS\Response)) {
                        continue;
                    }
                    $activeId = trim((string) $activeResponse->getProperty('.id'));
                    if ($activeId === '') {
                        continue;
                    }
                    $activeBytesIn += max(0, (int) $activeResponse->getProperty('bytes-in'));
                    $activeBytesOut += max(0, (int) $activeResponse->getProperty('bytes-out'));
                }
            }
        } catch (Throwable $e) {
            // Ignore active fallback read failure and keep user counters.
        }

        if ($bytesIn === 0 && $bytesOut === 0 && ($activeBytesIn > 0 || $activeBytesOut > 0)) {
            $bytesIn = $activeBytesIn;
            $bytesOut = $activeBytesOut;
        }

        return [
            'tx_byte' => $bytesOut,
            'rx_byte' => $bytesIn,
            'binding_name' => $foundName,
            'binding_user' => $username,
        ];
    }

    public function resetPppoeBindingCounters($customer, $plan, &$warning = '', $bindingName = '')
    {
        $warning = '';
        $username = $this->resolveHotspotUsageUsername($customer);
        if ($username === '') {
            $warning = 'Hotspot username is empty for counter reset.';
            return false;
        }

        $routerName = trim((string) ($plan['routers'] ?? ''));
        if ($routerName === '' || strtolower($routerName) === 'radius') {
            $warning = 'Hotspot counter reset requires direct MikroTik router.';
            return false;
        }

        $mikrotik = $this->info($routerName);
        if (!$mikrotik) {
            $warning = 'Router not found for hotspot counter reset: ' . $routerName;
            return false;
        }

        try {
            $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        } catch (Throwable $e) {
            $warning = 'Error connecting to RouterOS for hotspot counter reset: ' . $e->getMessage();
            return false;
        }
        if (!$client) {
            $warning = 'RouterOS client is unavailable for hotspot counter reset.';
            return false;
        }

        $findRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $findRequest->setArgument('.proplist', '.id');
        $findRequest->setQuery(RouterOS\Query::where('name', $username));
        try {
            $userId = trim((string) $client->sendSync($findRequest)->getProperty('.id'));
        } catch (Throwable $e) {
            $warning = 'Failed finding hotspot user before counter reset: ' . $e->getMessage();
            return false;
        }

        if ($userId === '') {
            $warning = 'Hotspot user not found for counter reset: ' . $username;
            return false;
        }

        $resetRequest = new RouterOS\Request('/ip/hotspot/user/reset-counters');
        $resetRequest->setArgument('numbers', $userId);
        try {
            $client->sendSync($resetRequest);
        } catch (Throwable $e) {
            $warning = 'Failed resetting hotspot counters: ' . $e->getMessage();
            return false;
        }

        return true;
    }

    function removeHotspotUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user print .proplist=.id',
            RouterOS\Query::where('name', $username)
        );
        $userID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $userID)
        );
    }

    function addHotspotUser($client, $plan, $customer)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $username = trim((string) ($customer['username'] ?? ''));
        $password = (string) ($customer['password'] ?? '');
        $comment = trim((string) ($customer['fullname'] ?? ''));
        if (!empty($customer['id'])) {
            $comment = trim($comment . ' | ' . implode(', ', User::getBillNames($customer['id'])));
        }
        $email = trim((string) ($customer['email'] ?? ''));
        $hasValidEmail = ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false);

        $buildAddRequest = function () use ($username, $password, $comment, $plan, $hasValidEmail, $email) {
            $request = new RouterOS\Request('/ip/hotspot/user/add');
            $request->setArgument('name', $username);
            $request->setArgument('profile', $plan['name_plan']);
            $request->setArgument('password', $password);
            $request->setArgument('comment', $comment);
            if ($hasValidEmail) {
                $request->setArgument('email', $email);
            }
            return $request;
        };

        if ($plan['typebp'] == "Limited") {
            if ($plan['limit_type'] == "Time_Limit") {
                if ($plan['time_unit'] == 'Hrs')
                    $timelimit = $plan['time_limit'] . ":00:00";
                else
                    $timelimit = "00:" . $plan['time_limit'] . ":00";
                $addRequest = $buildAddRequest();
                $client->sendSync(
                    $addRequest
                        ->setArgument('limit-uptime', $timelimit)
                );
            } else if ($plan['limit_type'] == "Data_Limit") {
                if ($plan['data_unit'] == 'GB')
                    $datalimit = $plan['data_limit'] . "000000000";
                else
                    $datalimit = $plan['data_limit'] . "000000";
                $addRequest = $buildAddRequest();
                $client->sendSync(
                    $addRequest
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            } else if ($plan['limit_type'] == "Both_Limit") {
                if ($plan['time_unit'] == 'Hrs')
                    $timelimit = $plan['time_limit'] . ":00:00";
                else
                    $timelimit = "00:" . $plan['time_limit'] . ":00";
                if ($plan['data_unit'] == 'GB')
                    $datalimit = $plan['data_limit'] . "000000000";
                else
                    $datalimit = $plan['data_limit'] . "000000";
                $addRequest = $buildAddRequest();
                $client->sendSync(
                    $addRequest
                        ->setArgument('limit-uptime', $timelimit)
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            }
        } else {
            $addRequest = $buildAddRequest();
            $client->sendSync(
                $addRequest
            );
        }
    }

    function setHotspotUser($client, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    function setHotspotUserPackage($client, $username, $plan_name)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('profile', $plan_name);
        $client->sendSync($setRequest);
    }

    function removeHotspotActiveUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('user', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    function getIpHotspotUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $username)
        );
        return $client->sendSync($printRequest)->getProperty('address');
    }
}
