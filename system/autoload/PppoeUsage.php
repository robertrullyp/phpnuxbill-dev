<?php

class PppoeUsage
{
    private static $storageReady = null;
    const SOURCE_RESET_SCHEDULE = 'reset_schedule';
    const SOURCE_RESET_DONE = 'reset_done';
    const SOURCE_RESET_SKIPPED = 'reset_skipped';

    public static function normalizePlanType($type)
    {
        return strtoupper(trim((string) $type));
    }

    public static function isSupportedPlan($plan)
    {
        $type = self::normalizePlanType($plan['type'] ?? '');
        $device = strtolower(trim((string) ($plan['device'] ?? '')));
        $router = strtolower(trim((string) ($plan['routers'] ?? '')));
        $isRadius = (int) ($plan['is_radius'] ?? 0) === 1;

        $isDirectRouter = !$isRadius && $router !== '' && $router !== 'radius';
        $isPppoe = ($type === 'PPPOE' && $device === 'mikrotikpppoe');
        $isHotspot = ($type === 'HOTSPOT' && $device === 'mikrotikhotspot');

        return $isDirectRouter && ($isPppoe || $isHotspot);
    }

    public static function isStorageReady()
    {
        if (self::$storageReady !== null) {
            return self::$storageReady;
        }

        try {
            $db = ORM::get_db();
            if (!$db) {
                self::$storageReady = false;
                return false;
            }

            $requiredTables = [
                'tbl_recharge_usage_cycles',
                'tbl_recharge_usage_samples',
            ];
            foreach ($requiredTables as $table) {
                $stmt = $db->prepare("SHOW TABLES LIKE :table");
                $stmt->execute([':table' => $table]);
                if (!$stmt->fetch(PDO::FETCH_NUM)) {
                    self::$storageReady = false;
                    return false;
                }
            }

            $requiredRechargeColumns = [
                'usage_tx_bytes',
                'usage_rx_bytes',
            ];
            foreach ($requiredRechargeColumns as $column) {
                $stmt = $db->prepare("SHOW COLUMNS FROM `tbl_user_recharges` LIKE :column");
                $stmt->execute([':column' => $column]);
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    self::$storageReady = false;
                    return false;
                }
            }

            $requiredCycleColumns = [
                'recharge_id',
                'transaction_id',
                'customer_id',
                'plan_id',
                'router_name',
                'type',
                'binding_name',
                'binding_user',
                'started_at',
                'expires_at',
                'status',
                'usage_tx_bytes',
                'usage_rx_bytes',
                'last_counter_tx',
                'last_counter_rx',
                'last_sample_at',
            ];
            foreach ($requiredCycleColumns as $column) {
                $stmt = $db->prepare("SHOW COLUMNS FROM `tbl_recharge_usage_cycles` LIKE :column");
                $stmt->execute([':column' => $column]);
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    self::$storageReady = false;
                    return false;
                }
            }

            $requiredSampleColumns = [
                'cycle_id',
                'recharge_id',
                'sample_at',
                'counter_tx',
                'counter_rx',
                'delta_tx',
                'delta_rx',
                'usage_tx_total',
                'usage_rx_total',
                'source',
            ];
            foreach ($requiredSampleColumns as $column) {
                $stmt = $db->prepare("SHOW COLUMNS FROM `tbl_recharge_usage_samples` LIKE :column");
                $stmt->execute([':column' => $column]);
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    self::$storageReady = false;
                    return false;
                }
            }

            self::$storageReady = true;
            return true;
        } catch (Throwable $e) {
            self::$storageReady = false;
            return false;
        }
    }

    public static function resolveSecretUsername($customer)
    {
        return self::resolveUsageIdentity($customer, 'PPPOE');
    }

    public static function resolveUsageIdentity($customer, $planType = 'PPPOE')
    {
        $planType = self::normalizePlanType($planType);
        $username = trim((string) ($customer['username'] ?? ''));
        if ($planType === 'HOTSPOT') {
            return $username;
        }

        $pppoe = trim((string) ($customer['pppoe_username'] ?? ''));
        if ($pppoe !== '') {
            return $pppoe;
        }
        return $username;
    }

    public static function resolveBindingName($secretUsername, $planType = 'PPPOE')
    {
        $planType = self::normalizePlanType($planType);
        $safeUsername = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) $secretUsername);
        $safeUsername = trim((string) $safeUsername, '_');
        if ($safeUsername === '') {
            $safeUsername = 'user';
        }

        if ($planType === 'HOTSPOT') {
            if (strlen($safeUsername) > 63) {
                $safeUsername = substr($safeUsername, 0, 63);
            }
            return $safeUsername;
        }

        $maxSuffixLength = 57;
        if (strlen($safeUsername) > $maxSuffixLength) {
            $safeUsername = substr($safeUsername, 0, $maxSuffixLength);
        }
        return 'pppoe-' . $safeUsername;
    }

    public static function toDateTime($date, $time)
    {
        $date = trim((string) $date);
        $time = trim((string) $time);
        if ($date === '') {
            return date('Y-m-d H:i:s');
        }
        if ($time === '') {
            $time = '00:00:00';
        }
        return $date . ' ' . $time;
    }

    public static function normalizeDateTime($value, $fallback = '')
    {
        $value = trim((string) $value);
        if ($value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $value)) {
            if (strlen($value) === 10) {
                return $value . ' 00:00:00';
            }
            return $value;
        }

        $fallback = trim((string) $fallback);
        if ($fallback !== '') {
            return self::normalizeDateTime($fallback, '');
        }

        return date('Y-m-d H:i:s');
    }

    public static function closeOpenCyclesForScope($customerId, $routerName, $type, $endedAt = null, $excludeCycleId = 0)
    {
        if (!self::isStorageReady()) {
            return;
        }

        $customerId = (int) $customerId;
        $routerName = trim((string) $routerName);
        $type = strtoupper(trim((string) $type));
        if ($customerId < 1 || $routerName === '' || $type === '') {
            return;
        }
        if ($endedAt === null || trim((string) $endedAt) === '') {
            $endedAt = date('Y-m-d H:i:s');
        }

        $query = ORM::for_table('tbl_recharge_usage_cycles')
            ->where('customer_id', $customerId)
            ->where('router_name', $routerName)
            ->where('type', $type)
            ->where('status', 'open');

        if ((int) $excludeCycleId > 0) {
            $query->where_not_equal('id', (int) $excludeCycleId);
        }

        $rows = $query->find_many();
        foreach ($rows as $row) {
            $row->status = 'closed';
            $row->ended_at = $endedAt;
            $row->updated_at = date('Y-m-d H:i:s');
            $row->save();
        }
    }

    public static function closeCycleByRechargeId($rechargeId, $endedAt = null)
    {
        if (!self::isStorageReady()) {
            return;
        }

        $rechargeId = (int) $rechargeId;
        if ($rechargeId < 1) {
            return;
        }
        if ($endedAt === null || trim((string) $endedAt) === '') {
            $endedAt = date('Y-m-d H:i:s');
        }

        $rows = ORM::for_table('tbl_recharge_usage_cycles')
            ->where('recharge_id', $rechargeId)
            ->where('status', 'open')
            ->find_many();

        foreach ($rows as $row) {
            $row->status = 'closed';
            $row->ended_at = $endedAt;
            $row->updated_at = date('Y-m-d H:i:s');
            $row->save();
        }
    }

    public static function closeCycleById($cycleId, $endedAt = null)
    {
        if (!self::isStorageReady()) {
            return false;
        }

        $cycleId = (int) $cycleId;
        if ($cycleId < 1) {
            return false;
        }
        if ($endedAt === null || trim((string) $endedAt) === '') {
            $endedAt = date('Y-m-d H:i:s');
        }

        $row = ORM::for_table('tbl_recharge_usage_cycles')->find_one($cycleId);
        if (!$row) {
            return false;
        }

        $row->status = 'closed';
        $row->ended_at = $endedAt;
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();

        return true;
    }

    public static function getOpenCycleByRechargeId($rechargeId)
    {
        if (!self::isStorageReady()) {
            return null;
        }

        $rechargeId = (int) $rechargeId;
        if ($rechargeId < 1) {
            return null;
        }

        return ORM::for_table('tbl_recharge_usage_cycles')
            ->where('recharge_id', $rechargeId)
            ->where('status', 'open')
            ->order_by_desc('id')
            ->find_one();
    }

    public static function getLatestCycleByRechargeId($rechargeId)
    {
        if (!self::isStorageReady()) {
            return null;
        }

        $rechargeId = (int) $rechargeId;
        if ($rechargeId < 1) {
            return null;
        }

        return ORM::for_table('tbl_recharge_usage_cycles')
            ->where('recharge_id', $rechargeId)
            ->order_by_desc('id')
            ->find_one();
    }

    public static function getCycleById($cycleId)
    {
        if (!self::isStorageReady()) {
            return null;
        }

        $cycleId = (int) $cycleId;
        if ($cycleId < 1) {
            return null;
        }

        return ORM::for_table('tbl_recharge_usage_cycles')->find_one($cycleId);
    }

    public static function openActivationCycle($customer, $plan, $recharge, $transaction, $baseline = [], $note = '')
    {
        if (!self::isStorageReady()) {
            return null;
        }

        $customerId = (int) ($customer['id'] ?? 0);
        $rechargeId = (int) ($recharge['id'] ?? 0);
        $transactionId = (int) ($transaction['id'] ?? 0);
        $planId = (int) ($plan['id'] ?? 0);
        $routerName = trim((string) ($recharge['routers'] ?? ($plan['routers'] ?? '')));
        $type = self::normalizePlanType($recharge['type'] ?? ($plan['type'] ?? 'PPPOE'));

        if ($customerId < 1 || $rechargeId < 1 || $planId < 1 || $routerName === '' || $type === '') {
            return null;
        }

        $startedAt = self::toDateTime($recharge['recharged_on'] ?? date('Y-m-d'), $recharge['recharged_time'] ?? date('H:i:s'));
        $expiresAt = self::toDateTime($recharge['expiration'] ?? '', $recharge['time'] ?? '');
        $now = date('Y-m-d H:i:s');

        self::closeOpenCyclesForScope($customerId, $routerName, $type, $startedAt);

        $secretUser = trim((string) ($baseline['binding_user'] ?? self::resolveUsageIdentity($customer, $type)));
        $bindingName = trim((string) ($baseline['binding_name'] ?? self::resolveBindingName($secretUser, $type)));
        $counterTx = max(0, (int) ($baseline['tx'] ?? 0));
        $counterRx = max(0, (int) ($baseline['rx'] ?? 0));

        $cycle = ORM::for_table('tbl_recharge_usage_cycles')->create();
        $cycle->recharge_id = $rechargeId;
        $cycle->transaction_id = $transactionId;
        $cycle->customer_id = $customerId;
        $cycle->plan_id = $planId;
        $cycle->router_name = $routerName;
        $cycle->type = $type;
        $cycle->binding_name = $bindingName;
        $cycle->binding_user = $secretUser;
        $cycle->started_at = $startedAt;
        $cycle->expires_at = $expiresAt;
        $cycle->status = 'open';
        $cycle->usage_tx_bytes = 0;
        $cycle->usage_rx_bytes = 0;
        $cycle->last_counter_tx = $counterTx;
        $cycle->last_counter_rx = $counterRx;
        $cycle->last_sample_at = $now;
        $cycle->note = trim((string) $note);
        $cycle->created_at = $now;
        $cycle->updated_at = $now;
        $cycle->save();

        self::updateRechargeTotals($rechargeId, 0, 0);
        self::insertSample($cycle, $counterTx, $counterRx, 0, 0, $now, 'activation', trim((string) $note));
        self::scheduleCounterReset($rechargeId, $expiresAt, 'Auto-scheduled at activation expiry', (int) ($cycle['id'] ?? 0));

        return $cycle;
    }

    public static function ensureOpenCycleForRecharge($recharge, $plan = null, $customer = null)
    {
        if (!self::isStorageReady()) {
            return null;
        }

        $rechargeId = (int) ($recharge['id'] ?? 0);
        if ($rechargeId < 1) {
            return null;
        }

        $cycle = self::getOpenCycleByRechargeId($rechargeId);
        if ($cycle) {
            return $cycle;
        }

        if ($plan === null || $customer === null) {
            $planId = (int) ($recharge['plan_id'] ?? 0);
            if ($plan === null && $planId > 0) {
                $plan = ORM::for_table('tbl_plans')->find_one($planId);
            }
            if ($customer === null) {
                $customerId = (int) ($recharge['customer_id'] ?? 0);
                if ($customerId > 0) {
                    $customer = ORM::for_table('tbl_customers')->find_one($customerId);
                }
            }
        }

        if (!$plan || !$customer) {
            return null;
        }

        $transaction = ORM::for_table('tbl_transactions')
            ->where('user_id', (int) ($recharge['customer_id'] ?? 0))
            ->where('plan_name', (string) ($recharge['namebp'] ?? ''))
            ->where('routers', (string) ($recharge['routers'] ?? ''))
            ->order_by_desc('id')
            ->find_one();

        $transactionData = $transaction ? $transaction->as_array() : ['id' => 0];

        $usageType = self::normalizePlanType($recharge['type'] ?? ($plan['type'] ?? 'PPPOE'));
        $usageIdentity = self::resolveUsageIdentity($customer, $usageType);
        return self::openActivationCycle($customer, $plan, $recharge, $transactionData, [
            'tx' => (int) ($recharge['usage_tx_bytes'] ?? 0),
            'rx' => (int) ($recharge['usage_rx_bytes'] ?? 0),
            'binding_user' => $usageIdentity,
            'binding_name' => self::resolveBindingName($usageIdentity, $usageType),
        ], 'Auto-created by cron collector');
    }

    public static function recordSample($cycle, $counterTx, $counterRx, $sampleAt = null, $source = 'cron', $note = '')
    {
        if (!self::isStorageReady() || !$cycle) {
            return false;
        }

        $counterTx = max(0, (int) $counterTx);
        $counterRx = max(0, (int) $counterRx);
        $sampleAt = ($sampleAt === null || trim((string) $sampleAt) === '') ? date('Y-m-d H:i:s') : trim((string) $sampleAt);

        $lastTx = max(0, (int) ($cycle['last_counter_tx'] ?? 0));
        $lastRx = max(0, (int) ($cycle['last_counter_rx'] ?? 0));

        $deltaTx = ($counterTx >= $lastTx) ? ($counterTx - $lastTx) : $counterTx;
        $deltaRx = ($counterRx >= $lastRx) ? ($counterRx - $lastRx) : $counterRx;

        $newUsageTx = max(0, (int) ($cycle['usage_tx_bytes'] ?? 0) + $deltaTx);
        $newUsageRx = max(0, (int) ($cycle['usage_rx_bytes'] ?? 0) + $deltaRx);

        $cycle->usage_tx_bytes = $newUsageTx;
        $cycle->usage_rx_bytes = $newUsageRx;
        $cycle->last_counter_tx = $counterTx;
        $cycle->last_counter_rx = $counterRx;
        $cycle->last_sample_at = $sampleAt;
        $cycle->updated_at = date('Y-m-d H:i:s');
        $cycle->save();

        self::updateRechargeTotals((int) $cycle['recharge_id'], $newUsageTx, $newUsageRx);
        self::insertSample($cycle, $counterTx, $counterRx, $deltaTx, $deltaRx, $sampleAt, $source, $note);

        return true;
    }

    public static function updateRechargeTotals($rechargeId, $usageTxBytes, $usageRxBytes)
    {
        if (!self::isStorageReady()) {
            return;
        }

        $rechargeId = (int) $rechargeId;
        if ($rechargeId < 1) {
            return;
        }

        $row = ORM::for_table('tbl_user_recharges')->find_one($rechargeId);
        if ($row) {
            $row->usage_tx_bytes = max(0, (int) $usageTxBytes);
            $row->usage_rx_bytes = max(0, (int) $usageRxBytes);
            $row->save();
        }
    }

    public static function scheduleCounterReset($rechargeId, $dueAt, $note = '', $cycleId = 0)
    {
        if (!self::isStorageReady()) {
            return false;
        }

        $rechargeId = (int) $rechargeId;
        if ($rechargeId < 1) {
            return false;
        }

        $dueAt = self::normalizeDateTime($dueAt);
        $cycleId = max(0, (int) $cycleId);
        $note = substr(trim((string) $note), 0, 255);

        $existing = ORM::for_table('tbl_recharge_usage_samples')
            ->where('recharge_id', $rechargeId)
            ->where('sample_at', $dueAt)
            ->where_in('source', [
                self::SOURCE_RESET_SCHEDULE,
                self::SOURCE_RESET_DONE,
                self::SOURCE_RESET_SKIPPED,
            ])
            ->find_one();
        if ($existing) {
            return true;
        }

        $schedule = ORM::for_table('tbl_recharge_usage_samples')->create();
        $schedule->cycle_id = $cycleId;
        $schedule->recharge_id = $rechargeId;
        $schedule->sample_at = $dueAt;
        $schedule->counter_tx = 0;
        $schedule->counter_rx = 0;
        $schedule->delta_tx = 0;
        $schedule->delta_rx = 0;
        $schedule->usage_tx_total = 0;
        $schedule->usage_rx_total = 0;
        $schedule->source = self::SOURCE_RESET_SCHEDULE;
        $schedule->note = $note;
        $schedule->created_at = date('Y-m-d H:i:s');
        $schedule->save();

        return true;
    }

    public static function cancelPendingCounterResetSchedules($rechargeId, $fromAt = null)
    {
        if (!self::isStorageReady()) {
            return 0;
        }

        $rechargeId = (int) $rechargeId;
        if ($rechargeId < 1) {
            return 0;
        }

        $query = ORM::for_table('tbl_recharge_usage_samples')
            ->where('recharge_id', $rechargeId)
            ->where('source', self::SOURCE_RESET_SCHEDULE);

        if ($fromAt !== null && trim((string) $fromAt) !== '') {
            $query->where_gte('sample_at', self::normalizeDateTime($fromAt));
        }

        $rows = $query->find_many();
        $count = 0;
        foreach ($rows as $row) {
            $row->source = self::SOURCE_RESET_SKIPPED;
            $existingNote = trim((string) ($row['note'] ?? ''));
            $suffix = 'Canceled by system change';
            $row->note = substr($existingNote === '' ? $suffix : ($existingNote . ' | ' . $suffix), 0, 255);
            $row->save();
            $count++;
        }

        return $count;
    }

    public static function rescheduleCounterReset($rechargeId, $dueAt, $note = '', $cycleId = 0)
    {
        if (!self::isStorageReady()) {
            return false;
        }

        $rechargeId = (int) $rechargeId;
        if ($rechargeId < 1) {
            return false;
        }

        self::cancelPendingCounterResetSchedules($rechargeId);
        return self::scheduleCounterReset($rechargeId, $dueAt, $note, $cycleId);
    }

    public static function getDueCounterResetSchedules($limit = 100)
    {
        if (!self::isStorageReady()) {
            return [];
        }

        $limit = (int) $limit;
        if ($limit < 1) {
            $limit = 100;
        } elseif ($limit > 1000) {
            $limit = 1000;
        }

        return ORM::for_table('tbl_recharge_usage_samples')
            ->where('source', self::SOURCE_RESET_SCHEDULE)
            ->where_lte('sample_at', date('Y-m-d H:i:s'))
            ->order_by_asc('sample_at')
            ->order_by_asc('id')
            ->limit($limit)
            ->find_many();
    }

    public static function finalizeCounterResetSchedule($scheduleId, $status = 'done', $detail = '')
    {
        if (!self::isStorageReady()) {
            return false;
        }

        $scheduleId = (int) $scheduleId;
        if ($scheduleId < 1) {
            return false;
        }

        $row = ORM::for_table('tbl_recharge_usage_samples')->find_one($scheduleId);
        if (!$row) {
            return false;
        }

        if ((string) ($row['source'] ?? '') !== self::SOURCE_RESET_SCHEDULE) {
            return false;
        }

        $status = strtolower(trim((string) $status));
        $row->source = ($status === 'skip' || $status === 'skipped')
            ? self::SOURCE_RESET_SKIPPED
            : self::SOURCE_RESET_DONE;

        $existingNote = trim((string) ($row['note'] ?? ''));
        $detail = trim((string) $detail);
        if ($detail !== '') {
            $row->note = substr(trim($existingNote === '' ? $detail : ($existingNote . ' | ' . $detail)), 0, 255);
        }
        $row->save();

        return true;
    }

    private static function insertSample($cycle, $counterTx, $counterRx, $deltaTx, $deltaRx, $sampleAt, $source, $note)
    {
        if (!self::isStorageReady()) {
            return;
        }

        $sample = ORM::for_table('tbl_recharge_usage_samples')->create();
        $sample->cycle_id = (int) $cycle['id'];
        $sample->recharge_id = (int) $cycle['recharge_id'];
        $sample->sample_at = $sampleAt;
        $sample->counter_tx = max(0, (int) $counterTx);
        $sample->counter_rx = max(0, (int) $counterRx);
        $sample->delta_tx = max(0, (int) $deltaTx);
        $sample->delta_rx = max(0, (int) $deltaRx);
        $sample->usage_tx_total = max(0, (int) ($cycle['usage_tx_bytes'] ?? 0));
        $sample->usage_rx_total = max(0, (int) ($cycle['usage_rx_bytes'] ?? 0));
        $sample->source = substr(trim((string) $source), 0, 24);
        $sample->note = substr(trim((string) $note), 0, 255);
        $sample->created_at = date('Y-m-d H:i:s');
        $sample->save();
    }
}
