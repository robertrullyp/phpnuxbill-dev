<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 * This is Core, don't modification except you want to contribute
 * better create new plugin
 **/

use PEAR2\Net\RouterOS;

class MikrotikPppoe
{
    // show Description
    function description()
    {
        return [
            'title' => 'Mikrotik PPPOE',
            'description' => 'To handle connection between PHPNuxBill with Mikrotik PPPOE',
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
        global $isChangePlan;
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $profileWarning = '';
        if (!$this->ensurePppProfileExists($client, $plan, $profileWarning)) {
            throw new Exception(
                $profileWarning !== ''
                    ? $profileWarning
                    : ('PPPoE profile is missing and cannot be created for plan: ' . ($plan['name_plan'] ?? '-'))
            );
        }
        $cid = self::getIdByCustomer($customer, $client);
        $isExp = ORM::for_table('tbl_plans')->select("id")->where('plan_expired', $plan['id'])->find_one();
        if (empty($cid)) {
            //customer not exists, add it
            $this->addPpoeUser($client, $plan, $customer, $isExp);
        }else{
            $setRequest = new RouterOS\Request('/ppp/secret/set');
            $setRequest->setArgument('numbers', $cid);
            if (!empty($customer['pppoe_password'])) {
                $setRequest->setArgument('password', $customer['pppoe_password']);
            } else {
                $setRequest->setArgument('password', $customer['password']);
            }
            $setRequest->setArgument('name', $this->resolveSecretUsername($customer));
            $unsetIP = false;
            if (!empty($customer['pppoe_ip']) && !$isExp){
                $setRequest->setArgument('remote-address', $customer['pppoe_ip']);
            } else {
                $unsetIP = true;
				}
            $setRequest->setArgument('profile', $plan['name_plan']);
            $setRequest->setArgument('comment', $this->resolvePppoeComment($customer));
            $client->sendSync($setRequest);

            if($unsetIP){
                $unsetRequest = new RouterOS\Request('/ppp/secret/unset');
                $unsetRequest->setArgument('.id', $cid);
                $unsetRequest->setArgument('value-name','remote-address');
                $client->sendSync($unsetRequest);
            }


            //disconnect then
            if(isset($isChangePlan) && $isChangePlan){
                $this->removePpoeActive($client, $customer['username']);
                if (!empty($customer['pppoe_username'])) {
                    $this->removePpoeActive($client, $customer['pppoe_username']);
                }
            }
        }

        $bindingName = '';
        $bindingWarning = '';
        if (!$this->ensurePppoeServerBinding($customer, $plan, $bindingName, $bindingWarning) && $bindingWarning !== '') {
            $this->logPppoeBindingWarning($plan, $customer, $bindingWarning, 'add_customer');
        }
    }

	function sync_customer($customer, $plan)
    {
        $this->add_customer($customer, $plan);
    }

    function remove_customer($customer, $plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            if($p){
                $this->add_customer($customer, $p);
                $this->removePpoeActive($client, $customer['username']);
                if (!empty($customer['pppoe_username'])) {
                    $this->removePpoeActive($client, $customer['pppoe_username']);
                }
                return;
            }
        }
        $this->removePpoeUser($client, $customer['username']);
        if (!empty($customer['pppoe_username'])) {
            $this->removePpoeUser($client, $customer['pppoe_username']);
        }
        $this->removePpoeActive($client, $customer['username']);
        if (!empty($customer['pppoe_username'])) {
            $this->removePpoeActive($client, $customer['pppoe_username']);
        }
        $bindingWarning = '';
        if (!$this->removePppoeServerBinding($customer, $plan, $bindingWarning) && $bindingWarning !== '') {
            $this->logPppoeBindingWarning($plan, $customer, $bindingWarning, 'remove_customer');
        }
    }

    // customer change username
    public function change_username($plan, $from, $to)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        //check if customer exists
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $from));
        $cid = $client->sendSync($printRequest)->getProperty('.id');
        if (!empty($cid)) {
            $setRequest = new RouterOS\Request('/ppp/secret/set');
            $setRequest->setArgument('numbers', $cid);
            $setRequest->setArgument('name', $to);
            $client->sendSync($setRequest);
            //disconnect then
            $this->removePpoeActive($client, $from);
            $this->renamePppoeServerBinding($client, $from, $to);
        }
    }

    function add_plan($plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $warning = '';
        if (!$this->syncPppProfile($client, $plan, $warning)) {
            throw new Exception(
                $warning !== ''
                    ? $warning
                    : ('Failed to sync PPPoE profile for plan: ' . ($plan['name_plan'] ?? '-'))
            );
        }
    }

    protected function buildPppProfileRate($plan)
    {
        $bw = ORM::for_table('tbl_bandwidth')->find_one((int) ($plan['id_bw'] ?? 0));
        if (!$bw) {
            return '';
        }

        $unitdown = ($bw['rate_down_unit'] == 'Kbps') ? 'K' : 'M';
        $unitup = ($bw['rate_up_unit'] == 'Kbps') ? 'K' : 'M';
        $rate = $bw['rate_up'] . $unitup . '/' . $bw['rate_down'] . $unitdown;
        if (!empty(trim((string) $bw['burst']))) {
            $rate .= ' ' . trim((string) $bw['burst']);
        }
        if ((string) $bw['rate_up'] === '0' || (string) $bw['rate_down'] === '0') {
            $rate = '';
        }

        return $rate;
    }

    protected function buildPppProfileAddresses($plan)
    {
        $poolName = trim((string) ($plan['pool'] ?? ''));
        $localAddress = $poolName;
        $remoteAddress = $poolName;

        if ($poolName !== '') {
            $pool = $this->resolvePlanPoolRecord($plan, $poolName);
            if ($pool) {
                $remoteAddress = trim((string) ($pool['pool_name'] ?? $poolName));
                $localAddress = trim((string) ($pool['local_ip'] ?? ''));
                if ($localAddress === '') {
                    $localAddress = $remoteAddress;
                }
            }
        }

        return [$localAddress, $remoteAddress];
    }

    protected function resolvePlanPoolRecord($plan, $poolName)
    {
        $poolName = trim((string) $poolName);
        if ($poolName === '') {
            return null;
        }

        $routerName = trim((string) ($plan['routers'] ?? ''));
        if ($routerName !== '') {
            $pool = ORM::for_table('tbl_pool')
                ->where('pool_name', $poolName)
                ->where('routers', $routerName)
                ->find_one();
            if ($pool) {
                return $pool;
            }
        }

        return ORM::for_table('tbl_pool')
            ->where('pool_name', $poolName)
            ->find_one();
    }

    protected function findRouterPool($client, $poolName)
    {
        $poolName = trim((string) $poolName);
        if ($poolName === '') {
            return ['id' => '', 'ranges' => ''];
        }

        $printRequest = new RouterOS\Request('/ip/pool/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $poolName));
        $response = $client->sendSync($printRequest);

        return [
            'id' => (string) $response->getProperty('.id'),
            'ranges' => trim((string) $response->getProperty('ranges')),
        ];
    }

    protected function ensurePppPoolExists($client, $plan, &$warning = '')
    {
        $warning = '';
        $poolName = trim((string) ($plan['pool'] ?? ''));
        if ($poolName === '') {
            return true;
        }

        if (!$client) {
            $warning = 'Router client is not available for PPPoE pool sync.';
            return false;
        }

        $poolRecord = $this->resolvePlanPoolRecord($plan, $poolName);
        if (!$poolRecord) {
            $warning = 'PPPoE pool "' . $poolName . '" is not found in system pool list.';
            return false;
        }

        $ranges = trim((string) ($poolRecord['range_ip'] ?? ''));
        if ($ranges === '') {
            $warning = 'PPPoE pool "' . $poolName . '" has empty range.';
            return false;
        }

        try {
            $routerPool = $this->findRouterPool($client, $poolName);
            if ($routerPool['id'] === '') {
                $addRequest = new RouterOS\Request('/ip/pool/add');
                $addRequest->setArgument('name', $poolName);
                $addRequest->setArgument('ranges', $ranges);
                $client->sendSync($addRequest);
                return true;
            }

            if ($routerPool['ranges'] !== $ranges) {
                $setRequest = new RouterOS\Request('/ip/pool/set');
                $setRequest->setArgument('numbers', $routerPool['id']);
                $setRequest->setArgument('name', $poolName);
                $setRequest->setArgument('ranges', $ranges);
                $client->sendSync($setRequest);
            }

            return true;
        } catch (Throwable $e) {
            $warning = 'Failed to sync PPPoE pool "' . $poolName . '": ' . $e->getMessage();
            return false;
        }
    }

    protected function findPppProfileId($client, $profileName)
    {
        $profileName = trim((string) $profileName);
        if ($profileName === '') {
            return '';
        }

        $printRequest = new RouterOS\Request('/ppp/profile/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $profileName));
        return (string) $client->sendSync($printRequest)->getProperty('.id');
    }

    protected function syncPppProfile($client, $plan, &$warning = '')
    {
        $warning = '';

        if (!$client) {
            $warning = 'Router client is not available for PPPoE profile sync.';
            return false;
        }

        $profileName = trim((string) ($plan['name_plan'] ?? ''));
        if ($profileName === '') {
            $warning = 'Plan name is empty, cannot sync PPPoE profile.';
            return false;
        }

        if (!$this->ensurePppPoolExists($client, $plan, $warning)) {
            return false;
        }

        list($localAddress, $remoteAddress) = $this->buildPppProfileAddresses($plan);
        if ($remoteAddress === '') {
            $warning = 'PPPoE plan pool is empty. Save a valid pool first.';
            return false;
        }

        try {
            $profileId = $this->findPppProfileId($client, $profileName);
            $request = new RouterOS\Request($profileId === '' ? '/ppp/profile/add' : '/ppp/profile/set');
            if ($profileId !== '') {
                $request->setArgument('numbers', $profileId);
            } else {
                $request->setArgument('name', $profileName);
            }

            $request->setArgument('local-address', $localAddress);
            $request->setArgument('remote-address', $remoteAddress);
            $request->setArgument('rate-limit', $this->buildPppProfileRate($plan));

            if (isset($plan['on_login'])) {
                $request->setArgument('on-up', (string) $plan['on_login']);
            }
            if (isset($plan['on_logout'])) {
                $request->setArgument('on-down', (string) $plan['on_logout']);
            }

            $client->sendSync($request);
            return true;
        } catch (Throwable $e) {
            $warning = 'Failed to sync PPPoE profile "' . $profileName . '": ' . $e->getMessage();
            return false;
        }
    }

    protected function ensurePppProfileExists($client, $plan, &$warning = '')
    {
        $warning = '';
        $profileName = trim((string) ($plan['name_plan'] ?? ''));
        if ($profileName === '') {
            $warning = 'Plan name is empty, cannot ensure PPPoE profile.';
            return false;
        }

        $profileId = $this->findPppProfileId($client, $profileName);
        if ($profileId !== '') {
            return true;
        }

        return $this->syncPppProfile($client, $plan, $warning);
    }

    /**
     * Function to ID by username from Mikrotik
     */
    function getIdByCustomer($customer, $client){
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $customer['username']));
        $id = $client->sendSync($printRequest)->getProperty('.id');
        if(empty($id)){
            if (!empty($customer['pppoe_username'])) {
                $printRequest = new RouterOS\Request('/ppp/secret/print');
                $printRequest->setQuery(RouterOS\Query::where('name', $customer['pppoe_username']));
                $id = $client->sendSync($printRequest)->getProperty('.id');
            }
        }
        return $id;
    }

    public function resolveSecretUsername($customer)
    {
        $pppoeUsername = trim((string) ($customer['pppoe_username'] ?? ''));
        if ($pppoeUsername !== '') {
            return $pppoeUsername;
        }
        return trim((string) ($customer['username'] ?? ''));
    }

    public function resolvePppoeBindingName($customer)
    {
        $secretUsername = $this->resolveSecretUsername($customer);
        if ($secretUsername === '') {
            return '';
        }
        $safeUsername = preg_replace('/[^a-zA-Z0-9._-]/', '_', $secretUsername);
        $safeUsername = trim((string) $safeUsername, '_');
        if ($safeUsername === '') {
            $safeUsername = 'user';
        }
        return 'pppoe-' . $safeUsername;
    }

    protected function resolvePppoeComment($customer)
    {
        $fullname = trim((string) ($customer['fullname'] ?? ''));
        $email = trim((string) ($customer['email'] ?? ''));
        $customerId = (int) ($customer['id'] ?? 0);
        $billNames = [];
        if ($customerId > 0) {
            $billNames = User::getBillNames($customerId);
        }
        return $fullname . ' | ' . $email . ' | ' . implode(', ', $billNames);
    }

    protected function logPppoeBindingWarning($plan, $customer, $warning, $action = 'sync')
    {
        $warning = trim((string) $warning);
        if ($warning === '') {
            return;
        }

        $routerName = trim((string) ($plan['routers'] ?? ''));
        $planName = trim((string) ($plan['name_plan'] ?? ''));
        $username = trim((string) ($customer['username'] ?? ''));
        $secretUsername = $this->resolveSecretUsername($customer);

        $text = "PPPoE Binding Warning ({$action})\n" .
            "Router: {$routerName}\n" .
            "Plan: {$planName}\n" .
            "Customer: {$username}\n" .
            "Secret User: {$secretUsername}\n" .
            "Detail: {$warning}";

        if (function_exists('_log')) {
            _log($text);
        }
        if (class_exists('Message')) {
            Message::sendTelegram($text);
        }
    }

    protected function readPppoeServerServices($client)
    {
        $services = [];
        if (!$client) {
            return $services;
        }
        $request = new RouterOS\Request('/interface/pppoe-server/server/print');
        $responses = $client->sendSync($request);
        foreach ($responses as $response) {
            $service = trim((string) $response->getProperty('service-name'));
            if ($service !== '') {
                $services[$service] = $service;
            }
        }
        $services = array_values($services);
        sort($services, SORT_NATURAL | SORT_FLAG_CASE);
        return $services;
    }

    public function listPppoeServerServices($routerName, &$error = '')
    {
        $error = '';
        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            return [];
        }

        try {
            $mikrotik = $this->info($routerName);
            if (!$mikrotik) {
                $error = 'Router not found.';
                return [];
            }
            $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
            if (!$client) {
                $error = 'Router client is unavailable.';
                return [];
            }
            return $this->readPppoeServerServices($client);
        } catch (Throwable $e) {
            $error = $e->getMessage();
            return [];
        }
    }

    protected function resolvePlanPppoeService($client, $plan, &$warning = '')
    {
        $warning = '';
        $service = trim((string) ($plan['pppoe_service'] ?? ''));
        if ($service !== '') {
            return $service;
        }
        $services = $this->readPppoeServerServices($client);
        if (!empty($services)) {
            return (string) $services[0];
        }
        $warning = 'PPPoE service is empty and router has no PPPoE server entries.';
        return '';
    }

    protected function findPppoeBindingByField($client, $field, $value)
    {
        $value = trim((string) $value);
        if ($value === '' || !$client) {
            return null;
        }

        $request = new RouterOS\Request('/interface/pppoe-server/print');
        $request->setQuery(RouterOS\Query::where($field, $value));
        $responses = $client->sendSync($request);
        foreach ($responses as $response) {
            $id = trim((string) $response->getProperty('.id'));
            if ($id === '') {
                continue;
            }
            return [
                'id' => $id,
                'name' => trim((string) $response->getProperty('name')),
                'user' => trim((string) $response->getProperty('user')),
                'service' => trim((string) $response->getProperty('service')),
            ];
        }
        return null;
    }

    protected function findInterfaceByName($client, $name)
    {
        $name = trim((string) $name);
        if ($name === '' || !$client) {
            return null;
        }
        $request = new RouterOS\Request('/interface/print');
        $request->setQuery(RouterOS\Query::where('name', $name));
        $responses = $client->sendSync($request);
        foreach ($responses as $response) {
            $id = trim((string) $response->getProperty('.id'));
            if ($id === '') {
                continue;
            }
            return [
                'id' => $id,
                'name' => trim((string) $response->getProperty('name')),
                'rx-byte' => (int) $response->getProperty('rx-byte'),
                'tx-byte' => (int) $response->getProperty('tx-byte'),
            ];
        }
        return null;
    }

    public function ensurePppoeServerBinding($customer, $plan, &$bindingName = '', &$warning = '')
    {
        $bindingName = $this->resolvePppoeBindingName($customer);
        $warning = '';
        if ($bindingName === '') {
            $warning = 'PPPoE binding name is empty.';
            return false;
        }

        $routerName = trim((string) ($plan['routers'] ?? ''));
        if ($routerName === '') {
            $warning = 'Router name is empty.';
            return false;
        }

        try {
            $mikrotik = $this->info($routerName);
            if (!$mikrotik) {
                $warning = 'Router not found.';
                return false;
            }
            $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
            if (!$client) {
                $warning = 'Router client is unavailable.';
                return false;
            }

            $secretUsername = $this->resolveSecretUsername($customer);
            if ($secretUsername === '') {
                $warning = 'Secret username is empty.';
                return false;
            }

            $serviceWarning = '';
            $serviceName = $this->resolvePlanPppoeService($client, $plan, $serviceWarning);
            if ($serviceName === '') {
                $warning = $serviceWarning !== '' ? $serviceWarning : 'Unable to resolve PPPoE service.';
                return false;
            }

            $comment = $this->resolvePppoeComment($customer);
            $existing = $this->findPppoeBindingByField($client, 'name', $bindingName);
            if (!$existing) {
                $existing = $this->findPppoeBindingByField($client, 'user', $secretUsername);
            }

            if ($existing) {
                $setRequest = new RouterOS\Request('/interface/pppoe-server/set');
                $setRequest->setArgument('numbers', $existing['id']);
                $setRequest->setArgument('name', $bindingName);
                $setRequest->setArgument('user', $secretUsername);
                $setRequest->setArgument('service', $serviceName);
                $setRequest->setArgument('comment', $comment);
                $client->sendSync($setRequest);
            } else {
                $addRequest = new RouterOS\Request('/interface/pppoe-server/add');
                $addRequest->setArgument('name', $bindingName);
                $addRequest->setArgument('user', $secretUsername);
                $addRequest->setArgument('service', $serviceName);
                $addRequest->setArgument('comment', $comment);
                $client->sendSync($addRequest);
            }

            return true;
        } catch (Throwable $e) {
            $warning = $e->getMessage();
            return false;
        }
    }

    public function removePppoeServerBinding($customer, $plan, &$warning = '')
    {
        $warning = '';
        $routerName = trim((string) ($plan['routers'] ?? ''));
        if ($routerName === '') {
            return false;
        }

        try {
            $mikrotik = $this->info($routerName);
            if (!$mikrotik) {
                $warning = 'Router not found.';
                return false;
            }
            $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
            if (!$client) {
                $warning = 'Router client is unavailable.';
                return false;
            }

            $usernames = [];
            $username = trim((string) ($customer['username'] ?? ''));
            if ($username !== '') {
                $usernames[] = $username;
            }
            $pppoeUsername = trim((string) ($customer['pppoe_username'] ?? ''));
            if ($pppoeUsername !== '') {
                $usernames[] = $pppoeUsername;
            }
            $usernames = array_values(array_unique($usernames));

            $bindingIds = [];
            foreach ($usernames as $user) {
                $candidate = $this->findPppoeBindingByField($client, 'name', 'pppoe-' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $user));
                if ($candidate && !empty($candidate['id'])) {
                    $bindingIds[$candidate['id']] = $candidate['id'];
                }
                $candidate = $this->findPppoeBindingByField($client, 'user', $user);
                if ($candidate && !empty($candidate['id'])) {
                    $bindingIds[$candidate['id']] = $candidate['id'];
                }
            }

            foreach ($bindingIds as $bindingId) {
                $removeRequest = new RouterOS\Request('/interface/pppoe-server/remove');
                $removeRequest->setArgument('numbers', $bindingId);
                $client->sendSync($removeRequest);
            }

            return true;
        } catch (Throwable $e) {
            $warning = $e->getMessage();
            return false;
        }
    }

    protected function renamePppoeServerBinding($client, $fromUsername, $toUsername, $comment = '')
    {
        $fromUsername = trim((string) $fromUsername);
        $toUsername = trim((string) $toUsername);
        if ($fromUsername === '' || $toUsername === '' || !$client) {
            return false;
        }

        $fromBindingName = 'pppoe-' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fromUsername);
        $toBindingName = 'pppoe-' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $toUsername);

        $existing = $this->findPppoeBindingByField($client, 'name', $fromBindingName);
        if (!$existing) {
            $existing = $this->findPppoeBindingByField($client, 'user', $fromUsername);
        }
        if (!$existing) {
            return false;
        }

        $setRequest = new RouterOS\Request('/interface/pppoe-server/set');
        $setRequest->setArgument('numbers', $existing['id']);
        $setRequest->setArgument('name', $toBindingName);
        $setRequest->setArgument('user', $toUsername);
        if (trim((string) $comment) !== '') {
            $setRequest->setArgument('comment', trim((string) $comment));
        }
        $client->sendSync($setRequest);

        return true;
    }

    public function getPppoeBindingCounters($customer, $plan, &$warning = '', $bindingName = '')
    {
        $warning = '';
        $routerName = trim((string) ($plan['routers'] ?? ''));
        if ($routerName === '') {
            $warning = 'Router name is empty.';
            return null;
        }

        try {
            $mikrotik = $this->info($routerName);
            if (!$mikrotik) {
                $warning = 'Router not found.';
                return null;
            }
            $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
            if (!$client) {
                $warning = 'Router client is unavailable.';
                return null;
            }

            $secretUsername = $this->resolveSecretUsername($customer);
            if ($bindingName === '') {
                $bindingName = $this->resolvePppoeBindingName($customer);
            }

            $binding = $this->findPppoeBindingByField($client, 'name', $bindingName);
            if (!$binding && $secretUsername !== '') {
                $binding = $this->findPppoeBindingByField($client, 'user', $secretUsername);
            }
            if ($binding && !empty($binding['name'])) {
                $bindingName = $binding['name'];
            }

            $request = new RouterOS\Request('/interface/print');
            $request->setArgument('stats', '');
            $request->setQuery(RouterOS\Query::where('name', $bindingName));
            $responses = $client->sendSync($request);
            foreach ($responses as $response) {
                return [
                    'binding_name' => $bindingName,
                    'rx_byte' => max(0, (int) $response->getProperty('rx-byte')),
                    'tx_byte' => max(0, (int) $response->getProperty('tx-byte')),
                ];
            }

            $warning = 'PPPoE binding interface counters not found.';
            return null;
        } catch (Throwable $e) {
            $warning = $e->getMessage();
            return null;
        }
    }

    public function resetPppoeBindingCounters($customer, $plan, &$warning = '', $bindingName = '')
    {
        $warning = '';
        $routerName = trim((string) ($plan['routers'] ?? ''));
        if ($routerName === '') {
            $warning = 'Router name is empty.';
            return false;
        }

        try {
            $mikrotik = $this->info($routerName);
            if (!$mikrotik) {
                $warning = 'Router not found.';
                return false;
            }
            $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
            if (!$client) {
                $warning = 'Router client is unavailable.';
                return false;
            }

            $secretUsername = $this->resolveSecretUsername($customer);
            if ($bindingName === '') {
                $bindingName = $this->resolvePppoeBindingName($customer);
            }

            $binding = $this->findPppoeBindingByField($client, 'name', $bindingName);
            if (!$binding && $secretUsername !== '') {
                $binding = $this->findPppoeBindingByField($client, 'user', $secretUsername);
            }
            if ($binding && !empty($binding['name'])) {
                $bindingName = $binding['name'];
            }

            $iface = $this->findInterfaceByName($client, $bindingName);
            if (!$iface) {
                $warning = 'PPPoE binding interface not found for counter reset.';
                return false;
            }

            $resetRequest = new RouterOS\Request('/interface/reset-counters');
            $resetRequest->setArgument('numbers', $iface['id']);
            $client->sendSync($resetRequest);
            return true;
        } catch (Throwable $e) {
            $warning = $e->getMessage();
            return false;
        }
    }

    function update_plan($old_name, $new_plan)
    {
        $mikrotik = $this->info($new_plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $warning = '';
        if (!$this->syncPppProfile($client, $new_plan, $warning)) {
            throw new Exception(
                $warning !== ''
                    ? $warning
                    : ('Failed to update PPPoE profile for plan: ' . ($new_plan['name_plan'] ?? '-'))
            );
        }
    }

    function remove_plan($plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=.id',
            RouterOS\Query::where('name', $plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    function add_pool($pool){
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $mikrotik = $this->info($pool['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $addRequest = new RouterOS\Request('/ip/pool/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $pool['pool_name'])
                ->setArgument('ranges', $pool['range_ip'])
        );
    }

    function update_pool($old_pool, $new_pool){
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $mikrotik = $this->info($new_pool['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=.id',
            RouterOS\Query::where('name', $old_pool['pool_name'])
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($poolID)) {
            $this->add_pool($new_pool);
        } else {
            $setRequest = new RouterOS\Request('/ip/pool/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $poolID)
                    ->setArgument('name', $new_pool['pool_name'])
                    ->setArgument('ranges', $new_pool['range_ip'])
            );
        }
    }

    function remove_pool($pool){
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $mikrotik = $this->info($pool['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=.id',
            RouterOS\Query::where('name', $pool['pool_name'])
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/pool/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $poolID)
        );
    }


    function online_customer($customer, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ppp active print',
            RouterOS\Query::where('name', $customer['username'])
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        if(empty($id)){
            $printRequest = new RouterOS\Request(
                '/ppp active print',
                RouterOS\Query::where('name', $customer['pppoe_username'])
            );
            $id = $client->sendSync($printRequest)->getProperty('.id');
        }
        return $id;
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
        return new RouterOS\Client($iport[0], $user, $pass, ($iport[1]) ? $iport[1] : null);
    }

    function removePpoeUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        //$printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ppp/secret/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    function addPpoeUser($client, $plan, $customer, $isExp = false)
    {
        $setRequest = new RouterOS\Request('/ppp/secret/add');
        $setRequest->setArgument('service', 'pppoe');
        $setRequest->setArgument('profile', $plan['name_plan']);
        $setRequest->setArgument('comment', $this->resolvePppoeComment($customer));
        if (!empty($customer['pppoe_password'])) {
            $setRequest->setArgument('password', $customer['pppoe_password']);
        } else {
            $setRequest->setArgument('password', $customer['password']);
        }
        $setRequest->setArgument('name', $this->resolveSecretUsername($customer));
        if (!empty($customer['pppoe_ip']) && !$isExp) {
            $setRequest->setArgument('remote-address', $customer['pppoe_ip']);
        }
        $client->sendSync($setRequest);
    }

    function removePpoeActive($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ppp/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/active/remove');
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

    function addIpToAddressList($client, $ip, $listName, $comment = '')
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/firewall/address-list/add');
        $client->sendSync(
            $addRequest
                ->setArgument('address', $ip)
                ->setArgument('comment', $comment)
                ->setArgument('list', $listName)
        );
    }

    function removeIpFromAddressList($client, $ip)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip firewall address-list print .proplist=.id',
            RouterOS\Query::where('address', $ip)
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/firewall/address-list/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }
}
