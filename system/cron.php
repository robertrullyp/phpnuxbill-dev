<?php

include "../init.php";
$lockFile = "$CACHE_PATH/router_monitor.lock";

if (!is_dir($CACHE_PATH)) {
    echo "Directory '$CACHE_PATH' does not exist. Exiting...\n";
    exit;
}

$lock = fopen($lockFile, 'c');

if ($lock === false) {
    echo "Failed to open lock file. Exiting...\n";
    exit;
}

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Script is already running. Exiting...\n";
    fclose($lock);
    exit;
}


$isCli = true;
if (php_sapi_name() !== 'cli') {
    $isCli = false;
    echo "<pre>";
}
echo "PHP Time\t" . date('Y-m-d H:i:s') . "\n";
$res = ORM::raw_execute('SELECT NOW() AS WAKTU;');
$statement = ORM::get_last_statement();
$rows = [];
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    echo "MYSQL Time\t" . $row['WAKTU'] . "\n";
}

$_c = $config;

function resolvePlanForRechargeCron($rechargeRow)
{
    if (!$rechargeRow) {
        return null;
    }

    $planId = (int) ($rechargeRow['plan_id'] ?? 0);
    if ($planId > 0) {
        $plan = ORM::for_table('tbl_plans')->where('id', $planId)->find_one();
        if ($plan) {
            return $plan;
        }
    }

    $planFallbackName = trim((string) ($rechargeRow['namebp'] ?? ''));
    $planFallbackType = strtolower(trim((string) ($rechargeRow['type'] ?? '')));
    if ($planFallbackName !== '') {
        $planFallbackQuery = ORM::for_table('tbl_plans')->where('name_plan', $planFallbackName);
        if ($planFallbackType !== '') {
            $planFallbackQuery->where_raw('LOWER(TRIM(`type`)) = ?', [$planFallbackType]);
        }
        return $planFallbackQuery->order_by_desc('id')->find_one();
    }

    return null;
}

function collectPppoeUsageSampleForRecharge($rechargeRow, $planRow = null, $customerRow = null, $source = 'cron', $closeCycle = false)
{
    if (!class_exists('PppoeUsage') || !PppoeUsage::isStorageReady() || !$rechargeRow) {
        return;
    }

    $rechargeData = is_array($rechargeRow) ? $rechargeRow : $rechargeRow->as_array();
    $rechargeId = (int) ($rechargeData['id'] ?? 0);
    if ($rechargeId < 1) {
        return;
    }

    $plan = $planRow;
    if (!$plan) {
        $plan = resolvePlanForRechargeCron($rechargeData);
    }
    if (!$plan) {
        if ($closeCycle) {
            PppoeUsage::closeCycleByRechargeId($rechargeId, date('Y-m-d H:i:s'));
        }
        return;
    }

    $planData = is_array($plan) ? $plan : $plan->as_array();
    if (!PppoeUsage::isSupportedPlan($planData)) {
        if ($closeCycle) {
            PppoeUsage::closeCycleByRechargeId($rechargeId, date('Y-m-d H:i:s'));
        }
        return;
    }

    $customer = $customerRow;
    if (!$customer) {
        $customerId = (int) ($rechargeData['customer_id'] ?? 0);
        if ($customerId > 0) {
            $customer = ORM::for_table('tbl_customers')->where('id', $customerId)->find_one();
        }
    }
    if (!$customer) {
        if ($closeCycle) {
            PppoeUsage::closeCycleByRechargeId($rechargeId, date('Y-m-d H:i:s'));
        }
        return;
    }

    $customerData = is_array($customer) ? $customer : $customer->as_array();
    try {
        $cycle = PppoeUsage::ensureOpenCycleForRecharge($rechargeData, $planData, $customerData);
        if (!$cycle) {
            if ($closeCycle) {
                PppoeUsage::closeCycleByRechargeId($rechargeId, date('Y-m-d H:i:s'));
            }
            return;
        }

        $dvc = Package::getDevice($planData);
        if (!$dvc || !file_exists($dvc)) {
            _log('PPPoE usage collector warning: device file not found for plan ' . ($planData['name_plan'] ?? ''));
            if ($closeCycle) {
                PppoeUsage::closeCycleById((int) $cycle['id'], date('Y-m-d H:i:s'));
            }
            return;
        }

        require_once $dvc;
        $deviceClass = trim((string) ($planData['device'] ?? ''));
        if ($deviceClass === '' || !class_exists($deviceClass)) {
            _log('PPPoE usage collector warning: device class not found: ' . $deviceClass);
            if ($closeCycle) {
                PppoeUsage::closeCycleById((int) $cycle['id'], date('Y-m-d H:i:s'));
            }
            return;
        }

        $device = new $deviceClass();
        if (!method_exists($device, 'getPppoeBindingCounters')) {
            _log('PPPoE usage collector warning: getPppoeBindingCounters is not implemented by ' . $deviceClass);
            if ($closeCycle) {
                PppoeUsage::closeCycleById((int) $cycle['id'], date('Y-m-d H:i:s'));
            }
            return;
        }

        $warning = '';
        $bindingName = trim((string) ($cycle['binding_name'] ?? ''));
        $counters = $device->getPppoeBindingCounters($customerData, $planData, $warning, $bindingName);
        if (is_array($counters)) {
            if (!empty($counters['binding_name']) && (string) $cycle['binding_name'] !== (string) $counters['binding_name']) {
                $cycle->binding_name = (string) $counters['binding_name'];
                $cycle->updated_at = date('Y-m-d H:i:s');
                $cycle->save();
            }

            $sampleNote = ($source === 'expiry-final')
                ? 'Final sample before expiry'
                : 'Cron periodic sample';
            PppoeUsage::recordSample(
                $cycle,
                (int) ($counters['tx_byte'] ?? 0),
                (int) ($counters['rx_byte'] ?? 0),
                date('Y-m-d H:i:s'),
                $source,
                $sampleNote
            );
        } elseif ($warning !== '') {
            _log('PPPoE usage collector warning: ' . $warning);
        }

        if ($closeCycle) {
            PppoeUsage::closeCycleById((int) $cycle['id'], date('Y-m-d H:i:s'));
        }
    } catch (Throwable $e) {
        _log('PPPoE usage collector error: ' . $e->getMessage());
        if ($closeCycle) {
            PppoeUsage::closeCycleByRechargeId($rechargeId, date('Y-m-d H:i:s'));
        }
    }
}

function collectPppoeUsageFromActiveRecharges()
{
    if (!class_exists('PppoeUsage') || !PppoeUsage::isStorageReady()) {
        return;
    }

    $rows = ORM::for_table('tbl_user_recharges')
        ->where('status', 'on')
        ->where('type', 'PPPOE')
        ->order_by_desc('id')
        ->find_many();

    $latestRows = [];
    foreach ($rows as $row) {
        $scopeKey = (int) ($row['customer_id'] ?? 0) . '|' . trim((string) ($row['routers'] ?? '')) . '|' . strtoupper(trim((string) ($row['type'] ?? '')));
        if (isset($latestRows[$scopeKey])) {
            continue;
        }
        $latestRows[$scopeKey] = $row;
    }

    $processed = 0;
    foreach ($latestRows as $row) {
        $plan = resolvePlanForRechargeCron($row);
        if (!$plan) {
            continue;
        }
        $planData = $plan->as_array();
        if (!PppoeUsage::isSupportedPlan($planData)) {
            continue;
        }
        collectPppoeUsageSampleForRecharge($row, $plan, null, 'cron', false);
        $processed++;
    }

    echo "PPPoE usage sampled for {$processed} active scope(s)\n";
}

function processDuePppoeCounterResets()
{
    if (!class_exists('PppoeUsage') || !PppoeUsage::isStorageReady()) {
        return;
    }

    $schedules = PppoeUsage::getDueCounterResetSchedules(200);
    if (!is_array($schedules) || count($schedules) === 0) {
        echo "PPPoE counter reset schedules: 0 due\n";
        return;
    }

    $done = 0;
    $skipped = 0;
    $pending = 0;

    foreach ($schedules as $schedule) {
        $scheduleData = is_array($schedule) ? $schedule : $schedule->as_array();
        $scheduleId = (int) ($scheduleData['id'] ?? 0);
        $rechargeId = (int) ($scheduleData['recharge_id'] ?? 0);
        if ($scheduleId < 1 || $rechargeId < 1) {
            continue;
        }

        try {
            $recharge = ORM::for_table('tbl_user_recharges')->find_one($rechargeId);
            if (!$recharge) {
                PppoeUsage::finalizeCounterResetSchedule($scheduleId, 'skipped', 'Recharge not found');
                $skipped++;
                continue;
            }

            $plan = resolvePlanForRechargeCron($recharge);
            if (!$plan) {
                PppoeUsage::finalizeCounterResetSchedule($scheduleId, 'skipped', 'Plan not found');
                $skipped++;
                continue;
            }
            $planData = is_array($plan) ? $plan : $plan->as_array();
            if (!PppoeUsage::isSupportedPlan($planData)) {
                PppoeUsage::finalizeCounterResetSchedule($scheduleId, 'skipped', 'Unsupported plan/device for PPPoE reset');
                $skipped++;
                continue;
            }

            if (strtolower(trim((string) ($recharge['status'] ?? ''))) !== 'on') {
                PppoeUsage::finalizeCounterResetSchedule($scheduleId, 'skipped', 'Recharge inactive');
                $skipped++;
                continue;
            }

            $customer = ORM::for_table('tbl_customers')->where('id', (int) ($recharge['customer_id'] ?? 0))->find_one();
            if (!$customer) {
                PppoeUsage::finalizeCounterResetSchedule($scheduleId, 'skipped', 'Customer not found');
                $skipped++;
                continue;
            }
            $customerData = $customer->as_array();

            $dvc = Package::getDevice($planData);
            if (!$dvc || !file_exists($dvc)) {
                _log('PPPoE counter reset warning: device file not found for recharge #' . $rechargeId);
                $pending++;
                continue;
            }

            require_once $dvc;
            $deviceClass = trim((string) ($planData['device'] ?? ''));
            if ($deviceClass === '' || !class_exists($deviceClass)) {
                PppoeUsage::finalizeCounterResetSchedule($scheduleId, 'skipped', 'Device class not found: ' . $deviceClass);
                $skipped++;
                continue;
            }

            $device = new $deviceClass();
            if (!method_exists($device, 'resetPppoeBindingCounters')) {
                PppoeUsage::finalizeCounterResetSchedule($scheduleId, 'skipped', 'Device does not support resetPppoeBindingCounters');
                $skipped++;
                continue;
            }

            $bindingName = '';
            $cycleId = (int) ($scheduleData['cycle_id'] ?? 0);
            if ($cycleId > 0) {
                $cycle = PppoeUsage::getCycleById($cycleId);
                if ($cycle && !empty($cycle['binding_name'])) {
                    $bindingName = trim((string) $cycle['binding_name']);
                }
            }

            $warning = '';
            $resetOk = $device->resetPppoeBindingCounters($customerData, $planData, $warning, $bindingName);
            if ($resetOk) {
                PppoeUsage::finalizeCounterResetSchedule($scheduleId, 'done', 'Counter reset executed at ' . date('Y-m-d H:i:s'));
                $done++;
                continue;
            }

            if ($warning !== '') {
                _log('PPPoE counter reset warning: recharge #' . $rechargeId . ' - ' . $warning);
            } else {
                _log('PPPoE counter reset warning: recharge #' . $rechargeId . ' - unknown reset failure');
            }
            // Keep schedule pending for retry on next cron run.
            $pending++;
        } catch (Throwable $e) {
            _log('PPPoE counter reset error: ' . $e->getMessage());
            $pending++;
        }
    }

    echo "PPPoE counter reset schedules: done {$done}, skipped {$skipped}, pending {$pending}\n";
}


$unlimitedExpirationDate = '2099-12-31';
$unlimitedExpirationTime = '23:59:59';

// Normalize existing active rows for unlimited plans (validity <= 0) before expiry scan.
ORM::raw_execute(
    "UPDATE `tbl_user_recharges` AS `tur`
    SET `tur`.`expiration` = ?, `tur`.`time` = ?, `tur`.`status` = 'on'
    WHERE `tur`.`status` = 'on'
      AND EXISTS (
          SELECT 1
          FROM `tbl_plans` AS `p`
          WHERE (
                `p`.`id` = `tur`.`plan_id`
                OR (
                    `p`.`name_plan` = `tur`.`namebp`
                    AND LOWER(TRIM(COALESCE(`p`.`type`, ''))) = LOWER(TRIM(COALESCE(`tur`.`type`, '')))
                )
          )
            AND CAST(COALESCE(`p`.`validity`, 0) AS SIGNED) <= 0
      )
      AND (`tur`.`expiration` <> ? OR `tur`.`time` <> ?)",
    [$unlimitedExpirationDate, $unlimitedExpirationTime, $unlimitedExpirationDate, $unlimitedExpirationTime]
);

$d = ORM::for_table('tbl_user_recharges')
    ->table_alias('tur')
    ->select('tur.*')
    ->where('tur.status', 'on')
    ->where_lte('tur.expiration', date("Y-m-d"))
	    ->where_raw(
	        "NOT EXISTS (
	            SELECT 1
	            FROM `tbl_plans` AS `p`
	            WHERE (
	                `p`.`id` = `tur`.`plan_id`
	                OR (
	                    `p`.`name_plan` = `tur`.`namebp`
	                    AND LOWER(TRIM(COALESCE(`p`.`type`, ''))) = LOWER(TRIM(COALESCE(`tur`.`type`, '')))
	                )
	            )
	              AND CAST(COALESCE(`p`.`validity`, 0) AS SIGNED) <= 0
	        )"
	    )
	    ->find_many();
echo "Found " . count($d) . " user(s)\n";
run_hook('cronjob'); #HOOK
// Cleanup temporary WA media files (max 7 days retention)
Message::cleanupExpiredWhatsappMedia();
// Process WhatsApp queue
Message::processWhatsappQueue();
collectPppoeUsageFromActiveRecharges();

foreach ($d as $ds) {
    try {
        $date_now = strtotime(date("Y-m-d H:i:s"));
        $expiration = strtotime($ds['expiration'] . ' ' . $ds['time']);
        echo $ds['expiration'] . " : " . ($isCli ? $ds['username'] : Lang::maskText($ds['username']));

        if ($date_now >= $expiration) {
            // Fetch user recharge details
            $u = ORM::for_table('tbl_user_recharges')->where('id', $ds['id'])->find_one();
            if (!$u) {
                throw new Exception("User recharge record not found for ID: " . $ds['id']);
            }

            // Fetch customer details
            $c = ORM::for_table('tbl_customers')->where('id', $ds['customer_id'])->find_one();
            if (!$c) {
                $c = $u;
            }

            // Fetch plan details with fallback by name+type for legacy rows.
            $p = resolvePlanForRechargeCron($u);
            if (!$p) {
                throw new Exception("Plan not found for ID: " . $u['plan_id']);
            }

	            $planValidity = (int) ($p['validity'] ?? 0);
	            $isUnlimitedPlan = ($planValidity <= 0);
	            if ($isUnlimitedPlan) {
	                $u->expiration = $unlimitedExpirationDate;
	                $u->time = $unlimitedExpirationTime;
	                $u->status = 'on';
	                $u->save();
	                echo " : SKIP (UNLIMITED)\r\n";
	                continue;
	            }
	
	            // If a newer unlimited row is still active for the same service,
	            // close this old row without disconnecting the customer.
	            $hasUnlimitedSibling = ORM::for_table('tbl_user_recharges')
	                ->table_alias('tur2')
                ->select('tur2.id')
                ->where('tur2.customer_id', $u['customer_id'])
                ->where('tur2.status', 'on')
                ->where_not_equal('tur2.id', $u['id'])
                ->where('tur2.routers', $u['routers'])
                ->where('tur2.type', $u['type'])
	                ->where_raw(
	                    "EXISTS (
	                        SELECT 1
	                        FROM `tbl_plans` AS `p2`
	                        WHERE (
	                            `p2`.`id` = `tur2`.`plan_id`
	                            OR (
	                                `p2`.`name_plan` = `tur2`.`namebp`
	                                AND LOWER(TRIM(COALESCE(`p2`.`type`, ''))) = LOWER(TRIM(COALESCE(`tur2`.`type`, '')))
	                            )
	                        )
	                          AND CAST(COALESCE(`p2`.`validity`, 0) AS SIGNED) <= 0
	                    )"
	                )
	                ->find_one();
		            if ($hasUnlimitedSibling) {
                        if (class_exists('PppoeUsage') && PppoeUsage::isStorageReady()) {
                            PppoeUsage::closeCycleByRechargeId((int) $u['id'], date('Y-m-d H:i:s'));
                        }
		                $u->status = 'off';
		                $u->save();
		                echo " : SKIP (OLDER ROW, ACTIVE UNLIMITED EXISTS)\r\n";
		                continue;
		            }

            echo " : EXPIRED \r\n";
            collectPppoeUsageSampleForRecharge($u, $p, $c, 'expiry-final', true);

            $dvc = Package::getDevice($p);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $p['device'])->remove_customer($c, $p);
                } else {
                    throw new Exception("Cron error: Devices " . $p['device'] . "not found, cannot disconnect ".$c['username']."\n");
                }
            }

            // Send notification and update user status
            try {
                $textExpired = Lang::getNotifText('expired', [
                    'plan_id' => (int) ($p['id'] ?? 0),
                    'type' => (string) ($p['type'] ?? ''),
                ]);
                echo Message::sendPackageNotification(
                    $c,
                    $u['namebp'],
                    $p['price'],
                    Message::getMessageType($p['type'], $textExpired),
                    $config['user_notification_expired'],
                    'expired'
                ) . "\n";
                $u->status = 'off';
                $u->save();
            } catch (Throwable $e) {
                _log($e->getMessage());
                sendTelegram($e->getMessage());
                echo "Error: " . $e->getMessage() . "\n";
            }

            // Auto-renewal from deposit
            if ($config['enable_balance'] == 'yes' && $c['auto_renewal']) {
                [$bills, $add_cost] = User::getBills($ds['customer_id']);
                if ($add_cost != 0) {
                    $p['price'] += $add_cost;
                }

                if ($p && $c['balance'] >= $p['price']) {
                    if (Package::rechargeUser($ds['customer_id'], $ds['routers'], $p['id'], 'Customer', 'Balance')) {
                        Balance::min($ds['customer_id'], $p['price']);
                        echo "plan enabled: " . (string) $p['enabled'] . " | User balance: " . (string) $c['balance'] . " | price " . (string) $p['price'] . "\n";
                        echo "auto renewal Success\n";
                    } else {
                        echo "plan enabled: " . $p['enabled'] . " | User balance: " . $c['balance'] . " | price " . $p['price'] . "\n";
                        echo "auto renewal Failed\n";
                        Message::sendTelegram("FAILED RENEWAL #cron\n\n#u." . $c['username'] . " #buy #Hotspot \n" . $p['name_plan'] .
                            "\nRouter: " . $p['routers'] .
                            "\nPrice: " . $p['price']);
                    }
                } else {
                    echo "no renewal | plan enabled: " . (string) $p['enabled'] . " | User balance: " . (string) $c['balance'] . " | price " . (string) $p['price'] . "\n";
                }
            } else {
                echo "no renewal | balance" . $config['enable_balance'] . " auto_renewal " . $c['auto_renewal'] . "\n";
            }
        } else {
            echo " : ACTIVE \r\n";
        }
    } catch (Throwable $e) {
        // Catch any unexpected errors
        _log($e->getMessage());
        sendTelegram($e->getMessage());
        echo "Unexpected Error: " . $e->getMessage() . "\n";
    }
}

processDuePppoeCounterResets();

//Cek interim-update radiusrest
if ($config['frrest_interim_update'] != 0) {

    $r_a = ORM::for_table('rad_acct')
        ->whereRaw("BINARY acctstatustype = 'Start' OR acctstatustype = 'Interim-Update'")
        ->where_lte('dateAdded', date("Y-m-d H:i:s"))->find_many();

    foreach ($r_a as $ra) {
        $interval = $_c['frrest_interim_update'] * 60;
        $timeUpdate = strtotime($ra['dateAdded']) + $interval;
        $timeNow = strtotime(date("Y-m-d H:i:s"));
        if ($timeNow >= $timeUpdate) {
            $ra->acctstatustype = 'Stop';
            $ra->save();
        }
    }
}

if ($config['router_check']) {
    echo "Checking router status...\n";
    $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
    if (!$routers) {
        echo "No active routers found in the database.\n";
        flock($lock, LOCK_UN);
        fclose($lock);
        unlink($lockFile);
        exit;
    }

    $offlineRouters = [];
    $errors = [];

    foreach ($routers as $router) {
        // check if custom port
        if (strpos($router->ip_address, ':') === false) {
            $ip = $router->ip_address;
            $port = 8728;
        } else {
            [$ip, $port] = explode(':', $router->ip_address);
        }
        $isOnline = false;

        try {
            $timeout = 5;
            if (is_callable('fsockopen') && false === stripos(ini_get('disable_functions'), 'fsockopen')) {
                $fsock = @fsockopen($ip, $port, $errno, $errstr, $timeout);
                if ($fsock) {
                    fclose($fsock);
                    $isOnline = true;
                } else {
                    throw new Exception("Unable to connect to $ip on port $port using fsockopen: $errstr ($errno)");
                }
            } elseif (is_callable('stream_socket_client') && false === stripos(ini_get('disable_functions'), 'stream_socket_client')) {
                $connection = @stream_socket_client("$ip:$port", $errno, $errstr, $timeout);
                if ($connection) {
                    fclose($connection);
                    $isOnline = true;
                } else {
                    throw new Exception("Unable to connect to $ip on port $port using stream_socket_client: $errstr ($errno)");
                }
            } else {
                throw new Exception("Neither fsockopen nor stream_socket_client are enabled on the server.");
            }
        } catch (Exception $e) {
            _log($e->getMessage());
            $errors[] = "Error with router $ip: " . $e->getMessage();
        }

        if ($isOnline) {
            $router->last_seen = date('Y-m-d H:i:s');
            $router->status = 'Online';
        } else {
            $router->status = 'Offline';
            $offlineRouters[] = $router;
        }

        $router->save();
    }

    if (!empty($offlineRouters)) {
        $message = "Dear Administrator,\n";
        $message .= "The following routers are offline:\n";
        foreach ($offlineRouters as $router) {
            $message .= "Name: {$router->name}, IP: {$router->ip_address}, Last Seen: {$router->last_seen}\n";
        }
        $message .= "\nPlease check the router's status and take appropriate action.\n\nBest regards,\nRouter Monitoring System";

        $adminEmail = $config['mail_from'];
        $subject = "Router Offline Alert";
        Message::SendEmail($adminEmail, $subject, $message);
        sendTelegram($message);
    }

    if (!empty($errors)) {
        $message = "The following errors occurred during router monitoring:\n";
        foreach ($errors as $error) {
            $message .= "$error\n";
        }

        $adminEmail = $config['mail_from'];
        $subject = "Router Monitoring Error Alert";
        Message::SendEmail($adminEmail, $subject, $message);
        sendTelegram($message);
    }
    echo "Router monitoring finished checking.\n";
}

flock($lock, LOCK_UN);
fclose($lock);
unlink($lockFile);

$timestampFile = "$UPLOAD_PATH/cron_last_run.txt";
file_put_contents($timestampFile, time());

run_hook('cronjob_end'); #HOOK
echo "Cron job finished and completed successfully.\n";
