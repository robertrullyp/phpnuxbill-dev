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

    protected static function activateLinkedPlans($customerId, $gateway, $channel, $note, array &$processedPlanIds, $plan, $skip, $primaryRouterName = '')
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
                    $shouldSkipInvoiceNotification
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

    protected static function isUnlimitedPeriodPlan($plan): bool
    {
        if (!$plan) {
            return false;
        }
        if (!is_array($plan)) {
            $plan = $plan->as_array();
        }
        return isset($plan['validity_unit'], $plan['validity'])
            && $plan['validity_unit'] === 'Period'
            && (int) $plan['validity'] <= 0;
    }

    protected static function getUnlimitedExpirationDate(): string
    {
        return '2099-12-31';
    }

    protected static function getUnlimitedExpirationTime(): string
    {
        return '23:59:59';
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
    public static function rechargeUser($id_customer, $router_name, $plan_id, $gateway, $channel, $note = '', &$processedPlanIds = null, $skipInvoiceNotification = false)
    {
        global $config, $admin, $c, $p, $b, $t, $d, $zero, $trx, $_app_stage, $isChangePlan;
        $transactionForNotification = null;
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
        $planIdInt = (int) $plan_id;
        if ($planIdInt <= 0 || in_array($planIdInt, $processedPlanIds, true)) {
            return false;
        }
        $processedPlanIds[] = $planIdInt;

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
        $isUnlimitedPeriod = self::isUnlimitedPeriodPlan($p);

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

        if ($p['validity_unit'] == 'Period' && !$isUnlimitedPeriod) {
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
            self::activateLinkedPlans($id_customer, $gateway, $channel, $note, $processedPlanIds, $p, $isVoucher, $router_name);
            return $result;
        }

        if ($router_name == 'Custom Balance') {
            $result = self::rechargeCustomBalance($c, $p, $gateway, $channel, $note);
            self::activateLinkedPlans($id_customer, $gateway, $channel, $note, $processedPlanIds, $p, $isVoucher, $router_name);
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

        if ($isUnlimitedPeriod) {
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
            $lastExpired = Lang::dateAndTimeFormat($b['expiration'], $b['time']);
            $isChangePlan = false;
            if ($b['namebp'] == $p['name_plan'] && $b['status'] == 'on' && $config['extend_expiry'] == 'yes') {
                // if it same internet plan, expired will extend
                switch ($p['validity_unit']) {
                    case 'Months':
                        $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                        $time = $b['time'];
                        break;
                    case 'Period':
                        if ($isUnlimitedPeriod) {
                            $date_exp = self::getUnlimitedExpirationDate();
                            $time = self::getUnlimitedExpirationTime();
                        } else {
                            $date_exp = date("Y-m-$day_exp", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                            $time = date("23:59:00");
                        }
                        break;
                    case 'Days':
                        $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' days'));
                        $time = $b['time'];
                        break;
                    case 'Hrs':
                        $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($b['expiration'] . ' ' . $b['time'] . ' +' . $p['validity'] . ' hours')));
                        $date_exp = $datetime[0];
                        $time = $datetime[1];
                        break;
                    case 'Mins':
                        $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($b['expiration'] . ' ' . $b['time'] . ' +' . $p['validity'] . ' minutes')));
                        $date_exp = $datetime[0];
                        $time = $datetime[1];
                        break;
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
                if ($p['validity_unit'] == 'Period' && !$isUnlimitedPeriod) {
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

            if ($p['validity_unit'] == 'Period' && !$isUnlimitedPeriod) {
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
                if ($p['validity_unit'] == 'Period' && !$isUnlimitedPeriod) {
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

            if ($p['validity_unit'] == 'Period' && !$isUnlimitedPeriod && (int) $p['validity'] > 0 && $p['price'] != 0) {
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
        self::activateLinkedPlans($id_customer, $gateway, $channel, $note, $processedPlanIds, $p, $isVoucher, $router_name);
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
