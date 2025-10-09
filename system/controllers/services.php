<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/
_admin();
$ui->assign('_title', Lang::T('Hotspot Plans'));
$ui->assign('_system_menu', 'services');

$action = $routes['1'];
$ui->assign('_admin', $admin);

// Ensure DB enum supports 'exclude' to keep visibility in sync when saving
if (!function_exists('ensureVisibilityEnumSupportsExclude')) {
    function ensureVisibilityEnumSupportsExclude()
    {
        static $done = false;
        if ($done) {
            return;
        }
        try {
            $db = ORM::get_db();
            if ($db) {
                $stmt = $db->query("SHOW COLUMNS FROM `tbl_plans` LIKE 'visibility'");
                if ($stmt) {
                    $col = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$col) {
                        // Add the column if missing
                        $db->exec("ALTER TABLE `tbl_plans` ADD `visibility` ENUM('all','custom','exclude') NOT NULL DEFAULT 'all' COMMENT 'plan visibility for customers' AFTER `prepaid`");
                    } elseif (isset($col['Type']) && strpos($col['Type'], "'exclude'") === false) {
                        // Add 'exclude' into enum if missing
                        $db->exec("ALTER TABLE `tbl_plans` MODIFY `visibility` ENUM('all','custom','exclude') NOT NULL DEFAULT 'all' COMMENT 'plan visibility for customers'");
                    }
                }
            }
        } catch (Exception $e) {
            // silently ignore; do not block UI if DB user has no alter privilege
        }
        $done = true;
    }
}
ensureVisibilityEnumSupportsExclude();

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

switch ($action) {
    case 'sync':
        set_time_limit(-1);
        $target = isset($routes['2']) ? strtolower($routes['2']) : '';
        $syncTargets = [
            'hotspot' => ['Hotspot', 'services/hotspot'],
            'pppoe'   => ['PPPOE', 'services/pppoe'],
            'vpn'     => ['VPN', 'services/vpn'],
        ];

        if (!isset($syncTargets[$target])) {
            r2(getUrl('services/hotspot'), 'w', 'Unknown command');
        }

        list($planType, $redirectRoute) = $syncTargets[$target];
        $plans = ORM::for_table('tbl_plans')->where('type', $planType)->find_many();
        $log = '';

        foreach ($plans as $plan) {
            $dvc = Package::getDevice($plan);
            if ($_app_stage != 'demo') {
                if (!empty($dvc) && file_exists($dvc)) {
                    require_once $dvc;
                    (new $plan['device'])->add_plan($plan);
                    $log .= "DONE : $plan[name_plan], $plan[device]<br>";
                } else {
                    $log .= "FAILED : $plan[name_plan], $plan[device] | Device Not Found<br>";
                }
            }
        }

        r2(getUrl($redirectRoute), 's', $log);
    case 'hotspot':
        $name = _req('name');
        $type1 = _req('type1');
        $type2 = _req('type2');
        $type3 = _req('type3');
        $bandwidth = _req('bandwidth');
        $valid = _req('valid');
        $device = _req('device');
        $status = _req('status');
        $router = _req('router');
        $ui->assign('type1', $type1);
        $ui->assign('type2', $type2);
        $ui->assign('type3', $type3);
        $ui->assign('bandwidth', $bandwidth);
        $ui->assign('valid', $valid);
        $ui->assign('device', $device);
        $ui->assign('status', $status);
        $ui->assign('router', $router);

        $append_url = "&type1=" . urlencode($type1)
            . "&type2=" . urlencode($type2)
            . "&type3=" . urlencode($type3)
            . "&bandwidth=" . urlencode($bandwidth)
            . "&valid=" . urlencode($valid)
            . "&device=" . urlencode($device)
            . "&status=" . urlencode($status)
            . "&router=" . urlencode($router);

        $bws = ORM::for_table('tbl_plans')->distinct()->select("id_bw")->where('tbl_plans.type', 'Hotspot')->findArray();
        $ids = array_column($bws, 'id_bw');
        if (count($ids)) {
            $ui->assign('bws', ORM::for_table('tbl_bandwidth')->select("id")->select('name_bw')->where_id_in($ids)->findArray());
        } else {
            $ui->assign('bws', []);
        }
        $ui->assign('type2s', ORM::for_table('tbl_plans')->getEnum("plan_type"));
        $ui->assign('type3s', ORM::for_table('tbl_plans')->getEnum("typebp"));
        $ui->assign('valids', ORM::for_table('tbl_plans')->getEnum("validity_unit"));
        $ui->assign('routers', array_column(ORM::for_table('tbl_plans')->distinct()->select("routers")->where('tbl_plans.type', 'Hotspot')->whereNotEqual('routers', '')->findArray(), 'routers'));
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $query = ORM::for_table('tbl_bandwidth')
            ->left_outer_join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))
            ->where('tbl_plans.type', 'Hotspot');

        if (!empty($type1)) {
            $query->where('tbl_plans.prepaid', $type1);
        }
        if (!empty($type2)) {
            $query->where('tbl_plans.plan_type', $type2);
        }
        if (!empty($type3)) {
            $query->where('tbl_plans.typebp', $type3);
        }
        if (!empty($bandwidth)) {
            $query->where('tbl_plans.id_bw', $bandwidth);
        }
        if (!empty($valid)) {
            $query->where('tbl_plans.validity_unit', $valid);
        }
        if (!empty($router)) {
            if ($router == 'radius') {
                $query->where('tbl_plans.is_radius', '1');
            } else {
                $query->where('tbl_plans.routers', $router);
            }
        }
        if (!empty($device)) {
            $query->where('tbl_plans.device', $device);
        }
        if (in_array($status, ['0', '1'])) {
            $query->where('tbl_plans.enabled', $status);
        }
        if ($name != '') {
            $query->where_like('tbl_plans.name_plan', '%' . $name . '%');
        }
        $d = Paginator::findMany($query, ['name' => $name], 20, $append_url);
        $ui->assign('d', $d);
        // Build visibility counts for labels in list view
        $visibility_counts = [];
        $ids = [];
        foreach ($d as $row) { $ids[] = $row['id']; }
        if (count($ids)) {
            $rows = ORM::for_table('tbl_plan_customers')
                ->select('plan_id')
                ->select_expr('COUNT(*)', 'cnt')
                ->where_in('plan_id', $ids)
                ->group_by('plan_id')
                ->find_array();
            foreach ($rows as $r) { $visibility_counts[$r['plan_id']] = (int)$r['cnt']; }
        }
        $ui->assign('visibility_counts', $visibility_counts);
        // Map plan id -> visibility
        $visibility_map = [];
        if (count($ids)) {
            $vrows = ORM::for_table('tbl_plans')->select('id')->select('visibility')->where_in('id', $ids)->find_array();
            foreach ($vrows as $vr) { $visibility_map[$vr['id']] = $vr['visibility']; }
        }
        $ui->assign('visibility_map', $visibility_map);
        run_hook('view_list_plans'); #HOOK
        $ui->display('admin/hotspot/list.tpl');
        break;
    case 'add':
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        $ui->assign('last_visibility', isset($_SESSION['last_visibility']) ? $_SESSION['last_visibility'] : 'all');
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $ui->assign('plan_options', Package::getPlanOptionsList());
        $ui->assign('selected_linked_plans', []);
        run_hook('view_add_plan'); #HOOK
        $ui->display('admin/hotspot/add.tpl');
        break;

    case 'edit':
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            if (empty($d['device'])) {
                if ($d['is_radius']) {
                    $d->device = 'Radius';
                } else {
                    $d->device = 'MikrotikHotspot';
                }
                $d->save();
            }
            $ui->assign('d', $d);
            // Preload selected customers for visibility
            $assigned = ORM::for_table('tbl_plan_customers')->select('customer_id')->where('plan_id', $id)->find_array();
            $selectedIds = array_column($assigned, 'customer_id');
            $selectedCustomers = [];
            if (!empty($selectedIds)) {
                $selectedCustomers = ORM::for_table('tbl_customers')->where_in('id', $selectedIds)->find_array();
            }
            $ui->assign('visible_customer_options', $selectedCustomers);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $devices = [];
            $files = scandir($DEVICE_PATH);
            foreach ($files as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext == 'php') {
                    $devices[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
            $ui->assign('devices', $devices);
            $ui->assign('plan_options', Package::getPlanOptionsList($id));
            $ui->assign('selected_linked_plans', Package::getLinkedPlanIds($id));
            //select expired plan
            if ($d['is_radius']) {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'Hotspot')->where("is_radius", 1)->findArray();
            } else {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'Hotspot')->where("routers", $d['routers'])->findArray();
            }
            $ui->assign('exps', $exps);
            run_hook('view_edit_plan'); #HOOK
            $ui->display('admin/hotspot/edit.tpl');
        } else {
            r2(getUrl('services/hotspot'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_plan'); #HOOK
            Package::removePlanLinks($id);
            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->remove_plan($d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            $d->delete();

            r2(getUrl('services/hotspot'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'add-post':
        $name = _post('name');
        $plan_type = _post('plan_type'); //Personal / Business
        $radius = _post('radius');
        $typebp = _post('typebp');
        $limit_type = _post('limit_type');
        $time_limit = _post('time_limit');
        $time_unit = _post('time_unit');
        $data_limit = _post('data_limit');
        $data_unit = _post('data_unit');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $sharedusers = _post('sharedusers');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');
        $visibilityInput = _post('visibility', null);
        $visibility = Package::normalizeVisibility($visibilityInput);
        $_SESSION['last_visibility'] = $visibility ?? 'all';
        $reminderEnabled = isset($_POST['reminder_enabled']) ? (int) $_POST['reminder_enabled'] : 0;
        $invoiceNotification = isset($_POST['invoice_notification']) ? (int) $_POST['invoice_notification'] : 0;
        $linkedPlans = $_POST['linked_plans'] ?? null;

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if (empty($radius)) {
            if ($routers == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }
        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->where('type', 'Hotspot')->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }

        run_hook('add_plan'); #HOOK

        if ($msg == '') {
            // Create new plan
            $d = ORM::for_table('tbl_plans')->create();
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price; // Set price with or without tax based on configuration
            $d->type = 'Hotspot';
            $d->typebp = $typebp;
            $d->plan_type = $plan_type;
            $d->limit_type = $limit_type;
            $d->time_limit = $time_limit;
            $d->time_unit = $time_unit;
            $d->data_limit = $data_limit;
            $d->data_unit = $data_unit;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->shared_users = $sharedusers;
            if (!empty($radius)) {
                $d->is_radius = 1;
                $d->routers = '';
            } else {
                $d->is_radius = 0;
                $d->routers = $routers;
            }
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->reminder_enabled = $reminderEnabled ? 1 : 0;
            $d->invoice_notification = $invoiceNotification ? 1 : 0;
            // set visibility for new plan
            $d->visibility = $visibility;
            $d->device = $device;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 20;
            }
            $d->save();

            $linkedPlanIds = Package::normalizeLinkedPlanIds($linkedPlans);
            Package::syncLinkedPlans($d->id(), $linkedPlanIds);

            // Save visibility mapping for custom/exclude selections
            if (in_array($d['visibility'], ['custom','exclude'])) {
                $selected = isset($_POST['visible_customers']) ? (array)$_POST['visible_customers'] : [];
                $selected = array_filter(array_unique(array_map('intval', $selected)));
                if (!empty($selected)) {
                    foreach ($selected as $cid) {
                        $m = ORM::for_table('tbl_plan_customers')->create();
                        $m->plan_id = (int)$d->id();
                        $m->customer_id = $cid;
                        $m->save();
                    }
                }
            }

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->add_plan($d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            r2(getUrl('services/edit/') . $d->id(), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('services/add'), 'e', $msg);
        }
        break;


    case 'edit-post':
        $id = _post('id');
        $name = _post('name');
        $plan_type = _post('plan_type');
        $id_bw = _post('id_bw');
        $typebp = _post('typebp');
        $price = _post('price');
        $price_old = _post('price_old');
        $limit_type = _post('limit_type');
        $time_limit = _post('time_limit');
        $time_unit = _post('time_unit');
        $data_limit = _post('data_limit');
        $data_unit = _post('data_unit');
        $sharedusers = _post('sharedusers');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $plan_expired = _post('plan_expired', '0');
        $device = _post('device');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $routers = _post('routers');
        $on_login = _post('on_login');
        $on_logout = _post('on_logout');
        $expired_date = _post('expired_date');
        $visibilityInput = _post('visibility', null);
        $visibility = Package::normalizeVisibility($visibilityInput);
        $_SESSION['last_visibility'] = $visibility ?? 'all';
        $linkedPlans = $_POST['linked_plans'] ?? null;
        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        $old = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($price_old <= $price) {
            $price_old = '';
        }

        run_hook('edit_plan'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];

            $rate = trim($rate . " " . $b['burst']);

            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price; // Set price with or without tax based on configuration
            $d->price_old = $price_old;
            $d->typebp = $typebp;
            $d->limit_type = $limit_type;
            $d->time_limit = $time_limit;
            $d->time_unit = $time_unit;
            $d->data_limit = $data_limit;
            $d->plan_type = $plan_type;
            $d->data_unit = $data_unit;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->shared_users = $sharedusers;
            $d->plan_expired = $plan_expired;
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->reminder_enabled = $reminderEnabled ? 1 : 0;
            $d->visibility = $visibility;
            $d->on_login = $on_login;
            $d->on_logout = $on_logout;
            $d->device = $device;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 20;
            }
            $d->save();

            $linkedPlanIds = Package::normalizeLinkedPlanIds($linkedPlans);
            Package::syncLinkedPlans($id, $linkedPlanIds);

            // Update visibility mapping
            ORM::for_table('tbl_plan_customers')->where('plan_id', $id)->delete_many();
            if (in_array($d['visibility'], ['custom','exclude'])) {
                $selected = isset($_POST['visible_customers']) ? (array)$_POST['visible_customers'] : [];
                $selected = array_filter(array_unique(array_map('intval', $selected)));
                foreach ($selected as $cid) {
                    $m = ORM::for_table('tbl_plan_customers')->create();
                    $m->plan_id = $id;
                    $m->customer_id = $cid;
                    $m->save();
                }
            }

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->update_plan($old, $d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            r2(getUrl('services/hotspot'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('services/edit/') . $id, 'e', $msg);
        }
        break;

    case 'pppoe':
        $ui->assign('_title', Lang::T('PPPOE Plans'));

        $name = _post('name');
        $name = _req('name');
        $type1 = _req('type1');
        $type2 = _req('type2');
        $type3 = _req('type3');
        $bandwidth = _req('bandwidth');
        $valid = _req('valid');
        $device = _req('device');
        $status = _req('status');
        $router = _req('router');
        $ui->assign('type1', $type1);
        $ui->assign('type2', $type2);
        $ui->assign('type3', $type3);
        $ui->assign('bandwidth', $bandwidth);
        $ui->assign('valid', $valid);
        $ui->assign('device', $device);
        $ui->assign('status', $status);
        $ui->assign('router', $router);

        $append_url = "&type1=" . urlencode($type1)
            . "&type2=" . urlencode($type2)
            . "&type3=" . urlencode($type3)
            . "&bandwidth=" . urlencode($bandwidth)
            . "&valid=" . urlencode($valid)
            . "&device=" . urlencode($device)
            . "&status=" . urlencode($status)
            . "&router=" . urlencode($router);

        $bws = ORM::for_table('tbl_plans')->distinct()->select("id_bw")->where('tbl_plans.type', 'PPPOE')->findArray();
        $ids = array_column($bws, 'id_bw');
        if (count($ids)) {
            $ui->assign('bws', ORM::for_table('tbl_bandwidth')->select("id")->select('name_bw')->where_id_in($ids)->findArray());
        } else {
            $ui->assign('bws', []);
        }
        $ui->assign('type2s', ORM::for_table('tbl_plans')->getEnum("plan_type"));
        $ui->assign('type3s', ORM::for_table('tbl_plans')->getEnum("typebp"));
        $ui->assign('valids', ORM::for_table('tbl_plans')->getEnum("validity_unit"));
        $ui->assign('routers', array_column(ORM::for_table('tbl_plans')->distinct()->select("routers")->whereNotEqual('routers', '')->findArray(), 'routers'));
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $query = ORM::for_table('tbl_bandwidth')
            ->left_outer_join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))
            ->where('tbl_plans.type', 'PPPOE');
        if (!empty($type1)) {
            $query->where('tbl_plans.prepaid', $type1);
        }
        if (!empty($type2)) {
            $query->where('tbl_plans.plan_type', $type2);
        }
        if (!empty($type3)) {
            $query->where('tbl_plans.typebp', $type3);
        }
        if (!empty($bandwidth)) {
            $query->where('tbl_plans.id_bw', $bandwidth);
        }
        if (!empty($valid)) {
            $query->where('tbl_plans.validity_unit', $valid);
        }
        if (!empty($router)) {
            if ($router == 'radius') {
                $query->where('tbl_plans.is_radius', '1');
            } else {
                $query->where('tbl_plans.routers', $router);
            }
        }
        if (!empty($device)) {
            $query->where('tbl_plans.device', $device);
        }
        if (in_array($status, ['0', '1'])) {
            $query->where('tbl_plans.enabled', $status);
        }
        if ($name != '') {
            $query->where_like('tbl_plans.name_plan', '%' . $name . '%');
        }
        $d = Paginator::findMany($query, ['name' => $name], 20, $append_url);

        $ui->assign('d', $d);
        // Visibility counts for PPPoE list
        $visibility_counts = [];
        $ids = [];
        foreach ($d as $row) { $ids[] = $row['id']; }
        if (count($ids)) {
            $rows = ORM::for_table('tbl_plan_customers')
                ->select('plan_id')
                ->select_expr('COUNT(*)', 'cnt')
                ->where_in('plan_id', $ids)
                ->group_by('plan_id')
                ->find_array();
            foreach ($rows as $r) { $visibility_counts[$r['plan_id']] = (int)$r['cnt']; }
        }
        $ui->assign('visibility_counts', $visibility_counts);
        $visibility_map = [];
        if (count($ids)) {
            $vrows = ORM::for_table('tbl_plans')->select('id')->select('visibility')->where_in('id', $ids)->find_array();
            foreach ($vrows as $vr) { $visibility_map[$vr['id']] = $vr['visibility']; }
        }
        $ui->assign('visibility_map', $visibility_map);
        run_hook('view_list_ppoe'); #HOOK
        $ui->display('admin/pppoe/list.tpl');
        break;

    case 'pppoe-add':
        $ui->assign('_title', Lang::T('PPPOE Plans'));
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        $ui->assign('last_visibility', isset($_SESSION['last_visibility']) ? $_SESSION['last_visibility'] : 'all');
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $ui->assign('plan_options', Package::getPlanOptionsList());
        $ui->assign('selected_linked_plans', []);
        run_hook('view_add_ppoe'); #HOOK
        $ui->display('admin/pppoe/add.tpl');
        break;

    case 'pppoe-edit':
        $ui->assign('_title', Lang::T('PPPOE Plans'));
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            if (empty($d['device'])) {
                if ($d['is_radius']) {
                    $d->device = 'Radius';
                } else {
                    $d->device = 'MikrotikPppoe';
                }
                $d->save();
            }
            $ui->assign('d', $d);
            $p = ORM::for_table('tbl_pool')->where('routers', ($d['is_radius']) ? 'radius' : $d['routers'])->find_many();
            $ui->assign('p', $p);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $r = [];
            if ($d['is_radius']) {
                $r = ORM::for_table('tbl_routers')->find_many();
            }
            $ui->assign('r', $r);
            $devices = [];
            $files = scandir($DEVICE_PATH);
            foreach ($files as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext == 'php') {
                    $devices[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
            $ui->assign('devices', $devices);
            $ui->assign('plan_options', Package::getPlanOptionsList($id));
            $ui->assign('selected_linked_plans', Package::getLinkedPlanIds($id));
            //select expired plan
            if ($d['is_radius']) {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'PPPOE')->where("is_radius", 1)->findArray();
            } else {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'PPPOE')->where("routers", $d['routers'])->findArray();
            }
            $ui->assign('exps', $exps);
            // Preload selected customers for visibility
            $assigned = ORM::for_table('tbl_plan_customers')->select('customer_id')->where('plan_id', $id)->find_array();
            $selectedIds = array_column($assigned, 'customer_id');
            $selectedCustomers = [];
            if (!empty($selectedIds)) {
                $selectedCustomers = ORM::for_table('tbl_customers')->where_in('id', $selectedIds)->find_array();
            }
            $ui->assign('visible_customer_options', $selectedCustomers);
            run_hook('view_edit_ppoe'); #HOOK
            $ui->display('admin/pppoe/edit.tpl');
        } else {
            r2(getUrl('services/pppoe'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'pppoe-delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_ppoe'); #HOOK
            Package::removePlanLinks($id);

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->remove_plan($d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            $d->delete();

            r2(getUrl('services/pppoe'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'pppoe-add-post':
        $name = _post('name_plan');
        $plan_type = _post('plan_type');
        $radius = _post('radius');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $pool = _post('pool_name');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');
        $reminderEnabled = isset($_POST['reminder_enabled']) ? (int) $_POST['reminder_enabled'] : 0;
        $invoiceNotification = isset($_POST['invoice_notification']) ? (int) $_POST['invoice_notification'] : 0;
        $linkedPlans = $_POST['linked_plans'] ?? null;
        $visibilityInput = _post('visibility', null);
        $visibility = Package::normalizeVisibility($visibilityInput);
        $_SESSION['last_visibility'] = $visibility ?? 'all';


        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if (empty($radius)) {
            if ($routers == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }
        run_hook('add_ppoe'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];
            $rate = trim($rate . " " . $b['burst']);
            $d = ORM::for_table('tbl_plans')->create();
            $d->type = 'PPPOE';
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->plan_type = $plan_type;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->pool = $pool;
            if (!empty($radius)) {
                $d->is_radius = 1;
                $d->routers = '';
            } else {
                $d->is_radius = 0;
                $d->routers = $routers;
            }
            $d->plan_expired = (int)$plan_expired;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 0;
            }
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->reminder_enabled = $reminderEnabled ? 1 : 0;
            $d->invoice_notification = $invoiceNotification ? 1 : 0;
            $d->visibility = $visibility;
            $d->device = $device;
            $d->save();

            $linkedPlanIds = Package::normalizeLinkedPlanIds($linkedPlans);
            Package::syncLinkedPlans($d->id(), $linkedPlanIds);

            // Handle custom visibility mapping for PPPoE plan
            if (in_array($d['visibility'], ['custom','exclude'])) {
                $selected = isset($_POST['visible_customers']) ? (array)$_POST['visible_customers'] : [];
                $selected = array_filter(array_unique(array_map('intval', $selected)));
                foreach ($selected as $cid) {
                    $m = ORM::for_table('tbl_plan_customers')->create();
                    $m->plan_id = $d->id();
                    $m->customer_id = $cid;
                    $m->save();
                }
            }

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->add_plan($d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            r2(getUrl('services/pppoe'), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('services/pppoe-add'), 'e', $msg);
        }
        break;

    case 'edit-pppoe-post':
        $id = _post('id');
        $plan_type = _post('plan_type');
        $name = _post('name_plan');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $price_old = _post('price_old');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $pool = _post('pool_name');
        $plan_expired = _post('plan_expired');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');
        $visibilityInput = _post('visibility', null);
        $visibility = Package::normalizeVisibility($visibilityInput);
        $_SESSION['last_visibility'] = $visibility ?? 'all';
        $on_login = _post('on_login');
        $on_logout = _post('on_logout');
        $reminderEnabled = isset($_POST['reminder_enabled']) ? (int) $_POST['reminder_enabled'] : 0;
        $invoiceNotification = isset($_POST['invoice_notification']) ? (int) $_POST['invoice_notification'] : 0;
        $linkedPlans = $_POST['linked_plans'] ?? null;

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        if ($price_old <= $price) {
            $price_old = '';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        $old = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        run_hook('edit_ppoe'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];
            $rate = trim($rate . " " . $b['burst']);

            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->price_old = $price_old;
            $d->plan_type = $plan_type;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->routers = $routers;
            $d->pool = $pool;
            $d->plan_expired = $plan_expired;
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            $d->on_login = $on_login;
            $d->on_logout = $on_logout;
            $d->reminder_enabled = $reminderEnabled ? 1 : 0;
            $d->invoice_notification = $invoiceNotification ? 1 : 0;
            $d->visibility = $visibility;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 0;
            }
            $d->save();

            $linkedPlanIds = Package::normalizeLinkedPlanIds($linkedPlans);
            Package::syncLinkedPlans($id, $linkedPlanIds);

            // Update visibility mapping
            ORM::for_table('tbl_plan_customers')->where('plan_id', $id)->delete_many();
            if (in_array($d['visibility'], ['custom','exclude'])) {
                $selected = isset($_POST['visible_customers']) ? (array)$_POST['visible_customers'] : [];
                $selected = array_filter(array_unique(array_map('intval', $selected)));
                foreach ($selected as $cid) {
                    $m = ORM::for_table('tbl_plan_customers')->create();
                    $m->plan_id = $id;
                    $m->customer_id = $cid;
                    $m->save();
                }
            }

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->update_plan($old, $d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            r2(getUrl('services/pppoe'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('services/pppoe-edit/') . $id, 'e', $msg);
        }
        break;
    case 'balance':
        $ui->assign('_title', Lang::T('Balance Plans'));
        $name = _post('name');
        if ($name != '') {
            $query = ORM::for_table('tbl_plans')->where('tbl_plans.type', 'Balance')->where_like('tbl_plans.name_plan', '%' . $name . '%');
            $d = Paginator::findMany($query, ['name' => $name]);
        } else {
            $query = ORM::for_table('tbl_plans')->where('tbl_plans.type', 'Balance');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        // Visibility counts for Balance list
        $visibility_counts = [];
        $ids = [];
        foreach ($d as $row) { $ids[] = $row['id']; }
        if (count($ids)) {
            $rows = ORM::for_table('tbl_plan_customers')
                ->select('plan_id')
                ->select_expr('COUNT(*)', 'cnt')
                ->where_in('plan_id', $ids)
                ->group_by('plan_id')
                ->find_array();
            foreach ($rows as $r) { $visibility_counts[$r['plan_id']] = (int)$r['cnt']; }
        }
        $ui->assign('visibility_counts', $visibility_counts);
        $visibility_map = [];
        if (count($ids)) {
            $vrows = ORM::for_table('tbl_plans')->select('id')->select('visibility')->where_in('id', $ids)->find_array();
            foreach ($vrows as $vr) { $visibility_map[$vr['id']] = $vr['visibility']; }
        }
        $ui->assign('visibility_map', $visibility_map);
        run_hook('view_list_balance'); #HOOK
        $ui->display('admin/balance/list.tpl');
        break;
    case 'balance-add':
        $ui->assign('_title', Lang::T('Balance Plans'));
        $ui->assign('last_visibility', isset($_SESSION['last_visibility']) ? $_SESSION['last_visibility'] : 'all');
        $ui->assign('plan_options', Package::getPlanOptionsList());
        $ui->assign('selected_linked_plans', []);
        run_hook('view_add_balance'); #HOOK
        $ui->display('admin/balance/add.tpl');
        break;
    case 'balance-edit':
        $ui->assign('_title', Lang::T('Balance Plans'));
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        $ui->assign('d', $d);
        $ui->assign('plan_options', Package::getPlanOptionsList($id));
        $ui->assign('selected_linked_plans', Package::getLinkedPlanIds($id));
        // Preload selected customers for visibility
        $assigned = ORM::for_table('tbl_plan_customers')->select('customer_id')->where('plan_id', $id)->find_array();
        $selectedIds = array_column($assigned, 'customer_id');
        $selectedCustomers = [];
        if (!empty($selectedIds)) {
            $selectedCustomers = ORM::for_table('tbl_customers')->where_in('id', $selectedIds)->find_array();
        }
        $ui->assign('visible_customer_options', $selectedCustomers);
        run_hook('view_edit_balance'); #HOOK
        $ui->display('admin/balance/edit.tpl');
        break;
    case 'balance-delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_balance'); #HOOK
            Package::removePlanLinks($id);
            $d->delete();
            r2(getUrl('services/balance'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;
    case 'balance-edit-post':
        $id = _post('id');
        $name = _post('name');
        $price = _post('price');
        $price_old = _post('price_old');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $visibilityInput = _post('visibility', null);
        $visibility = Package::normalizeVisibility($visibilityInput);
        $_SESSION['last_visibility'] = $visibility ?? 'all';
        $reminderEnabled = isset($_POST['reminder_enabled']) ? (int) $_POST['reminder_enabled'] : 0;
        $invoiceNotification = isset($_POST['invoice_notification']) ? (int) $_POST['invoice_notification'] : 0;
        $linkedPlans = $_POST['linked_plans'] ?? null;

        $msg = '';
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        if ($price_old <= $price) {
            $price_old = '';
        }
        run_hook('edit_ppoe'); #HOOK
        if ($msg == '') {
            $d->name_plan = $name;
            $d->price = $price;
            $d->enabled = $enabled;
            $d->price_old = $price_old;
            $d->prepaid = 'yes';
            $d->reminder_enabled = $reminderEnabled ? 1 : 0;
            $d->invoice_notification = $invoiceNotification ? 1 : 0;
            $d->visibility = $visibility;
            $d->save();

            $linkedPlanIds = Package::normalizeLinkedPlanIds($linkedPlans);
            Package::syncLinkedPlans($id, $linkedPlanIds);

            // Update visibility mapping
            ORM::for_table('tbl_plan_customers')->where('plan_id', $id)->delete_many();
            if (in_array($d['visibility'], ['custom','exclude'])) {
                $selected = isset($_POST['visible_customers']) ? (array)$_POST['visible_customers'] : [];
                $selected = array_filter(array_unique(array_map('intval', $selected)));
                foreach ($selected as $cid) {
                    $m = ORM::for_table('tbl_plan_customers')->create();
                    $m->plan_id = $id;
                    $m->customer_id = $cid;
                    $m->save();
                }
            }

            r2(getUrl('services/balance'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('services/balance-edit/') . $id, 'e', $msg);
        }
        break;
    case 'balance-add-post':
        $name = _post('name');
        $price = _post('price');
        $enabled = _post('enabled');
        $visibilityInput = _post('visibility', null);
        $visibility = Package::normalizeVisibility($visibilityInput);
        $_SESSION['last_visibility'] = $visibility ?? 'all';
        $reminderEnabled = isset($_POST['reminder_enabled']) ? (int) $_POST['reminder_enabled'] : 0;
        $invoiceNotification = isset($_POST['invoice_notification']) ? (int) $_POST['invoice_notification'] : 0;
        $linkedPlans = $_POST['linked_plans'] ?? null;

        $msg = '';
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }
        run_hook('add_ppoe'); #HOOK
        if ($msg == '') {
            $d = ORM::for_table('tbl_plans')->create();
            $d->type = 'Balance';
            $d->name_plan = $name;
            $d->id_bw = 0;
            $d->price = $price;
            $d->validity = 0;
            $d->validity_unit = 'Months';
            $d->routers = '';
            $d->pool = '';
            $d->enabled = $enabled;
            $d->prepaid = 'yes';
            $d->reminder_enabled = $reminderEnabled ? 1 : 0;
            $d->invoice_notification = $invoiceNotification ? 1 : 0;
            $d->visibility = $visibility;
            $d->save();

            $linkedPlanIds = Package::normalizeLinkedPlanIds($linkedPlans);
            Package::syncLinkedPlans($d->id(), $linkedPlanIds);

            if (in_array($d['visibility'], ['custom','exclude'])) {
                $selected = isset($_POST['visible_customers']) ? (array)$_POST['visible_customers'] : [];
                $selected = array_filter(array_unique(array_map('intval', $selected)));
                foreach ($selected as $cid) {
                    $m = ORM::for_table('tbl_plan_customers')->create();
                    $m->plan_id = $d->id();
                    $m->customer_id = $cid;
                    $m->save();
                }
            }

            r2(getUrl('services/balance'), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('services/balance-add'), 'e', $msg);
        }
        break;
    case 'vpn':
        $ui->assign('_title', Lang::T('VPN Plans'));

        $name = _post('name');
        $name = _req('name');
        $type1 = _req('type1');
        $type2 = _req('type2');
        $type3 = _req('type3');
        $bandwidth = _req('bandwidth');
        $valid = _req('valid');
        $device = _req('device');
        $status = _req('status');
        $router = _req('router');
        $ui->assign('type1', $type1);
        $ui->assign('type2', $type2);
        $ui->assign('type3', $type3);
        $ui->assign('bandwidth', $bandwidth);
        $ui->assign('valid', $valid);
        $ui->assign('device', $device);
        $ui->assign('status', $status);
        $ui->assign('router', $router);

        $append_url = "&type1=" . urlencode($type1)
            . "&type2=" . urlencode($type2)
            . "&type3=" . urlencode($type3)
            . "&bandwidth=" . urlencode($bandwidth)
            . "&valid=" . urlencode($valid)
            . "&device=" . urlencode($device)
            . "&status=" . urlencode($status)
            . "&router=" . urlencode($router);

        $bws = ORM::for_table('tbl_plans')->distinct()->select("id_bw")->where('tbl_plans.type', 'VPN')->findArray();
        $ids = array_column($bws, 'id_bw');
        if (count($ids)) {
            $ui->assign('bws', ORM::for_table('tbl_bandwidth')->select("id")->select('name_bw')->where_id_in($ids)->findArray());
        } else {
            $ui->assign('bws', []);
        }
        $ui->assign('type2s', ORM::for_table('tbl_plans')->getEnum("plan_type"));
        $ui->assign('type3s', ORM::for_table('tbl_plans')->getEnum("typebp"));
        $ui->assign('valids', ORM::for_table('tbl_plans')->getEnum("validity_unit"));
        $ui->assign('routers', array_column(ORM::for_table('tbl_plans')->distinct()->select("routers")->whereNotEqual('routers', '')->findArray(), 'routers'));
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $query = ORM::for_table('tbl_bandwidth')
            ->left_outer_join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))
            ->where('tbl_plans.type', 'VPN');
        if (!empty($type1)) {
            $query->where('tbl_plans.prepaid', $type1);
        }
        if (!empty($type2)) {
            $query->where('tbl_plans.plan_type', $type2);
        }
        if (!empty($type3)) {
            $query->where('tbl_plans.typebp', $type3);
        }
        if (!empty($bandwidth)) {
            $query->where('tbl_plans.id_bw', $bandwidth);
        }
        if (!empty($valid)) {
            $query->where('tbl_plans.validity_unit', $valid);
        }
        if (!empty($router)) {
            if ($router == 'radius') {
                $query->where('tbl_plans.is_radius', '1');
            } else {
                $query->where('tbl_plans.routers', $router);
            }
        }
        if (!empty($device)) {
            $query->where('tbl_plans.device', $device);
        }
        if (in_array($status, ['0', '1'])) {
            $query->where('tbl_plans.enabled', $status);
        }
        if ($name != '') {
            $query->where_like('tbl_plans.name_plan', '%' . $name . '%');
        }
        $d = Paginator::findMany($query, ['name' => $name], 20, $append_url);

        $ui->assign('d', $d);
        // Visibility counts for VPN list
        $visibility_counts = [];
        $ids = [];
        foreach ($d as $row) { $ids[] = $row['id']; }
        if (count($ids)) {
            $rows = ORM::for_table('tbl_plan_customers')
                ->select('plan_id')
                ->select_expr('COUNT(*)', 'cnt')
                ->where_in('plan_id', $ids)
                ->group_by('plan_id')
                ->find_array();
            foreach ($rows as $r) { $visibility_counts[$r['plan_id']] = (int)$r['cnt']; }
        }
        $ui->assign('visibility_counts', $visibility_counts);
        // Map plan id -> visibility for VPN list labels
        $visibility_map = [];
        if (count($ids)) {
            $vrows = ORM::for_table('tbl_plans')->select('id')->select('visibility')->where_in('id', $ids)->find_array();
            foreach ($vrows as $vr) { $visibility_map[$vr['id']] = $vr['visibility']; }
        }
        $ui->assign('visibility_map', $visibility_map);
        run_hook('view_list_vpn'); #HOOK
        $ui->display('admin/vpn/list.tpl');
        break;

    case 'vpn-add':
        $ui->assign('_title', Lang::T('VPN Plans'));
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        $ui->assign('last_visibility', isset($_SESSION['last_visibility']) ? $_SESSION['last_visibility'] : 'all');
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $ui->assign('plan_options', Package::getPlanOptionsList());
        $ui->assign('selected_linked_plans', []);
        run_hook('view_add_vpn'); #HOOK
        $ui->display('admin/vpn/add.tpl');
        break;

    case 'vpn-edit':
        $ui->assign('_title', Lang::T('VPN Plans'));
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            if (empty($d['device'])) {
                if ($d['is_radius']) {
                    $d->device = 'Radius';
                } else {
                    $d->device = 'MikrotikVpn';
                }
                $d->save();
            }
            $ui->assign('d', $d);
            $p = ORM::for_table('tbl_pool')->where('routers', ($d['is_radius']) ? 'radius' : $d['routers'])->find_many();
            $ui->assign('p', $p);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $r = [];
            if ($d['is_radius']) {
                $r = ORM::for_table('tbl_routers')->find_many();
            }
            $ui->assign('r', $r);
            $devices = [];
            $files = scandir($DEVICE_PATH);
            foreach ($files as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext == 'php') {
                    $devices[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
            $ui->assign('devices', $devices);
            $ui->assign('plan_options', Package::getPlanOptionsList($id));
            $ui->assign('selected_linked_plans', Package::getLinkedPlanIds($id));
            //select expired plan
            if ($d['is_radius']) {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'VPN')->where("is_radius", 1)->findArray();
            } else {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'VPN')->where("routers", $d['routers'])->findArray();
            }
            $ui->assign('exps', $exps);
            // Preload selected customers for visibility
            $assigned = ORM::for_table('tbl_plan_customers')->select('customer_id')->where('plan_id', $id)->find_array();
            $selectedIds = array_column($assigned, 'customer_id');
            $selectedCustomers = [];
            if (!empty($selectedIds)) {
                $selectedCustomers = ORM::for_table('tbl_customers')->where_in('id', $selectedIds)->find_array();
            }
            $ui->assign('visible_customer_options', $selectedCustomers);
            run_hook('view_edit_vpn'); #HOOK
            $ui->display('admin/vpn/edit.tpl');
        } else {
            r2(getUrl('services/vpn'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'vpn-delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_vpn'); #HOOK
            Package::removePlanLinks($id);

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->remove_plan($d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            $d->delete();

            r2(getUrl('services/vpn'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'vpn-add-post':
        $name = _post('name_plan');
        $plan_type = _post('plan_type');
        $radius = _post('radius');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $pool = _post('pool_name');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');
        $visibilityInput = _post('visibility', null);
        $visibility = Package::normalizeVisibility($visibilityInput);
        $_SESSION['last_visibility'] = $visibility ?? 'all';
        $reminderEnabled = isset($_POST['reminder_enabled']) ? (int) $_POST['reminder_enabled'] : 0;
        $invoiceNotification = isset($_POST['invoice_notification']) ? (int) $_POST['invoice_notification'] : 0;
        $linkedPlans = $_POST['linked_plans'] ?? null;


        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if (empty($radius)) {
            if ($routers == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }
        run_hook('add_vpn'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];
            $rate = trim($rate . " " . $b['burst']);
            $d = ORM::for_table('tbl_plans')->create();
            $d->type = 'VPN';
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->plan_type = $plan_type;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->pool = $pool;
            if (!empty($radius)) {
                $d->is_radius = 1;
                $d->routers = '';
            } else {
                $d->is_radius = 0;
                $d->routers = $routers;
            }
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 0;
            }
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            $d->reminder_enabled = $reminderEnabled ? 1 : 0;
            $d->invoice_notification = $invoiceNotification ? 1 : 0;
            $d->visibility = $visibility;
            $d->save();

            $linkedPlanIds = Package::normalizeLinkedPlanIds($linkedPlans);
            Package::syncLinkedPlans($d->id(), $linkedPlanIds);

            if (in_array($d['visibility'], ['custom','exclude'])) {
                $selected = isset($_POST['visible_customers']) ? (array)$_POST['visible_customers'] : [];
                $selected = array_filter(array_unique(array_map('intval', $selected)));
                foreach ($selected as $cid) {
                    $m = ORM::for_table('tbl_plan_customers')->create();
                    $m->plan_id = (int)$d->id();
                    $m->customer_id = $cid;
                    $m->save();
                }
            }

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->add_plan($d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            r2(getUrl('services/vpn'), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('services/vpn-add'), 'e', $msg);
        }
        break;

    case 'edit-vpn-post':
        $id = _post('id');
        $plan_type = _post('plan_type');
        $name = _post('name_plan');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $price_old = _post('price_old');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $pool = _post('pool_name');
        $plan_expired = _post('plan_expired');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');
        $on_login = _post('on_login');
        $on_logout = _post('on_logout');
        $visibilityInput = _post('visibility', null);
        $visibility = Package::normalizeVisibility($visibilityInput);
        $reminderEnabled = isset($_POST['reminder_enabled']) ? (int) $_POST['reminder_enabled'] : 0;
        $invoiceNotification = isset($_POST['invoice_notification']) ? (int) $_POST['invoice_notification'] : 0;
        $linkedPlans = $_POST['linked_plans'] ?? null;

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        if($price_old<=$price){
            $price_old = '';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        $old = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        run_hook('edit_vpn'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];
            $rate = trim($rate . " " . $b['burst']);

            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->price_old = $price_old;
            $d->plan_type = $plan_type;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->routers = $routers;
            $d->pool = $pool;
            $d->plan_expired = $plan_expired;
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            $d->on_login = $on_login;
            $d->on_logout = $on_logout;
            $d->reminder_enabled = $reminderEnabled ? 1 : 0;
            $d->invoice_notification = $invoiceNotification ? 1 : 0;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 0;
            }
            $d->visibility = $visibility;
            $d->save();

            $linkedPlanIds = Package::normalizeLinkedPlanIds($linkedPlans);
            Package::syncLinkedPlans($id, $linkedPlanIds);

            // Update visibility mapping
            ORM::for_table('tbl_plan_customers')->where('plan_id', $id)->delete_many();
            if (in_array($d['visibility'], ['custom','exclude'])) {
                $selected = isset($_POST['visible_customers']) ? (array)$_POST['visible_customers'] : [];
                $selected = array_filter(array_unique(array_map('intval', $selected)));
                foreach ($selected as $cid) {
                    $m = ORM::for_table('tbl_plan_customers')->create();
                    $m->plan_id = (int)$id;
                    $m->customer_id = $cid;
                    $m->save();
                }
            }

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->update_plan($old, $d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            r2(getUrl('services/vpn'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('services/vpn-edit/') . $id, 'e', $msg);
        }
        break;
    default:
        $ui->display('admin/404.tpl');
}
