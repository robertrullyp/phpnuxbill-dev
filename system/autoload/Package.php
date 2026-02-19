<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/



class Package
{
    /**
     * Determine if a plan is visible to a specific customer.
     */
    public static function isPlanVisibleToCustomer($plan, $customerId)
    {
        if (!$plan) {
            return false;
        }
        // Default to visible if no visibility column yet (backward compatibility)
        if (!isset($plan['visibility']) || $plan['visibility'] === 'all') {
            return true;
        }
        if ($plan['visibility'] === 'custom') {
            $row = ORM::for_table('tbl_plan_customers')
                ->where('plan_id', $plan['id'])
                ->where('customer_id', $customerId)
                ->find_one();
            return !empty($row);
        }
        if ($plan['visibility'] === 'exclude') {
            $row = ORM::for_table('tbl_plan_customers')
                ->where('plan_id', $plan['id'])
                ->where('customer_id', $customerId)
                ->find_one();
            // excluded if mapping exists
            return empty($row);
        }
        return true;
    }

    /**
     * Filter a list of plans to only those visible to a customer.
     * @param array<int,array|\IdiormResultSet|\ORM> $plans
     * @return array
     */
    public static function filterPlansForCustomer($plans, $customerId)
    {
        if (empty($plans)) {
            return [];
        }
        // Collect IDs and visibility flags if present
        $ids = [];
        $customIds = [];
        $excludeIds = [];
        foreach ($plans as $p) {
            $pid = is_array($p) ? $p['id'] : $p->id;
            $ids[] = $pid;
            $visibility = is_array($p) ? ($p['visibility'] ?? 'all') : ($p->visibility ?? 'all');
            if ($visibility === 'custom') {
                $customIds[] = $pid;
            } elseif ($visibility === 'exclude') {
                $excludeIds[] = $pid;
            }
        }
        $allowedCustom = [];
        $excluded = [];
        if (!empty($customIds)) {
            $rows = ORM::for_table('tbl_plan_customers')
                ->where_in('plan_id', $customIds)
                ->where('customer_id', $customerId)
                ->find_array();
            if ($rows) {
                $allowedCustom = array_column($rows, 'plan_id');
            }
        }
        if (!empty($excludeIds)) {
            $rows = ORM::for_table('tbl_plan_customers')
                ->where_in('plan_id', $excludeIds)
                ->where('customer_id', $customerId)
                ->find_array();
            if ($rows) {
                $excluded = array_column($rows, 'plan_id');
            }
        }
        $out = [];
        foreach ($plans as $p) {
            $pid = is_array($p) ? $p['id'] : $p->id;
            $visibility = is_array($p) ? ($p['visibility'] ?? 'all') : ($p->visibility ?? 'all');
            if ($visibility === 'all' || ($visibility === 'custom' && in_array($pid, $allowedCustom)) || ($visibility === 'exclude' && !in_array($pid, $excluded))) {
                $out[] = $p;
            }
        }
        return $out;
    }

    public static function getPlanOptionsList($excludeId = null)
    {
        $query = ORM::for_table('tbl_plans')
            ->select_many('id', 'name_plan', 'type')
            ->order_by_asc('name_plan');
        if ($excludeId !== null) {
            $query->where_not_equal('id', (int) $excludeId);
        }
        return $query->find_array();
    }

    public static function getLinkedPlanIds($planId)
    {
        $planId = (int) $planId;
        if ($planId <= 0) {
            return [];
        }
        $rows = ORM::for_table('tbl_plan_links')
            ->select_many('plan_id', 'linked_plan_id')
            ->where_any_is([
                ['plan_id' => $planId],
                ['linked_plan_id' => $planId],
            ])
            ->find_array();
        if (empty($rows)) {
            return [];
        }
        $ids = [];
        foreach ($rows as $row) {
            $rowPlan = (int) $row['plan_id'];
            $rowLinked = (int) $row['linked_plan_id'];
            if ($rowPlan === $planId && $rowLinked > 0) {
                $ids[] = $rowLinked;
            } elseif ($rowLinked === $planId && $rowPlan > 0) {
                $ids[] = $rowPlan;
            }
        }
        return array_values(array_unique($ids));
    }

    public static function syncLinkedPlans($planId, ?array $linkedPlanIds)
    {
        $planId = (int) $planId;
        if ($planId <= 0) {
            return;
        }

        $linkedPlanIds = self::normalizeLinkedPlanIds($linkedPlanIds);
        $linkedPlanIds = array_filter($linkedPlanIds, function ($id) use ($planId) {
            return $id !== $planId;
        });

        self::removePlanLinks($planId);

        if (empty($linkedPlanIds)) {
            return;
        }

        $validIds = ORM::for_table('tbl_plans')
            ->select('id')
            ->where_in('id', $linkedPlanIds)
            ->find_array();

        if (empty($validIds)) {
            return;
        }

        foreach ($validIds as $row) {
            $linkedId = (int) $row['id'];
            self::createPlanLinkRecord($planId, $linkedId);
            self::createPlanLinkRecord($linkedId, $planId);
        }
    }

    public static function removePlanLinks($planId)
    {
        $planId = (int) $planId;
        if ($planId <= 0) {
            return;
        }
        ORM::for_table('tbl_plan_links')->where('plan_id', $planId)->delete_many();
        ORM::for_table('tbl_plan_links')->where('linked_plan_id', $planId)->delete_many();
    }

    protected static function createPlanLinkRecord($planId, $linkedPlanId)
    {
        $planId = (int) $planId;
        $linkedPlanId = (int) $linkedPlanId;
        if ($planId <= 0 || $linkedPlanId <= 0 || $planId === $linkedPlanId) {
            return;
        }
        $exists = ORM::for_table('tbl_plan_links')
            ->where('plan_id', $planId)
            ->where('linked_plan_id', $linkedPlanId)
            ->find_one();
        if ($exists) {
            return;
        }
        $row = ORM::for_table('tbl_plan_links')->create();
        $row->plan_id = $planId;
        $row->linked_plan_id = $linkedPlanId;
        $row->save();
    }

    public static function normalizeVisibility($visibility)
    {
        if ($visibility === null) {
            return null;
        }

        if (is_string($visibility)) {
            $visibility = trim($visibility);
            if ($visibility === '' || strtolower($visibility) === 'null') {
                return null;
            }
        }

        $validOptions = ['all', 'custom', 'exclude'];
        return in_array($visibility, $validOptions, true) ? $visibility : null;
    }

    public static function normalizeLinkedPlanIds($linkedPlans): array
    {
        if ($linkedPlans === null) {
            return [];
        }

        if (!is_array($linkedPlans)) {
            $linkedPlans = [$linkedPlans];
        }

        $ids = [];
        foreach ($linkedPlans as $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $ids[] = (int) $value;
        }

        $ids = array_values(array_unique($ids));

        return array_filter($ids, function ($id) {
            return $id > 0;
        });
    }

    protected static function resolveRouterNameForPlan($plan)
    {
        if (!$plan) {
            return '';
        }
        if (!is_array($plan)) {
            $plan = $plan->as_array();
        }
        if (!empty($plan['is_radius'])) {
            return 'radius';
        }
        if (!empty($plan['type']) && $plan['type'] === 'Balance') {
            if (!empty($plan['routers'])) {
                return $plan['routers'];
            }
            return 'balance';
        }
        if (!empty($plan['routers'])) {
            return $plan['routers'];
        }
        if (!empty($plan['device']) && stripos($plan['device'], 'radius') !== false) {
            return 'radius';
        }
        return '';
    }

    protected static function activateLinkedPlans($customerId, $gateway, $channel, $note, array &$processedPlanIds, $plan, $skip, $primaryRouterName = '', &$chargeSummary = null)
    {
        if ($skip || $customerId <= 0 || !$plan) {
            return;
        }
        if (!is_array($plan)) {
            $plan = $plan->as_array();
        }
        if (empty($plan['id'])) {
            return;
        }
        $linkedIds = self::getLinkedPlanIds($plan['id']);
        if (empty($linkedIds)) {
            return;
        }

        $template = Lang::T('Linked Plan Activation Note');
        foreach ($linkedIds as $linkedId) {
            if (in_array($linkedId, $processedPlanIds, true)) {
                continue;
            }

            $linkedPlan = ORM::for_table('tbl_plans')->find_one($linkedId);
            if (!$linkedPlan) {
                continue;
            }

            if (!is_array($linkedPlan)) {
                $linkedPlan = $linkedPlan->as_array();
            }

            if (isset($linkedPlan['enabled']) && (int) $linkedPlan['enabled'] === 0) {
                continue;
            }

            $routerName = self::determineRouterNameForLinkedPlan(
                $customerId,
                $linkedPlan,
                $plan,
                $primaryRouterName
            );

            if (empty($routerName)) {
                continue;
            }

            $linkedNoteText = str_replace('[[plan]]', $plan['name_plan'], $template);
            $combinedNote = trim($note . "\n" . $linkedNoteText);

            $shouldSkipInvoiceNotification = isset($linkedPlan['invoice_notification'])
                && (int) $linkedPlan['invoice_notification'] === 0;

            try {
                self::rechargeUser(
                    $customerId,
                    $routerName,
                    $linkedPlan['id'],
                    $gateway,
                    $channel,
                    $combinedNote,
                    $processedPlanIds,
                    $shouldSkipInvoiceNotification,
                    $chargeSummary
                );
            } catch (Throwable $throwable) {
                Message::sendTelegram(
                    "Failed to activate linked plan automatically\n" .
                    'Customer: ' . $customerId . "\n" .
                    'Primary Plan: ' . $plan['name_plan'] . "\n" .
                    'Linked Plan ID: ' . $linkedPlan['id'] . "\n" .
                    $throwable->getMessage()
                );
            }
        }
    }

    protected static function determineRouterNameForLinkedPlan($customerId, array $linkedPlan, array $primaryPlan, $primaryRouterName = '')
    {
        $existingRouter = self::getExistingRouterForCustomerPlan($customerId, $linkedPlan['id']);
        if (!empty($existingRouter)) {
            return $existingRouter;
        }

        $linkedRouter = self::resolveRouterNameForPlan($linkedPlan);
        if (!empty($linkedRouter)) {
            return $linkedRouter;
        }

        $primaryPlanRouter = self::resolveRouterNameForPlan($primaryPlan);
        if (!empty($primaryPlanRouter)) {
            return $primaryPlanRouter;
        }

        return $primaryRouterName;
    }

    protected static function buildRouterTypeKey($routerName, $type)
    {
        $routerName = trim((string) $routerName);
        $type = trim((string) $type);
        if ($routerName === '' || $type === '') {
            return '';
        }
        return strtolower($routerName) . '|' . strtolower($type);
    }

    protected static function loadRechargeStateMap($customerId)
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0) {
            return [];
        }
        $rows = ORM::for_table('tbl_user_recharges')
            ->select_many('routers', 'type')
            ->where('customer_id', $customerId)
            ->find_array();
        if (empty($rows)) {
            return [];
        }
        $state = [];
        foreach ($rows as $row) {
            $key = self::buildRouterTypeKey($row['routers'] ?? '', $row['type'] ?? '');
            if ($key !== '') {
                $state[$key] = true;
            }
        }
        return $state;
    }

    protected static function calculateEstimatedRechargeTransactionPrice($customerId, array $plan, $routerName, $includeBills, array &$rechargeStateMap)
    {
        $isUnlimitedPlan = self::isUnlimitedPlan($plan);
        $addCost = 0.0;
        if ($includeBills) {
            list($_bills, $addCostRaw) = User::getBills($customerId);
            $addCost = (float) $addCostRaw;
        }

        $planPrice = (float) ($plan['price'] ?? 0);
        if (($plan['validity_unit'] ?? '') === 'Period' && !$isUnlimitedPlan) {
            $stateKey = self::buildRouterTypeKey($routerName, $plan['type'] ?? '');
            $hasExistingByType = ($stateKey !== '' && !empty($rechargeStateMap[$stateKey]));
            if (!$hasExistingByType) {
                return 0.0;
            }
            $invoiceAttr = User::getAttribute('Invoice', $customerId);
            if ($invoiceAttr !== null && $invoiceAttr !== '') {
                $planPrice = (float) $invoiceAttr;
            }
        }

        return max(0, $planPrice + $addCost);
    }

    protected static function estimateRechargeChargeGraphRecursive(
        $customerId,
        array $plan,
        $routerName,
        array &$processedPlanIds,
        array &$rechargeStateMap,
        array &$summary,
        $includeBillsForCurrentPlan = false
    ) {
        $planId = (int) ($plan['id'] ?? 0);
        if ($planId <= 0 || in_array($planId, $processedPlanIds, true)) {
            return;
        }
        $processedPlanIds[] = $planId;

        $charge = self::calculateEstimatedRechargeTransactionPrice(
            $customerId,
            $plan,
            $routerName,
            $includeBillsForCurrentPlan,
            $rechargeStateMap
        );
        $summary['total_charge'] += $charge;
        if (!isset($summary['primary_charge'])) {
            $summary['primary_charge'] = $charge;
            $summary['root_plan_id'] = $planId;
        }

        if (!isset($summary['plans']) || !is_array($summary['plans'])) {
            $summary['plans'] = [];
        }
        $summary['plans'][] = [
            'plan_id' => $planId,
            'router' => (string) $routerName,
            'charge' => $charge,
            'is_linked' => ($planId !== (int) ($summary['root_plan_id'] ?? $planId)),
        ];

        $stateKey = self::buildRouterTypeKey($routerName, $plan['type'] ?? '');
        if ($stateKey !== '') {
            $rechargeStateMap[$stateKey] = true;
        }

        $linkedIds = self::getLinkedPlanIds($planId);
        if (empty($linkedIds)) {
            return;
        }
        foreach ($linkedIds as $linkedId) {
            if (in_array((int) $linkedId, $processedPlanIds, true)) {
                continue;
            }
            $linkedPlan = ORM::for_table('tbl_plans')->find_one((int) $linkedId);
            if (!$linkedPlan) {
                continue;
            }
            $linkedPlan = is_array($linkedPlan) ? $linkedPlan : $linkedPlan->as_array();
            if (isset($linkedPlan['enabled']) && (int) $linkedPlan['enabled'] === 0) {
                continue;
            }
            $linkedRouter = self::determineRouterNameForLinkedPlan(
                $customerId,
                $linkedPlan,
                $plan,
                $routerName
            );
            if ($linkedRouter === '') {
                continue;
            }
            self::estimateRechargeChargeGraphRecursive(
                $customerId,
                $linkedPlan,
                $linkedRouter,
                $processedPlanIds,
                $rechargeStateMap,
                $summary,
                false
            );
        }
    }

    public static function estimateRechargeChargeGraph($customerId, $plan, $routerName, $includeBillsForPrimaryPlan = true)
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0 || !$plan) {
            return [
                'total_charge' => 0.0,
                'primary_charge' => 0.0,
                'linked_charge' => 0.0,
                'plans' => [],
            ];
        }
        $plan = is_array($plan) ? $plan : $plan->as_array();
        $rechargeStateMap = self::loadRechargeStateMap($customerId);
        $processedPlanIds = [];
        $summary = [
            'total_charge' => 0.0,
            'primary_charge' => null,
            'linked_charge' => 0.0,
            'plans' => [],
        ];
        self::estimateRechargeChargeGraphRecursive(
            $customerId,
            $plan,
            $routerName,
            $processedPlanIds,
            $rechargeStateMap,
            $summary,
            (bool) $includeBillsForPrimaryPlan
        );
        $summary['linked_charge'] = max(0, (float) $summary['total_charge'] - (float) ($summary['primary_charge'] ?? 0));
        return $summary;
    }

    protected static function estimateRefundChargeGraphRecursive(
        $customerId,
        array $plan,
        $routerName,
        $gateway,
        array &$processedPlanIds,
        array &$summary,
        $isLinkedAction = false
    ) {
        $planId = (int) ($plan['id'] ?? 0);
        if ($planId <= 0 || in_array($planId, $processedPlanIds, true)) {
            return;
        }
        $processedPlanIds[] = $planId;

        $charge = self::calculateRefundCharge($customerId, $plan, $gateway, $isLinkedAction);
        $summary['total_refund'] += $charge;
        if (!isset($summary['primary_refund'])) {
            $summary['primary_refund'] = $charge;
            $summary['root_plan_id'] = $planId;
        }
        if (!isset($summary['plans']) || !is_array($summary['plans'])) {
            $summary['plans'] = [];
        }
        $summary['plans'][] = [
            'plan_id' => $planId,
            'router' => (string) $routerName,
            'refund' => $charge,
            'is_linked' => $isLinkedAction,
        ];

        $linkedIds = self::getLinkedPlanIds($planId);
        if (empty($linkedIds)) {
            return;
        }
        foreach ($linkedIds as $linkedId) {
            if (in_array((int) $linkedId, $processedPlanIds, true)) {
                continue;
            }
            $linkedPlan = ORM::for_table('tbl_plans')->find_one((int) $linkedId);
            if (!$linkedPlan) {
                continue;
            }
            $linkedPlan = is_array($linkedPlan) ? $linkedPlan : $linkedPlan->as_array();
            $activeRecharge = self::findActiveRechargeRowForRefund($customerId, $linkedPlan, '');
            if (!$activeRecharge) {
                continue;
            }

            $linkedRouter = trim((string) ($activeRecharge['routers'] ?? ''));
            if ($linkedRouter === '') {
                $linkedRouter = self::resolveRouterNameForPlan($linkedPlan);
            }
            if ($linkedRouter === '') {
                $linkedRouter = self::resolveRouterNameForPlan($plan);
            }
            if ($linkedRouter === '') {
                $linkedRouter = trim((string) $routerName);
            }
            if ($linkedRouter === '') {
                continue;
            }

            self::estimateRefundChargeGraphRecursive(
                $customerId,
                $linkedPlan,
                $linkedRouter,
                $gateway,
                $processedPlanIds,
                $summary,
                true
            );
        }
    }

    public static function estimateRefundChargeGraph($customerId, $plan, $routerName, $gateway)
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0 || !$plan) {
            return [
                'total_refund' => 0.0,
                'primary_refund' => 0.0,
                'linked_refund' => 0.0,
                'plans' => [],
            ];
        }
        $plan = is_array($plan) ? $plan : $plan->as_array();
        $processedPlanIds = [];
        $summary = [
            'total_refund' => 0.0,
            'primary_refund' => null,
            'linked_refund' => 0.0,
            'plans' => [],
        ];
        self::estimateRefundChargeGraphRecursive(
            $customerId,
            $plan,
            $routerName,
            $gateway,
            $processedPlanIds,
            $summary,
            false
        );
        $summary['linked_refund'] = max(0, (float) $summary['total_refund'] - (float) ($summary['primary_refund'] ?? 0));
        return $summary;
    }

    protected static function normalizeDurationSeconds($seconds)
    {
        $seconds = (int) round($seconds);
        if ($seconds < 1) {
            $seconds = 1;
        }
        return $seconds;
    }

    protected static function resolvePlanValidityDurationSeconds($plan)
    {
        if (!$plan) {
            return null;
        }
        if (!is_array($plan)) {
            $plan = $plan->as_array();
        }
        $validity = (int) ($plan['validity'] ?? 0);
        if ($validity <= 0) {
            return null;
        }
        $unit = trim((string) ($plan['validity_unit'] ?? 'Days'));
        switch ($unit) {
            case 'Mins':
                return self::normalizeDurationSeconds($validity * 60);
            case 'Hrs':
                return self::normalizeDurationSeconds($validity * 3600);
            case 'Months':
            case 'Period':
                return self::normalizeDurationSeconds($validity * 30 * 86400);
            case 'Days':
            default:
                return self::normalizeDurationSeconds($validity * 86400);
        }
    }

    public static function resolveExtendDurationSeconds($plan, $requestedDays)
    {
        $requestedDays = (int) $requestedDays;
        if ($requestedDays < 1) {
            $requestedDays = 1;
        }
        $requestedSeconds = self::normalizeDurationSeconds($requestedDays * 86400);
        $planSeconds = self::resolvePlanValidityDurationSeconds($plan);
        if ($planSeconds !== null && $planSeconds > 0 && $planSeconds < $requestedSeconds) {
            return $planSeconds;
        }
        return $requestedSeconds;
    }

    public static function secondsToDaysRoundedUp($seconds)
    {
        $seconds = self::normalizeDurationSeconds($seconds);
        return (int) max(1, ceil($seconds / 86400));
    }

    public static function isTruthyValue($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return ((float) $value) !== 0.0;
        }
        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'yes', 'true', 'on'], true);
    }

    protected static function resolveConfigArray($settings)
    {
        if (is_array($settings)) {
            return $settings;
        }
        global $config;
        if (is_array($config)) {
            return $config;
        }
        return [];
    }

    public static function isCustomerSelfExtendPrepaidAllowed($plan, $settings = null)
    {
        $planData = is_array($plan) ? $plan : ($plan && method_exists($plan, 'as_array') ? $plan->as_array() : []);
        if (empty($planData)) {
            return false;
        }

        $isPrepaid = strtolower(trim((string) ($planData['prepaid'] ?? 'no'))) === 'yes';
        if (!$isPrepaid) {
            return true;
        }

        $cfg = self::resolveConfigArray($settings);
        return self::isTruthyValue($cfg['extend_allow_prepaid'] ?? 0);
    }

    public static function isCustomerSelfExtendPlanAllowed($plan, $settings = null)
    {
        $planData = is_array($plan) ? $plan : ($plan && method_exists($plan, 'as_array') ? $plan->as_array() : []);
        if (empty($planData)) {
            return false;
        }

        $cfg = self::resolveConfigArray($settings);
        if (!self::isTruthyValue($cfg['extend_expired'] ?? 0)) {
            return false;
        }
        if (array_key_exists('customer_can_extend', $planData) && !self::isTruthyValue($planData['customer_can_extend'])) {
            return false;
        }
        if (array_key_exists('enabled', $planData) && !self::isTruthyValue($planData['enabled'])) {
            return false;
        }

        $price = (float) ($planData['price'] ?? 0);
        if ($price <= 0) {
            return false;
        }

        $welcomePlanId = (int) ($cfg['welcome_package_plan'] ?? 0);
        $planId = (int) ($planData['id'] ?? 0);
        if ($welcomePlanId > 0 && $planId > 0 && $welcomePlanId === $planId) {
            return false;
        }

        return true;
    }

    protected static function appendRechargeChargeSummary(&$chargeSummary, $planId, $amount)
    {
        if (!is_array($chargeSummary)) {
            return;
        }
        $amount = max(0, (float) $amount);
        if (!isset($chargeSummary['total_charge'])) {
            $chargeSummary['total_charge'] = 0.0;
        }
        if (!isset($chargeSummary['primary_charge'])) {
            $chargeSummary['primary_charge'] = 0.0;
        }
        if (!isset($chargeSummary['plans']) || !is_array($chargeSummary['plans'])) {
            $chargeSummary['plans'] = [];
        }
        $chargeSummary['total_charge'] += $amount;
        if (!isset($chargeSummary['root_plan_id'])) {
            $chargeSummary['root_plan_id'] = (int) $planId;
        }
        if ((int) $chargeSummary['root_plan_id'] === (int) $planId && empty($chargeSummary['primary_recorded'])) {
            $chargeSummary['primary_charge'] = $amount;
            $chargeSummary['primary_recorded'] = true;
        }
        $chargeSummary['plans'][] = [
            'plan_id' => (int) $planId,
            'charge' => $amount,
            'is_linked' => ((int) $chargeSummary['root_plan_id'] !== (int) $planId),
        ];
        $chargeSummary['linked_charge'] = max(0, (float) $chargeSummary['total_charge'] - (float) ($chargeSummary['primary_charge'] ?? 0));
    }

    protected static function appendRefundSummary(&$refundSummary, $planId, $amount)
    {
        if (!is_array($refundSummary)) {
            return;
        }
        $amount = max(0, (float) $amount);
        if (!isset($refundSummary['total_refund'])) {
            $refundSummary['total_refund'] = 0.0;
        }
        if (!isset($refundSummary['primary_refund'])) {
            $refundSummary['primary_refund'] = 0.0;
        }
        if (!isset($refundSummary['plans']) || !is_array($refundSummary['plans'])) {
            $refundSummary['plans'] = [];
        }
        $refundSummary['total_refund'] += $amount;
        if (!isset($refundSummary['root_plan_id'])) {
            $refundSummary['root_plan_id'] = (int) $planId;
        }
        if ((int) $refundSummary['root_plan_id'] === (int) $planId && empty($refundSummary['primary_recorded'])) {
            $refundSummary['primary_refund'] = $amount;
            $refundSummary['primary_recorded'] = true;
        }
        $refundSummary['plans'][] = [
            'plan_id' => (int) $planId,
            'refund' => $amount,
            'is_linked' => ((int) $refundSummary['root_plan_id'] !== (int) $planId),
        ];
        $refundSummary['linked_refund'] = max(0, (float) $refundSummary['total_refund'] - (float) ($refundSummary['primary_refund'] ?? 0));
    }

    protected static function getExistingRouterForCustomerPlan($customerId, $planId)
    {
        $customerId = (int) $customerId;
        $planId = (int) $planId;
        if ($customerId <= 0 || $planId <= 0) {
            return '';
        }
        $row = ORM::for_table('tbl_user_recharges')
            ->select('routers')
            ->where('customer_id', $customerId)
            ->where('plan_id', $planId)
            ->order_by_desc('id')
            ->find_one();
        if ($row && !empty($row['routers'])) {
            return $row['routers'];
        }
        return '';
    }

    protected static function isUnlimitedPlan($plan): bool
    {
        if (!$plan) {
            return false;
        }
        if (!is_array($plan)) {
            $plan = $plan->as_array();
        }
        // Treat validity <= 0 as unlimited for all validity_unit values (Mins/Hrs/Days/Months/Period).
        return isset($plan['validity']) && (int) $plan['validity'] <= 0;
    }

    protected static function getUnlimitedExpirationDate(): string
    {
        return '2099-12-31';
    }

    protected static function getUnlimitedExpirationTime(): string
    {
        return '23:59:59';
    }

    protected static function handlePppoeUsageActivation($customer, $plan, $rechargeRow, $transactionRow)
    {
        if (!class_exists('PppoeUsage')) {
            return;
        }

        if (!$rechargeRow || !$plan || !$customer) {
            return;
        }

        $planData = is_array($plan) ? $plan : $plan->as_array();
        if (!PppoeUsage::isSupportedPlan($planData) || !PppoeUsage::isStorageReady()) {
            return;
        }

        $customerData = is_array($customer) ? $customer : $customer->as_array();
        $rechargeData = is_array($rechargeRow) ? $rechargeRow : $rechargeRow->as_array();
        $transactionData = null;
        if ($transactionRow) {
            $transactionData = is_array($transactionRow) ? $transactionRow : $transactionRow->as_array();
        } else {
            $transactionData = ['id' => 0];
        }

        try {
            $usageType = PppoeUsage::normalizePlanType($planData['type'] ?? '');
            $usageIdentity = PppoeUsage::resolveUsageIdentity($customerData, $usageType);
            $baseline = [
                'tx' => 0,
                'rx' => 0,
                'binding_user' => $usageIdentity,
                'binding_name' => PppoeUsage::resolveBindingName($usageIdentity, $usageType),
            ];
            $note = 'Recharge start';

            $dvc = self::getDevice($planData);
            if ($dvc && file_exists($dvc)) {
                require_once $dvc;
                $deviceClass = $planData['device'] ?? '';
                if ($deviceClass !== '' && class_exists($deviceClass)) {
                    $device = new $deviceClass();
                    if (method_exists($device, 'getPppoeBindingCounters')) {
                        $counterWarning = '';
                        $counters = $device->getPppoeBindingCounters($customerData, $planData, $counterWarning, $baseline['binding_name']);
                        if (is_array($counters)) {
                            $baseline['tx'] = max(0, (int) ($counters['tx_byte'] ?? 0));
                            $baseline['rx'] = max(0, (int) ($counters['rx_byte'] ?? 0));
                            if (!empty($counters['binding_name'])) {
                                $baseline['binding_name'] = (string) $counters['binding_name'];
                            }
                        } elseif ($counterWarning !== '') {
                            $note .= ' | counter warning: ' . $counterWarning;
                        }
                    }
                }
            }

            PppoeUsage::openActivationCycle($customerData, $planData, $rechargeData, $transactionData, $baseline, $note);
        } catch (Throwable $e) {
            if (class_exists('Message')) {
                Message::sendTelegram(
                    "Access usage init failed\n" .
                    'Customer: ' . ($customerData['username'] ?? '') . "\n" .
                    'Type: ' . ($planData['type'] ?? '') . "\n" .
                    'Plan: ' . ($planData['name_plan'] ?? '') . "\n" .
                    'Router: ' . ($planData['routers'] ?? '') . "\n" .
                    $e->getMessage()
                );
            }
        }
    }

    protected static function buildExtendAnchorFieldName($rechargeId)
    {
        $rechargeId = (int) $rechargeId;
        if ($rechargeId < 1) {
            return '';
        }
        return 'extend_anchor_recharge_' . $rechargeId;
    }

    public static function getExtendAnchorStart($customerId, $rechargeId)
    {
        $customerId = (int) $customerId;
        $fieldName = self::buildExtendAnchorFieldName($rechargeId);
        if ($customerId < 1 || $fieldName === '') {
            return '';
        }

        $value = trim((string) User::getAttribute($fieldName, $customerId, ''));
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false || $timestamp < 1) {
            return '';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    public static function setExtendAnchorStartIfMissing($customerId, $rechargeId, $anchorAt = null)
    {
        $customerId = (int) $customerId;
        $fieldName = self::buildExtendAnchorFieldName($rechargeId);
        if ($customerId < 1 || $fieldName === '') {
            return '';
        }

        $existing = self::getExtendAnchorStart($customerId, $rechargeId);
        if ($existing !== '') {
            return $existing;
        }

        $anchorAt = trim((string) $anchorAt);
        if ($anchorAt === '') {
            $anchorAt = date('Y-m-d H:i:s');
        }
        $anchorTs = strtotime($anchorAt);
        if ($anchorTs === false || $anchorTs < 1) {
            $anchorTs = time();
        }
        $normalized = date('Y-m-d H:i:s', $anchorTs);
        User::setAttribute($fieldName, $normalized, $customerId);
        return $normalized;
    }

    public static function clearExtendAnchorStart($customerId, $rechargeId)
    {
        $customerId = (int) $customerId;
        $fieldName = self::buildExtendAnchorFieldName($rechargeId);
        if ($customerId < 1 || $fieldName === '') {
            return;
        }
        User::setAttribute($fieldName, '', $customerId);
    }
    /**
     * @param int         $id_customer             String user identifier
     * @param string      $router_name             router name for this package
     * @param int         $plan_id                 plan id for this package
     * @param string      $gateway                 payment gateway name
     * @param string      $channel                 channel payment gateway
     * @param string      $note                    additional note for transaction
     * @param array|null  $processedPlanIds        processed plan ids to prevent duplicate activation
     * @param bool        $skipInvoiceNotification whether to skip sending invoice notification
     * @return string|false
     */
    public static function rechargeUser($id_customer, $router_name, $plan_id, $gateway, $channel, $note = '', &$processedPlanIds = null, $skipInvoiceNotification = false, &$chargeSummary = null)
    {
        global $config, $admin, $c, $p, $b, $t, $d, $zero, $trx, $_app_stage, $isChangePlan;
        $transactionForNotification = null;
        $rechargeForUsage = null;
        $date_only = date("Y-m-d");
        $time_only = date("H:i:s");
        $time = date("H:i:s");
        $inv = "";
        $isVoucher = false;
        $c = [];
        if ($trx && $trx['status'] == 2) {
            // if its already paid, return it
            return;
        }

        if ($processedPlanIds === null) {
            $processedPlanIds = [];
        }
        if ($chargeSummary !== null && !is_array($chargeSummary)) {
            $chargeSummary = [];
        }
        $planIdInt = (int) $plan_id;
        if ($planIdInt <= 0 || in_array($planIdInt, $processedPlanIds, true)) {
            return false;
        }
        $processedPlanIds[] = $planIdInt;
        if (is_array($chargeSummary) && !isset($chargeSummary['root_plan_id'])) {
            $chargeSummary['root_plan_id'] = $planIdInt;
            $chargeSummary['total_charge'] = 0.0;
            $chargeSummary['primary_charge'] = 0.0;
            $chargeSummary['linked_charge'] = 0.0;
            $chargeSummary['plans'] = [];
            $chargeSummary['primary_recorded'] = false;
        }

        if ($id_customer == '' or $router_name == '' or $planIdInt == 0) {
            return false;
        }
        if (trim($gateway) == 'Voucher' && $id_customer == 0) {
            $isVoucher = true;
        }

        $p = ORM::for_table('tbl_plans')->where('id', $planIdInt)->find_one();
        if (!$skipInvoiceNotification && $p && isset($p['invoice_notification']) && (int) $p['invoice_notification'] === 0) {
            $skipInvoiceNotification = true;
        }
        $isUnlimitedPlan = self::isUnlimitedPlan($p);

        if (!$isVoucher) {
            $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
            if ($c['status'] != 'Active') {
                _alert(Lang::T('This account status') . ' : ' . Lang::T($c['status']), 'danger', "");
            }
        } else {
            $c = [
                'fullname' => $gateway,
                'email' => '',
                'username' => $channel,
                'password' => $channel,
            ];
        }

        $add_cost = 0;
        $bills = [];
        // Zero cost recharge
        if (isset($zero) && $zero == 1) {
            $p['price'] = 0;
        } else {
            // Additional cost
            list($bills, $add_cost) = User::getBills($id_customer);
            if ($add_cost != 0 && $router_name != 'balance') {
                foreach ($bills as $k => $v) {
                    $note .= $k . " : " . Lang::moneyFormat($v) . "\n";
                }
                $note .= $p['name_plan'] . " : " . Lang::moneyFormat($p['price']) . "\n";
            }
        }


        if (!$p['enabled'] && $gateway != 'Welcome') {
            if (!isset($admin) || !isset($admin['id']) || empty($admin['id'])) {
                r2(getUrl('home'), 'e', Lang::T('Plan Not found'));
            }
            if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                r2(getUrl('dashboard'), 'e', Lang::T('You do not have permission to access this page'));
            }
        }

        if ($p['validity_unit'] == 'Period' && !$isUnlimitedPlan) {
            // if customer has attribute Expired Date use it
            $day_exp = User::getAttribute("Expired Date", $c['id']);
            if (!$day_exp) {
                // if customer no attribute Expired Date use plan expired date
                $day_exp = 20;
                if ($p['prepaid'] == 'no') {
                    $day_exp = $p['expired_date'];
                }
                if (empty($day_exp)) {
                    $day_exp = 20;
                }
            }
        }



        if ($router_name == 'balance') {
            $result = self::rechargeBalance($c, $p, $gateway, $channel, $note);
            self::activateLinkedPlans($id_customer, $gateway, $channel, $note, $processedPlanIds, $p, $isVoucher, $router_name, $chargeSummary);
            return $result;
        }

        if ($router_name == 'Custom Balance') {
            $result = self::rechargeCustomBalance($c, $p, $gateway, $channel, $note);
            self::activateLinkedPlans($id_customer, $gateway, $channel, $note, $processedPlanIds, $p, $isVoucher, $router_name, $chargeSummary);
            return $result;
        }

        /**
         * 1 Customer only can have 1 PPPOE and 1 Hotspot Plan, 1 prepaid and 1 postpaid
         */

        $query = ORM::for_table('tbl_user_recharges')
            ->select('tbl_user_recharges.id', 'id')
            ->select('customer_id')
            ->select('username')
            ->select('plan_id')
            ->select('namebp')
            ->select('recharged_on')
            ->select('recharged_time')
            ->select('expiration')
            ->select('time')
            ->select('status')
            ->select('method')
            ->select('tbl_user_recharges.routers', 'routers')
            ->select('tbl_user_recharges.type', 'type')
            ->select('admin_id')
            ->select('prepaid')
            ->where('tbl_user_recharges.routers', $router_name)
            ->where('tbl_user_recharges.Type', $p['type'])
            # PPPOE or Hotspot only can have 1 per customer prepaid or postpaid
            # because 1 customer can have 1 PPPOE and 1 Hotspot Plan in mikrotik
            //->where('prepaid', $p['prepaid'])
            ->left_outer_join('tbl_plans', array('tbl_plans.id', '=', 'tbl_user_recharges.plan_id'));
        if ($isVoucher) {
            $query->where('username', $c['username']);
        } else {
            $query->where('customer_id', $id_customer);
        }
        $b = $query->find_one();

        run_hook("recharge_user");

        if ($isUnlimitedPlan) {
            $date_exp = self::getUnlimitedExpirationDate();
            $time = self::getUnlimitedExpirationTime();
        } else if ($p['validity_unit'] == 'Months') {
            $date_exp = date("Y-m-d", strtotime('+' . $p['validity'] . ' month'));
        } else if ($p['validity_unit'] == 'Period') {
            $current_date = new DateTime($date_only);
            $exp_date = clone $current_date;
            $exp_date->modify('first day of next month');
            $exp_date->setDate($exp_date->format('Y'), $exp_date->format('m'), $day_exp);

            $min_days = 7 * $p['validity'];
            $max_days = 35 * $p['validity'];

            $days_until_exp = $exp_date->diff($current_date)->days;

            // If less than min_days away, move to the next period
            while ($days_until_exp < $min_days) {
                $exp_date->modify('+1 month');
                $days_until_exp = $exp_date->diff($current_date)->days;
            }

            // If more than max_days away, move to the previous period
            while ($days_until_exp > $max_days) {
                $exp_date->modify('-1 month');
                $days_until_exp = $exp_date->diff($current_date)->days;
            }

            // Final check to ensure we're not less than min_days or in the past
            if ($days_until_exp < $min_days || $exp_date <= $current_date) {
                $exp_date->modify('+1 month');
            }

            // Adjust for multiple periods
            if ($p['validity'] > 1) {
                $exp_date->modify('+' . ($p['validity'] - 1) . ' months');
            }

            $date_exp = $exp_date->format('Y-m-d');
            $time = "23:59:59";
        } else if ($p['validity_unit'] == 'Days') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' day')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        } else if ($p['validity_unit'] == 'Hrs') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' hour')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        } else if ($p['validity_unit'] == 'Mins') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' minute')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        }

        if ($b) {
            $previousExpiryDate = trim((string) ($b['expiration'] ?? ''));
            $previousExpiryTime = trim((string) ($b['time'] ?? ''));
            if ($previousExpiryTime === '') {
                $previousExpiryTime = '00:00:00';
            }
            $previousExpiryAt = ($previousExpiryDate === '')
                ? date('Y-m-d H:i:s')
                : ($previousExpiryDate . ' ' . $previousExpiryTime);
            $shouldSchedulePreviousExpiryReset = class_exists('PppoeUsage')
                && PppoeUsage::isStorageReady()
                && PppoeUsage::isSupportedPlan(is_object($p) ? $p->as_array() : (array) $p);
            $lastExpired = Lang::dateAndTimeFormat($b['expiration'], $b['time']);
            $isChangePlan = false;
            if ($b['namebp'] == $p['name_plan'] && $b['status'] == 'on') {
                $extendAnchorAt = self::getExtendAnchorStart($id_customer, (int) ($b['id'] ?? 0));
                $extendAnchorTs = $extendAnchorAt !== '' ? strtotime($extendAnchorAt) : false;
                $hasExtendAnchor = ($extendAnchorTs !== false && $extendAnchorTs > 0);
                $shouldExtendFromCurrent = ($config['extend_expiry'] == 'yes') || $hasExtendAnchor;

                if ($shouldExtendFromCurrent) {
                    // Extend from existing expiry by default, but if extend anchor exists use anchor start.
                    $anchorDate = $hasExtendAnchor ? date('Y-m-d', $extendAnchorTs) : '';
                    $anchorTime = $hasExtendAnchor ? date('H:i:s', $extendAnchorTs) : '';
                    $anchorDateTime = $hasExtendAnchor ? ($anchorDate . ' ' . $anchorTime) : '';

                    if ($isUnlimitedPlan) {
                        $date_exp = self::getUnlimitedExpirationDate();
                        $time = self::getUnlimitedExpirationTime();
                    } else {
                        switch ($p['validity_unit']) {
                            case 'Months':
                                if ($hasExtendAnchor) {
                                    $date_exp = date("Y-m-d", strtotime($anchorDateTime . ' +' . $p['validity'] . ' months'));
                                    $time = $anchorTime;
                                } else {
                                    $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                                    $time = $b['time'];
                                }
                                break;
                            case 'Period':
                                if ($hasExtendAnchor) {
                                    $date_exp = date("Y-m-$day_exp", strtotime($anchorDate . ' +' . $p['validity'] . ' months'));
                                } else {
                                    $date_exp = date("Y-m-$day_exp", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                                }
                                $time = date("23:59:00");
                                break;
                            case 'Days':
                                if ($hasExtendAnchor) {
                                    $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($anchorDateTime . ' +' . $p['validity'] . ' days')));
                                    $date_exp = $datetime[0];
                                    $time = $datetime[1];
                                } else {
                                    $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' days'));
                                    $time = $b['time'];
                                }
                                break;
                            case 'Hrs':
                                if ($hasExtendAnchor) {
                                    $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($anchorDateTime . ' +' . $p['validity'] . ' hours')));
                                } else {
                                    $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($b['expiration'] . ' ' . $b['time'] . ' +' . $p['validity'] . ' hours')));
                                }
                                $date_exp = $datetime[0];
                                $time = $datetime[1];
                                break;
                            case 'Mins':
                                if ($hasExtendAnchor) {
                                    $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($anchorDateTime . ' +' . $p['validity'] . ' minutes')));
                                } else {
                                    $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($b['expiration'] . ' ' . $b['time'] . ' +' . $p['validity'] . ' minutes')));
                                }
                                $date_exp = $datetime[0];
                                $time = $datetime[1];
                                break;
                        }
                    }
                } else {
                    $isChangePlan = true;
                }
            } else {
                $isChangePlan = true;
            }

            //if ($b['status'] == 'on') {
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'Demo') {
                try {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        (new $p['device'])->add_customer($c, $p);
                    } else {
                        new Exception(Lang::T("Devices Not Found"));
                    }
                } catch (Throwable $e) {
                    Message::sendTelegram(
                        "System Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                } catch (Exception $e) {
                    Message::sendTelegram(
                        "System Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                }
            }
            //}

            // if contains 'mikrotik', 'hotspot', 'pppoe', 'radius' then recharge it
            if (Validator::containsKeyword($p['device'])) {
                $b->customer_id = $id_customer;
                $b->username = $c['username'];
                $b->plan_id = $planIdInt;
                $b->namebp = $p['name_plan'];
                $b->recharged_on = $date_only;
                $b->recharged_time = $time_only;
                $b->expiration = $date_exp;
                $b->time = $time;
                $b->status = "on";
                $b->method = "$gateway - $channel";
                $b->routers = $router_name;
                $b->type = $p['type'];
                if ($admin) {
                    $b->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $b->admin_id = '0';
                }
                $b->save();
                self::clearExtendAnchorStart($id_customer, (int) ($b['id'] ?? 0));
                if ($shouldSchedulePreviousExpiryReset) {
                    PppoeUsage::scheduleCounterReset((int) $b['id'], $previousExpiryAt, 'Recharge extension: keep previous expiry reset');
                }
                $rechargeForUsage = $b;
            }

            // insert table transactions
            $t = ORM::for_table('tbl_transactions')->create();
            $t->invoice = $inv = "INV-" . Package::_raid();
            $t->username = $c['username'];
            $t->user_id = $id_customer;
            $t->plan_name = $p['name_plan'];
            if ($gateway == 'Voucher' && User::isUserVoucher($channel)) {
                //its already paid
                $t->price = 0;
            } else {
                if ($p['validity_unit'] == 'Period' && !$isUnlimitedPlan) {
                    // Postpaid price from field
                    $add_inv = User::getAttribute("Invoice", $id_customer);
                    if (empty($add_inv) or $add_inv == 0) {
                        $t->price = $p['price'] + $add_cost;
                    } else {
                        $t->price = $add_inv + $add_cost;
                    }
                } else {
                    $t->price = $p['price'] + $add_cost;
                }
            }
            $t->recharged_on = $date_only;
            $t->recharged_time = $time_only;
            $t->expiration = $date_exp;
            $t->time = $time;
            $t->method = "$gateway - $channel";
            $t->routers = $router_name;
            $t->note = $note;
            $t->type = $p['type'];
            if ($admin) {
                $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
            } else {
                $t->admin_id = '0';
            }
            $t->save();
            $transactionForNotification = $t;
            self::appendRechargeChargeSummary($chargeSummary, $planIdInt, (float) $t->price);

            if ($p['validity_unit'] == 'Period' && !$isUnlimitedPlan) {
                // insert price to fields for invoice next month
                $fl = ORM::for_table('tbl_customers_fields')->where('field_name', 'Invoice')->where('customer_id', $c['id'])->find_one();
                if (!$fl) {
                    $fl = ORM::for_table('tbl_customers_fields')->create();
                    $fl->customer_id = $c['id'];
                    $fl->field_name = 'Invoice';
                    $fl->field_value = $p['price'];
                    $fl->save();
                } else {
                    $fl->customer_id = $c['id'];
                    $fl->field_value = $p['price'];
                    $fl->save();
                }
            }

            Message::sendTelegram("#u$c[username] $c[fullname] #recharge #$p[type] \n" . $p['name_plan'] .
                "\nRouter: " . $router_name .
                "\nGateway: " . $gateway .
                "\nChannel: " . $channel .
                "\nLast Expired: $lastExpired" .
                "\nNew Expired: " . Lang::dateAndTimeFormat($date_exp, $time) .
                "\nPrice: " . Lang::moneyFormat($p['price'] + $add_cost) .
                "\nNote:\n" . $note);
        } else {
            // active plan not exists
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'Demo') {
                try {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        (new $p['device'])->add_customer($c, $p);
                    } else {
                        new Exception(Lang::T("Devices Not Found"));
                    }
                } catch (Throwable $e) {
                    Message::sendTelegram(
                        "System Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                } catch (Exception $e) {
                    Message::sendTelegram(
                        "System Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                }
            }

            // if contains 'mikrotik', 'hotspot', 'pppoe', 'radius' then recharge it
            if (Validator::containsKeyword($p['device'])) {
                $d = ORM::for_table('tbl_user_recharges')->create();
                $d->customer_id = $id_customer;
                $d->username = $c['username'];
                $d->plan_id = $planIdInt;
                $d->namebp = $p['name_plan'];
                $d->recharged_on = $date_only;
                $d->recharged_time = $time_only;
                $d->expiration = $date_exp;
                $d->time = $time;
                $d->status = "on";
                $d->method = "$gateway - $channel";
                $d->routers = $router_name;
                $d->type = $p['type'];
                if ($admin) {
                    $d->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $d->admin_id = '0';
                }
                $d->save();
                $rechargeForUsage = $d;
            }

            // insert table transactions
            $t = ORM::for_table('tbl_transactions')->create();
            $t->invoice = $inv = "INV-" . Package::_raid();
            $t->username = $c['username'];
            $t->user_id = $id_customer;
            $t->plan_name = $p['name_plan'];
            if ($gateway == 'Voucher' && User::isUserVoucher($channel)) {
                $t->price = 0;
                // its already paid
            } else {
                if ($p['validity_unit'] == 'Period' && !$isUnlimitedPlan) {
                    // Postpaid price always zero for first time
                    $bills = [];
                    $t->price = 0;
                } else {
                    $t->price = $p['price'] + $add_cost;
                }
            }
            $t->recharged_on = $date_only;
            $t->recharged_time = $time_only;
            $t->expiration = $date_exp;
            $t->time = $time;
            $t->method = "$gateway - $channel";
            $t->note = $note;
            $t->routers = $router_name;
            if ($admin) {
                $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
            } else {
                $t->admin_id = '0';
            }
            $t->type = $p['type'];
            $t->save();
            $transactionForNotification = $t;
            self::appendRechargeChargeSummary($chargeSummary, $planIdInt, (float) $t->price);

            if ($p['validity_unit'] == 'Period' && !$isUnlimitedPlan && (int) $p['validity'] > 0 && $p['price'] != 0) {
                // insert price to fields for invoice next month
                $fl = ORM::for_table('tbl_customers_fields')->where('field_name', 'Invoice')->where('customer_id', $c['id'])->find_one();
                if (!$fl) {
                    $fl = ORM::for_table('tbl_customers_fields')->create();
                    $fl->customer_id = $c['id'];
                    $fl->field_name = 'Invoice';
                    // Calculating Price
                    $sd = new DateTime("$date_only");
                    $ed = new DateTime("$date_exp");
                    $td = $ed->diff($sd);
                    $fd = $td->format("%a");
                    $gi = ($p['price'] / (30 * (int) $p['validity'])) * $fd;
                    if ($gi > $p['price']) {
                        $fl->field_value = $p['price'];
                    } else {
                        $fl->field_value = $gi;
                    }
                    $fl->save();
                } else {
                    $fl->customer_id = $c['id'];
                    $fl->field_value = $p['price'];
                    $fl->save();
                }
            }

            Message::sendTelegram("#u$c[username] $c[fullname] #buy #$p[type] \n" . $p['name_plan'] .
                "\nRouter: " . $router_name .
                "\nGateway: " . $gateway .
                "\nChannel: " . $channel .
                "\nExpired: " . Lang::dateAndTimeFormat($date_exp, $time) .
                "\nPrice: " . Lang::moneyFormat($p['price'] + $add_cost) .
                "\nNote:\n" . $note);
        }

        if (is_array($bills) && count($bills) > 0) {
            User::billsPaid($bills, $id_customer);
        }
        self::handlePppoeUsageActivation($c, $p, $rechargeForUsage, $transactionForNotification);
        self::activateLinkedPlans($id_customer, $gateway, $channel, $note, $processedPlanIds, $p, $isVoucher, $router_name, $chargeSummary);
        run_hook("recharge_user_finish");
        if (!$skipInvoiceNotification && $transactionForNotification) {
            $t = $transactionForNotification;
            Message::sendInvoice($c, $t);
        }
        if ($trx) {
            $trx->trx_invoice = $inv;
        }
        return $inv;
    }

    protected static function resolveRechargeDateTime($date, $time)
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

    protected static function subtractPlanValidityFromDateTime($currentDateTime, $plan)
    {
        $currentDateTime = trim((string) $currentDateTime);
        if ($currentDateTime === '') {
            $currentDateTime = date('Y-m-d H:i:s');
        }

        if (self::isUnlimitedPlan($plan)) {
            return date('Y-m-d H:i:s', time() - 1);
        }

        $validity = max(0, (int) ($plan['validity'] ?? 0));
        if ($validity <= 0) {
            return date('Y-m-d H:i:s', time() - 1);
        }

        try {
            $dt = new DateTime($currentDateTime);
        } catch (Exception $e) {
            $dt = new DateTime(date('Y-m-d H:i:s'));
        }

        $unit = trim((string) ($plan['validity_unit'] ?? 'Days'));
        switch ($unit) {
            case 'Months':
                $dt->modify('-' . $validity . ' month');
                break;
            case 'Period':
                $dt->modify('-' . $validity . ' month');
                $dt->setTime(23, 59, 59);
                break;
            case 'Hrs':
                $dt->modify('-' . $validity . ' hour');
                break;
            case 'Mins':
                $dt->modify('-' . $validity . ' minute');
                break;
            case 'Days':
            default:
                $dt->modify('-' . $validity . ' day');
                break;
        }

        return $dt->format('Y-m-d H:i:s');
    }

    protected static function findActiveRechargeRowForRefund($customerId, $plan, $routerName = '')
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0 || !$plan || empty($plan['id'])) {
            return null;
        }

        $query = ORM::for_table('tbl_user_recharges')
            ->where('customer_id', $customerId)
            ->where('plan_id', (int) $plan['id'])
            ->where('status', 'on');

        $routerName = trim((string) $routerName);
        if ($routerName !== '') {
            $query->where('routers', $routerName);
        }
        if (!empty($plan['type'])) {
            $query->where('type', (string) $plan['type']);
        }

        $row = $query->order_by_desc('id')->find_one();
        if ($row) {
            return $row;
        }

        return ORM::for_table('tbl_user_recharges')
            ->where('customer_id', $customerId)
            ->where('plan_id', (int) $plan['id'])
            ->where('status', 'on')
            ->order_by_desc('id')
            ->find_one();
    }

    protected static function calculateRefundCharge($customerId, $plan, $gateway, $isLinkedAction = false)
    {
        global $config;

        $gateway = strtolower(trim((string) $gateway));
        $isZero = strpos($gateway, 'zero') !== false;
        if ($isZero) {
            return 0;
        }

        $planPrice = (float) ($plan['price'] ?? 0);
        if (!$isLinkedAction && ($plan['validity_unit'] ?? '') === 'Period' && !self::isUnlimitedPlan($plan)) {
            $invoicePrice = User::getAttribute('Invoice', $customerId);
            if ($invoicePrice !== null && $invoicePrice !== '') {
                $planPrice = (float) $invoicePrice;
            }
        }

        $addCost = 0.0;
        if (!$isLinkedAction) {
            list($_bills, $addCostRaw) = User::getBills($customerId);
            $addCost = (float) $addCostRaw;
        }

        $tax = 0.0;
        if (($config['enable_tax'] ?? 'no') === 'yes') {
            $taxRateSetting = $config['tax_rate'] ?? null;
            $customTaxRate = isset($config['custom_tax_rate']) ? (float) $config['custom_tax_rate'] : null;
            $taxRate = ($taxRateSetting === 'custom') ? $customTaxRate : $taxRateSetting;
            $tax = (float) self::tax($planPrice, (float) $taxRate);
        }

        return max(0, (float) $planPrice + (float) $addCost + (float) $tax);
    }

    protected static function reverseLinkedPlans($customerId, $gateway, $channel, $note, array &$processedPlanIds, $plan, $primaryRouterName = '', &$refundSummary = null)
    {
        if ($customerId <= 0 || !$plan) {
            return;
        }
        if (!is_array($plan)) {
            $plan = $plan->as_array();
        }
        if (empty($plan['id'])) {
            return;
        }

        $linkedIds = self::getLinkedPlanIds($plan['id']);
        if (empty($linkedIds)) {
            return;
        }

        foreach ($linkedIds as $linkedId) {
            if (in_array($linkedId, $processedPlanIds, true)) {
                continue;
            }

            $linkedPlan = ORM::for_table('tbl_plans')->find_one($linkedId);
            if (!$linkedPlan) {
                continue;
            }
            if (!is_array($linkedPlan)) {
                $linkedPlan = $linkedPlan->as_array();
            }

            $activeRecharge = self::findActiveRechargeRowForRefund($customerId, $linkedPlan, '');
            if (!$activeRecharge) {
                continue;
            }

            $routerName = trim((string) ($activeRecharge['routers'] ?? ''));
            if ($routerName === '') {
                $routerName = self::resolveRouterNameForPlan($linkedPlan);
            }
            if ($routerName === '') {
                $routerName = self::resolveRouterNameForPlan($plan);
            }
            if ($routerName === '') {
                $routerName = trim((string) $primaryRouterName);
            }
            if ($routerName === '') {
                continue;
            }

            $linkedNote = trim($note . "\n" . Lang::T('Linked Plan Refund Note'));
            $skipInvoiceNotification = isset($linkedPlan['invoice_notification']) && (int) $linkedPlan['invoice_notification'] === 0;

            try {
                self::refundUser(
                    $customerId,
                    $routerName,
                    (int) $linkedPlan['id'],
                    $gateway,
                    $channel,
                    $linkedNote,
                    $processedPlanIds,
                    true,
                    $skipInvoiceNotification,
                    $refundSummary
                );
            } catch (Throwable $throwable) {
                Message::sendTelegram(
                    "Failed to refund linked plan automatically\n" .
                    'Customer: ' . $customerId . "\n" .
                    'Primary Plan: ' . ($plan['name_plan'] ?? '-') . "\n" .
                    'Linked Plan ID: ' . $linkedPlan['id'] . "\n" .
                    $throwable->getMessage()
                );
            }
        }
    }

    public static function refundUser(
        $id_customer,
        $router_name,
        $plan_id,
        $gateway,
        $channel,
        $note = '',
        &$processedPlanIds = null,
        $isLinkedAction = false,
        $skipInvoiceNotification = false,
        &$refundSummary = null
    ) {
        global $admin, $_app_stage;

        $id_customer = (int) $id_customer;
        $planIdInt = (int) $plan_id;
        $router_name = trim((string) $router_name);
        $note = trim((string) $note);
        if (strlen($note) > 256) {
            $note = substr($note, 0, 256);
        }

        if ($processedPlanIds === null) {
            $processedPlanIds = [];
        }
        if ($refundSummary !== null && !is_array($refundSummary)) {
            $refundSummary = [];
        }
        if ($id_customer <= 0 || $planIdInt <= 0 || in_array($planIdInt, $processedPlanIds, true)) {
            return false;
        }
        $processedPlanIds[] = $planIdInt;
        if (is_array($refundSummary) && !isset($refundSummary['root_plan_id'])) {
            $refundSummary['root_plan_id'] = $planIdInt;
            $refundSummary['total_refund'] = 0.0;
            $refundSummary['primary_refund'] = 0.0;
            $refundSummary['linked_refund'] = 0.0;
            $refundSummary['plans'] = [];
            $refundSummary['primary_recorded'] = false;
        }

        $plan = ORM::for_table('tbl_plans')->where('id', $planIdInt)->find_one();
        if (!$plan) {
            return false;
        }
        if (!is_array($plan)) {
            $plan = $plan->as_array();
        }
        if (!$skipInvoiceNotification && isset($plan['invoice_notification']) && (int) $plan['invoice_notification'] === 0) {
            $skipInvoiceNotification = true;
        }

        $customer = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
        if (!$customer) {
            return false;
        }
        $customerData = $customer->as_array();

        $activeRecharge = self::findActiveRechargeRowForRefund($id_customer, $plan, $router_name);
        if (!$activeRecharge) {
            return false;
        }

        if ($router_name === '') {
            $router_name = trim((string) ($activeRecharge['routers'] ?? ''));
        }
        if ($router_name !== '' && trim((string) ($activeRecharge['routers'] ?? '')) !== $router_name) {
            return false;
        }

        run_hook('refund_user');

        $previousExpiryAt = self::resolveRechargeDateTime($activeRecharge['expiration'] ?? '', $activeRecharge['time'] ?? '');
        $newExpiryAt = self::subtractPlanValidityFromDateTime($previousExpiryAt, $plan);
        $newExpiryTs = strtotime($newExpiryAt);
        if ($newExpiryTs === false) {
            $newExpiryTs = time() - 1;
            $newExpiryAt = date('Y-m-d H:i:s', $newExpiryTs);
        }

        $now = time();
        $deactivate = self::isUnlimitedPlan($plan) || $newExpiryTs <= $now;
        $newDate = date('Y-m-d', $newExpiryTs);
        $newTime = date('H:i:s', $newExpiryTs);

        $deviceFile = self::getDevice($plan);
        if ($_app_stage != 'Demo') {
            try {
                if (file_exists($deviceFile)) {
                    require_once $deviceFile;
                    if ($deactivate) {
                        (new $plan['device'])->remove_customer($customerData, $plan);
                    } else {
                        (new $plan['device'])->add_customer($customerData, $plan);
                    }
                }
            } catch (Throwable $e) {
                Message::sendTelegram(
                    "System Error. When refund Package. You may need to sync manually\n" .
                    "Router: $router_name\n" .
                    "Customer: u{$customerData['username']}\n" .
                    "Plan: {$plan['name_plan']}\n" .
                    $e->getMessage() . "\n" .
                    $e->getTraceAsString()
                );
            }
        }

        $activeRecharge->recharged_on = date('Y-m-d');
        $activeRecharge->recharged_time = date('H:i:s');
        $activeRecharge->expiration = $newDate;
        $activeRecharge->time = $newTime;
        $activeRecharge->status = $deactivate ? 'off' : 'on';
        $activeRecharge->method = "$gateway - $channel";
        $activeRecharge->routers = $router_name;
        $activeRecharge->type = $plan['type'];
        $activeRecharge->admin_id = !empty($admin['id']) ? (int) $admin['id'] : 0;
        $activeRecharge->save();

        $totalRefund = self::calculateRefundCharge($id_customer, $plan, $gateway, $isLinkedAction);
        self::appendRefundSummary($refundSummary, $planIdInt, $totalRefund);
        $trxPrice = (strpos(strtolower((string) $gateway), 'zero') !== false) ? 0 : (-1 * $totalRefund);

        $transaction = ORM::for_table('tbl_transactions')->create();
        $transaction->invoice = $invoice = 'INV-' . self::_raid();
        $transaction->username = $customerData['username'];
        $transaction->user_id = $id_customer;
        $transaction->plan_name = $plan['name_plan'];
        $transaction->price = $trxPrice;
        $transaction->recharged_on = date('Y-m-d');
        $transaction->recharged_time = date('H:i:s');
        $transaction->expiration = $newDate;
        $transaction->time = $newTime;
        $transaction->method = "$gateway - $channel";
        $transaction->routers = $router_name;
        $transaction->type = $plan['type'];
        $transaction->admin_id = !empty($admin['id']) ? (int) $admin['id'] : 0;
        $trxNote = trim($note . "\nRefund from " . Lang::dateAndTimeFormat(date('Y-m-d', strtotime($previousExpiryAt)), date('H:i:s', strtotime($previousExpiryAt))) . " to " . Lang::dateAndTimeFormat($newDate, $newTime));
        if (strlen($trxNote) > 256) {
            $trxNote = substr($trxNote, 0, 256);
        }
        $transaction->note = $trxNote;
        $transaction->save();

        if (class_exists('PppoeUsage') && PppoeUsage::isStorageReady() && PppoeUsage::isSupportedPlan($plan)) {
            $rechargeId = (int) ($activeRecharge['id'] ?? 0);
            if ($rechargeId > 0) {
                PppoeUsage::cancelPendingCounterResetSchedules($rechargeId);
                if ($deactivate) {
                    PppoeUsage::closeCycleByRechargeId($rechargeId, date('Y-m-d H:i:s'));
                } else {
                    PppoeUsage::rescheduleCounterReset($rechargeId, $newExpiryAt, 'Refund: schedule new expiry reset');
                }
            }
        }

        if (!$isLinkedAction) {
            self::reverseLinkedPlans($id_customer, $gateway, $channel, $note, $processedPlanIds, $plan, $router_name, $refundSummary);
        }

        Message::sendTelegram(
            "#u{$customerData['username']} {$customerData['fullname']} #refund #{$plan['type']}\n" .
            $plan['name_plan'] .
            "\nRouter: " . $router_name .
            "\nGateway: " . $gateway .
            "\nChannel: " . $channel .
            "\nPrevious Expired: " . Lang::dateAndTimeFormat(date('Y-m-d', strtotime($previousExpiryAt)), date('H:i:s', strtotime($previousExpiryAt))) .
            "\nNew Expired: " . Lang::dateAndTimeFormat($newDate, $newTime) .
            "\nAmount: " . Lang::moneyFormat($trxPrice) .
            "\nStatus: " . ($deactivate ? 'OFF' : 'ON') .
            ($note !== '' ? "\nNote:\n" . $note : '')
        );

        run_hook('refund_user_finish');
        if (!$skipInvoiceNotification) {
            Message::sendInvoice($customerData, $transaction);
        }

        return $invoice;
    }

    public static function rechargeBalance($customer, $plan, $gateway, $channel, $note = '')
    {
        global $admin, $config;
        // insert table transactions
        $t = ORM::for_table('tbl_transactions')->create();
        $t->invoice = $inv = "INV-" . Package::_raid();
        $t->username = $customer['username'];
        $t->user_id = $customer['id'];
        $t->plan_name = $plan['name_plan'];
        $t->price = $plan['price'];
        $t->recharged_on = date("Y-m-d");
        $t->recharged_time = date("H:i:s");
        $t->expiration = date("Y-m-d");
        $t->time = date("H:i:s");
        $t->method = "$gateway - $channel";
        $t->routers = 'balance';
        $t->type = "Balance";
        $t->note = $note;
        if ($admin) {
            $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
        } else {
            $t->admin_id = '0';
        }
        $t->save();

        $balance_before = $customer['balance'];
        Balance::plus($customer['id'], $plan['price']);
        $balance = $customer['balance'] + $plan['price'];

	        // invoice_balance is treated as global (no per plan/category override).
	        $textInvoice = Lang::getNotifText('invoice_balance');
        $textInvoice = str_replace('[[company_name]]', $config['CompanyName'], $textInvoice);
        $textInvoice = str_replace('[[address]]', $config['address'], $textInvoice);
        $textInvoice = str_replace('[[phone]]', $config['phone'], $textInvoice);
        $textInvoice = str_replace('[[invoice]]', $inv, $textInvoice);
        $textInvoice = str_replace('[[date]]', Lang::dateTimeFormat(date("Y-m-d H:i:s")), $textInvoice);
        $textInvoice = str_replace('[[trx_date]]', Lang::dateTimeFormat(date("Y-m-d H:i:s")), $textInvoice);
        $textInvoice = str_replace('[[payment_gateway]]', $gateway, $textInvoice);
        $textInvoice = str_replace('[[payment_channel]]', $channel, $textInvoice);
        $textInvoice = str_replace('[[type]]', 'Balance', $textInvoice);
        $textInvoice = str_replace('[[plan_name]]', $plan['name_plan'], $textInvoice);
        $textInvoice = str_replace('[[plan_price]]', Lang::moneyFormat($plan['price']), $textInvoice);
        $textInvoice = str_replace('[[name]]', $customer['fullname'], $textInvoice);
        $textInvoice = str_replace('[[user_name]]', $customer['username'], $textInvoice);
        $textInvoice = str_replace('[[user_password]]', $customer['password'], $textInvoice);
        $textInvoice = str_replace('[[footer]]', $config['note'], $textInvoice);
        $textInvoice = str_replace('[[balance_before]]', Lang::moneyFormat($balance_before), $textInvoice);
        $textInvoice = str_replace('[[balance]]', Lang::moneyFormat($balance), $textInvoice);

        if ($config['user_notification_payment'] == 'sms') {
            Message::sendSMS($customer['phonenumber'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'wa') {
            $options = Message::isWhatsappQueueEnabledForNotificationTemplate('invoice_balance') ? ['queue' => true, 'queue_context' => 'invoice'] : [];
            Message::sendWhatsapp($customer['phonenumber'], $textInvoice, $options);
        } else if ($config['user_notification_payment'] == 'email') {
            Message::sendEmail($customer['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Invoice") . ' ' . $inv, $textInvoice);
        }
        return $t->id();
    }

    public static function rechargeCustomBalance($customer, $plan, $gateway, $channel, $note = '')
    {
        global $admin, $config;
        $plan = ORM::for_table('tbl_payment_gateway')
            ->where('username', $customer['username'])
            ->where('routers', 'Custom Balance')
            ->where('status', '1')
            ->find_one();
        if (!$plan) {
            return false;
        }
        // insert table transactions
        $t = ORM::for_table('tbl_transactions')->create();
        $t->invoice = $inv = "INV-" . Package::_raid();
        $t->username = $customer['username'];
        $t->user_id = $customer['id'];
        $t->plan_name = 'Custom Balance';
        $t->price = $plan['price'];
        $t->recharged_on = date("Y-m-d");
        $t->recharged_time = date("H:i:s");
        $t->expiration = date("Y-m-d");
        $t->time = date("H:i:s");
        $t->method = "$gateway - $channel";
        $t->routers = 'balance';
        $t->type = "Balance";
        $t->note = $note;
        if ($admin) {
            $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
        } else {
            $t->admin_id = '0';
        }
        $t->save();

        $balance_before = $customer['balance'];
        Balance::plus($customer['id'], $plan['price']);
        $balance = $customer['balance'] + $plan['price'];

	        // invoice_balance is treated as global (no per plan/category override).
	        $textInvoice = Lang::getNotifText('invoice_balance');
        $textInvoice = str_replace('[[company_name]]', $config['CompanyName'], $textInvoice);
        $textInvoice = str_replace('[[address]]', $config['address'], $textInvoice);
        $textInvoice = str_replace('[[phone]]', $config['phone'], $textInvoice);
        $textInvoice = str_replace('[[invoice]]', $inv, $textInvoice);
        $textInvoice = str_replace('[[date]]', Lang::dateTimeFormat(date("Y-m-d H:i:s")), $textInvoice);
        $textInvoice = str_replace('[[trx_date]]', Lang::dateTimeFormat(date("Y-m-d H:i:s")), $textInvoice);
        $textInvoice = str_replace('[[payment_gateway]]', $gateway, $textInvoice);
        $textInvoice = str_replace('[[payment_channel]]', $channel, $textInvoice);
        $textInvoice = str_replace('[[type]]', 'Balance', $textInvoice);
        $textInvoice = str_replace('[[plan_name]]', $plan['name_plan'], $textInvoice);
        $textInvoice = str_replace('[[plan_price]]', Lang::moneyFormat($plan['price']), $textInvoice);
        $textInvoice = str_replace('[[name]]', $customer['fullname'], $textInvoice);
        $textInvoice = str_replace('[[user_name]]', $customer['username'], $textInvoice);
        $textInvoice = str_replace('[[user_password]]', $customer['password'], $textInvoice);
        $textInvoice = str_replace('[[footer]]', $config['note'], $textInvoice);
        $textInvoice = str_replace('[[balance_before]]', Lang::moneyFormat($balance_before), $textInvoice);
        $textInvoice = str_replace('[[balance]]', Lang::moneyFormat($balance), $textInvoice);

        if ($config['user_notification_payment'] == 'sms') {
            Message::sendSMS($customer['phonenumber'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'wa') {
            $options = Message::isWhatsappQueueEnabledForNotificationTemplate('invoice_balance') ? ['queue' => true, 'queue_context' => 'invoice'] : [];
            Message::sendWhatsapp($customer['phonenumber'], $textInvoice, $options);
        } else if ($config['user_notification_payment'] == 'email') {
            Message::sendEmail($customer['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Invoice") . ' ' . $inv, $textInvoice);
        }
        return $t->id();
    }

    public static function _raid()
    {
        return ORM::for_table('tbl_transactions')->max('id') + 1;
    }

    /**
     * @param in   tbl_transactions
     * @param string $router_name router name for this package
     * @param int   $plan_id plan id for this package
     * @param string $gateway payment gateway name
     * @param string $channel channel payment gateway
     * @return boolean
     */
    public static function createInvoice($in)
    {
        global $config, $admin, $ui;
        $date = Lang::dateAndTimeFormat($in['recharged_on'], $in['recharged_time']);
        if ($admin['id'] != $in['admin_id'] && $in['admin_id'] > 0) {
            $_admin = Admin::_info($in['admin_id']);
            // if admin not deleted
            if ($_admin) $admin = $_admin;
        } else {
            $admin['fullname'] = 'Customer';
        }
        $cust = ORM::for_table('tbl_customers')->where('username', $in['username'])->findOne();

        $note = '';
        $noteLines = [];
        $showInvoiceNote = isset($config['show_invoice_note']) && $config['show_invoice_note'] == 'yes';
        //print
        $address = trim((string) $config['address']);
        if ($address !== '') {
            $addressLines = preg_split("/\r\n|\r|\n/", $address);
            $addressLines = array_values(array_filter($addressLines, static function ($line) {
                return trim($line) !== '';
            }));
            $address = implode("\n", $addressLines);
        }
        $invoice = Lang::pad($config['CompanyName'], ' ', 2) . "\n";
        $addressPadded = rtrim(Lang::pad($address, ' ', 2), "\n");
        $invoice .= $addressPadded . "\n";
        $invoice .= Lang::pad($config['phone'], ' ', 2) . "\n";
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pads("Invoice", $in['invoice'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Date'), $date, ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Sales'), $admin['fullname'], ' ') . "\n";
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pads(Lang::T('Type'), $in['type'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Plan Name'), $in['plan_name'], ' ') . "\n";
        if ($showInvoiceNote && !empty($in['note'])) {
            $in['note'] = str_replace("\r", "", $in['note']);
            $noteLines = explode("\n", $in['note']);
            foreach ($noteLines as $t) {
                if (strpos($t, " : ") === false) {
                    if (!empty($t)) {
                        $note .= "$t\n";
                    }
                } else {
                    $tmp2 = explode(" : ", $t);
                    $invoice .= Lang::pads($tmp2[0], $tmp2[1], ' ') . "\n";
                }
            }
        }
        $invoice .= Lang::pads(Lang::T('Total'), Lang::moneyFormat($in['price']), ' ') . "\n";
        $method = explode("-", $in['method']);
        $invoice .= Lang::pads($method[0], $method[1], ' ') . "\n";
        if (!empty($note)) {
            $invoice .= Lang::pad("", '=') . "\n";
            $invoice .= Lang::pad($note, ' ', 2) . "\n";
        }
        $invoice .= Lang::pad("", '=') . "\n";
        if ($cust) {
            $invoice .= Lang::pads(Lang::T('Full Name'), $cust['fullname'], ' ') . "\n";
        }
        $invoice .= Lang::pads(Lang::T('Username'), $in['username'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Password'), '**********', ' ') . "\n";
        if ($in['type'] != 'Balance') {
            $invoice .= Lang::pads(Lang::T('Created On'), Lang::dateAndTimeFormat($in['recharged_on'], $in['recharged_time']), ' ') . "\n";
            $invoice .= Lang::pads(Lang::T('Expires On'), Lang::dateAndTimeFormat($in['expiration'], $in['time']), ' ') . "\n";
        }
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pad($config['note'], ' ', 2) . "\n";
        $ui->assign('invoice', $invoice);
        $config['printer_cols'] = 30;
        //whatsapp
        $invoice = Lang::pad($config['CompanyName'], ' ', 2) . "\n";
        $addressPadded = rtrim(Lang::pad($address, ' ', 2), "\n");
        $invoice .= $addressPadded . "\n";
        $invoice .= Lang::pad($config['phone'], ' ', 2) . "\n";
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pads("Invoice", $in['invoice'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Date'), $date, ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Sales'), $admin['fullname'], ' ') . "\n";
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pads(Lang::T('Type'), $in['type'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Plan Name'), $in['plan_name'], ' ') . "\n";
        if ($showInvoiceNote && !empty($noteLines)) {
            $invoice .= Lang::pad("", '=') . "\n";
            foreach ($noteLines as $t) {
                if (strpos($t, " : ") === false) {
                    if (!empty($t)) {
                        $invoice .= Lang::pad($t, ' ', 2) . "\n";
                    }
                } else {
                    $tmp2 = explode(" : ", $t);
                    $invoice .= Lang::pads($tmp2[0], $tmp2[1], ' ') . "\n";
                }
            }
        }
        $invoice .= Lang::pads(Lang::T('Total'), Lang::moneyFormat($in['price']), ' ') . "\n";
        $invoice .= Lang::pads($method[0], $method[1], ' ') . "\n";
        if (!empty($note)) {
            $invoice .= Lang::pad("", '=') . "\n";
            $invoice .= Lang::pad($note, ' ', 2) . "\n";
        }
        $invoice .= Lang::pad("", '=') . "\n";
        if ($cust) {
            $invoice .= Lang::pads(Lang::T('Full Name'), $cust['fullname'], ' ') . "\n";
        }
        $invoice .= Lang::pads(Lang::T('Username'), $in['username'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Password'), '**********', ' ') . "\n";
        if ($in['type'] != 'Balance') {
            $invoice .= Lang::pads(Lang::T('Created On'), Lang::dateAndTimeFormat($in['recharged_on'], $in['recharged_time']), ' ') . "\n";
            $invoice .= Lang::pads(Lang::T('Expires On'), Lang::dateAndTimeFormat($in['expiration'], $in['time']), ' ') . "\n";
        }
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pad($config['note'], ' ', 2) . "\n";
        $ui->assign('whatsapp', urlencode("```$invoice```"));
        $ui->assign('in', $in);
    }
    public static function tax($price, $tax_rate = 1)
    {
        // Convert tax rate to decimal
        $tax_rate_decimal = $tax_rate / 100;
        $tax = $price * $tax_rate_decimal;
        return $tax;
    }

    public static function getDevice($plan)
    {
        global $DEVICE_PATH;
        if ($plan === false) {
            return "none";
        }
        if (!isset($plan['device'])) {
            return "none";
        }
        if (!empty($plan['device'])) {
            return $DEVICE_PATH . DIRECTORY_SEPARATOR . $plan['device'] . '.php';
        }
        if ($plan['is_radius'] == 1) {
            $plan->device = 'Radius';
            $plan->save();
            return $DEVICE_PATH . DIRECTORY_SEPARATOR . 'Radius' . '.php';
        }
        if ($plan['type'] == 'PPPOE') {
            $plan->device = 'MikrotikPppoe';
            $plan->save();
            return $DEVICE_PATH . DIRECTORY_SEPARATOR . 'MikrotikPppoe' . '.php';
        }
        $plan->device = 'MikrotikHotspot';
        $plan->save();
        return $DEVICE_PATH . DIRECTORY_SEPARATOR . 'MikrotikHotspot' . '.php';
    }
}
