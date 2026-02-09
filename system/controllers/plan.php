<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Recharge Account'));
$ui->assign('_system_menu', 'plan');

$action = $routes['1'];
$ui->assign('_admin', $admin);

$appPath = (string) parse_url(APP_URL, PHP_URL_PATH);
$appPath = rtrim($appPath, '/');
$customerSelect2Url = ($appPath === '' ? '' : $appPath) . '/?_route=autoload/customer_select2';
$customerSelect2UrlJs = json_encode($customerSelect2Url, JSON_UNESCAPED_SLASHES);

$select2_customer = <<<EOT
<script>
document.addEventListener("DOMContentLoaded", function(event) {
    var customerSelect2Url = {$customerSelect2UrlJs};
    $('#personSelect').select2({
        theme: "bootstrap",
        ajax: {
            url: function(params) {
                if(params.term != undefined){
                    return customerSelect2Url + '&s=' + encodeURIComponent(params.term);
                }else{
                    return customerSelect2Url;
                }
            }
        }
    });
});
</script>
EOT;
getUrl('docs');
$accessibleRouterNames = _router_get_accessible_router_names($admin, false);
$applyPlanRouterScope = function ($query, $routerColumn = 'routers', $radiusColumn = 'is_radius') use ($admin, $accessibleRouterNames) {
    if (($admin['user_type'] ?? '') === 'SuperAdmin') {
        return $query;
    }
    if (empty($accessibleRouterNames)) {
        $query->where($radiusColumn, 1);
        return $query;
    }
    $placeholders = implode(',', array_fill(0, count($accessibleRouterNames), '?'));
    $query->where_raw('(' . $radiusColumn . ' = 1 OR ' . $routerColumn . ' IN (' . $placeholders . '))', $accessibleRouterNames);
    return $query;
};
switch ($action) {
    case 'sync':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        set_time_limit(-1);
        $turs = ORM::for_table('tbl_user_recharges')->where('status', 'on')->find_many();
        $log = '';
        $router = '';
        foreach ($turs as $tur) {
            $p = ORM::for_table('tbl_plans')->findOne($tur['plan_id']);
            if ($p) {
                $c = ORM::for_table('tbl_customers')->findOne($tur['customer_id']);
                if ($c) {
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'Demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            if (method_exists($dvc, 'sync_customer')) {
                                (new $p['device'])->sync_customer($c, $p);
                            } else {
                                (new $p['device'])->add_customer($c, $p);
                            }
                        } else {
                            new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                    $log .= "DONE : $tur[username], $ptur[namebp], $tur[type], $tur[routers]<br>";
                } else {
                    $log .= "Customer NOT FOUND : $tur[username], $tur[namebp], $tur[type], $tur[routers]<br>";
                }
            } else {
                $log .= "PLAN NOT FOUND : $tur[username], $tur[namebp], $tur[type], $tur[routers]<br>";
            }
        }
        r2(getUrl('plan/list'), 's', $log);
    case 'recharge':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('xfooter', $select2_customer);
        if (isset($routes['2']) && !empty($routes['2'])) {
            $prefillCustomer = ORM::for_table('tbl_customers')->find_one((int) $routes['2']);
            if (!$prefillCustomer || !_customer_can_access($prefillCustomer, $admin)) {
                r2(getUrl('plan/recharge'), 'e', Lang::T('Customer not found'));
            }
            $ui->assign('cust', $prefillCustomer);
        }
        $usings = explode(',', $config['payment_usings']);
        $usings = array_filter(array_unique($usings));
        if (count($usings) == 0) {
            $usings[] = Lang::T('Cash');
        }
        $ui->assign('usings', $usings);
        run_hook('view_recharge'); #HOOK
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/plan/recharge.tpl');
        break;

    case 'recharge-confirm':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('plan/recharge'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        $id_customer = _post('id_customer');
        $server = _post('server');
        $planId = _post('plan');
        $using = _post('using');
        $note = trim((string) _post('note'));
        if (strlen($note) > 256) {
            $note = substr($note, 0, 256);
        }

        $msg = '';
        if ($id_customer == '' or $server == '' or $planId == '' or $using == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if (!empty($server) && !_router_can_access_router($server, $admin, ['radius'])) {
            $msg .= Lang::T('Selected router is outside your allowed scope') . '<br>';
        }
        $custRow = null;
        if ($id_customer !== '') {
            $custRow = ORM::for_table('tbl_customers')->find_one((int) $id_customer);
            if (!$custRow || !_customer_can_access($custRow, $admin)) {
                $msg .= Lang::T('Customer not found') . '<br>';
            }
        }

        if ($msg == '') {
            $gateway = 'Recharge';
            $channel = $admin['fullname'];
            $cust = $custRow ? $custRow->as_array() : User::_info($id_customer);
            $plan = ORM::for_table('tbl_plans')->find_one($planId);
            if (!$plan) {
                $msg .= Lang::T('Plan not found') . '<br>';
            } elseif (!empty($plan['is_radius'])) {
                if ($server !== 'radius') {
                    $msg .= Lang::T('Invalid plan for selected router') . '<br>';
                }
            } else {
                if ($server !== (string) ($plan['routers'] ?? '')) {
                    $msg .= Lang::T('Invalid plan for selected router') . '<br>';
                }
                if (!_router_can_access_router((string) ($plan['routers'] ?? ''), $admin, ['radius'])) {
                    $msg .= Lang::T('Selected router is outside your allowed scope') . '<br>';
                }
            }
        }

        if ($msg == '') {
            list($bills, $add_cost) = User::getBills($id_customer);
            $add_inv = User::getAttribute("Invoice", $id_customer);
            if (!empty($add_inv)) {
                $plan['price'] = $add_inv;
            }

            // Tax calculation start
            $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : null;
            $custom_tax_rate = isset($config['custom_tax_rate']) ? (float) $config['custom_tax_rate'] : null;

            if ($tax_rate_setting === 'custom') {
                $tax_rate = $custom_tax_rate;
            } else {
                $tax_rate = $tax_rate_setting;
            }

            if ($tax_enable === 'yes') {
                $tax = Package::tax($plan['price'], $tax_rate);
            } else {
                $tax = 0;
            }
            // Tax calculation stop
            $total_cost = $plan['price'] + $add_cost + $tax;

            if ($using == 'balance' && $config['enable_balance'] == 'yes') {
                if (!$cust) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Customer not found'));
                }
                if (!$plan) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Plan not found'));
                }
                if ($cust['balance'] < $total_cost) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('insufficient balance'));
                }
                $gateway = 'Recharge Balance';
            }
            if ($using == 'zero') {
                $zero = 1;
                $gateway = 'Recharge Zero';
            }
            $usings = explode(',', $config['payment_usings']);
            $usings = array_filter(array_unique($usings));
            if (count($usings) == 0) {
                $usings[] = Lang::T('Cash');
            }
            if ($tax_enable === 'yes') {
                $ui->assign('tax', $tax);
            }
            $ui->assign('usings', $usings);
            $ui->assign('bills', $bills);
            $ui->assign('add_cost', $add_cost);
            $ui->assign('cust', $cust);
            $ui->assign('gateway', $gateway);
            $ui->assign('channel', $channel);
            $ui->assign('server', $server);
            $ui->assign('using', $using);
            $ui->assign('plan', $plan);
            $ui->assign('add_inv', $add_inv);
            $ui->assign('note', $note);
            $ui->assign('csrf_token', Csrf::generateAndStoreToken());
            $ui->display('admin/plan/recharge-confirm.tpl');
        } else {
            r2(getUrl('plan/recharge'), 'e', $msg);
        }
        break;

    case 'recharge-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('plan/recharge'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        $id_customer = _post('id_customer');
        $server = _post('server');
        $planId = _post('plan');
        $using = _post('using');
        $svoucher = _post('svoucher');
        $note = trim((string) _post('note'));
        if (strlen($note) > 256) {
            $note = substr($note, 0, 256);
        }

        $plan = ORM::for_table('tbl_plans')->find_one($planId);

        if (!empty(App::getVoucherValue($svoucher))) {
            $username = App::getVoucherValue($svoucher);
            $in = ORM::for_table('tbl_transactions')->where('username', $username)->order_by_desc('id')->find_one();
            Package::createInvoice($in);
            $ui->assign('csrf_token', Csrf::generateAndStoreToken());
            $ui->display('admin/plan/invoice.tpl');
            die();
        }

        $msg = '';
        if ($id_customer == '' or $server == '' or $planId == '' or $using == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if (!empty($server) && !_router_can_access_router($server, $admin, ['radius'])) {
            $msg .= Lang::T('Selected router is outside your allowed scope') . '<br>';
        }
        $custRow = null;
        if ($id_customer !== '') {
            $custRow = ORM::for_table('tbl_customers')->find_one((int) $id_customer);
            if (!$custRow || !_customer_can_access($custRow, $admin)) {
                $msg .= Lang::T('Customer not found') . '<br>';
            }
        }
        if (!$plan) {
            $msg .= Lang::T('Plan not found') . '<br>';
        } elseif (!empty($plan['is_radius'])) {
            if ($server !== 'radius') {
                $msg .= Lang::T('Invalid plan for selected router') . '<br>';
            }
        } else {
            if ($server !== (string) ($plan['routers'] ?? '')) {
                $msg .= Lang::T('Invalid plan for selected router') . '<br>';
            }
            if (!_router_can_access_router((string) ($plan['routers'] ?? ''), $admin, ['radius'])) {
                $msg .= Lang::T('Selected router is outside your allowed scope') . '<br>';
            }
        }

        if ($msg == '') {
            $gateway = ucwords($using);
            $channel = $admin['fullname'];
            $cust = $custRow ? $custRow->as_array() : User::_info($id_customer);
            list($bills, $add_cost) = User::getBills($id_customer);

            // Tax calculation start
            $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : null;
            $custom_tax_rate = isset($config['custom_tax_rate']) ? (float) $config['custom_tax_rate'] : null;

            if ($tax_rate_setting === 'custom') {
                $tax_rate = $custom_tax_rate;
            } else {
                $tax_rate = $tax_rate_setting;
            }

            if ($tax_enable === 'yes') {
                $tax = Package::tax($plan['price'], $tax_rate);
            } else {
                $tax = 0;
            }
            // Tax calculation stop
            $total_cost = $plan['price'] + $add_cost + $tax;

            if ($using == 'balance' && $config['enable_balance'] == 'yes') {
                //$plan = ORM::for_table('tbl_plans')->find_one($planId);
                if (!$cust) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Customer not found'));
                }
                if (!$plan) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Plan not found'));
                }
                if ($cust['balance'] < $total_cost) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('insufficient balance'));
                }
                $gateway = 'Recharge Balance';
            }
            if ($using == 'zero') {
                $add_cost = 0;
                $zero = 1;
                $gateway = 'Recharge Zero';
            }
            if (Package::rechargeUser($id_customer, $server, $planId, $gateway, $channel, $note)) {
                if ($using == 'balance') {
                    Balance::min($cust['id'], $total_cost);
                }
                $in = ORM::for_table('tbl_transactions')->where('username', $cust['username'])->order_by_desc('id')->find_one();
                Package::createInvoice($in);
                App::setVoucher($svoucher, $cust['username']);
                $ui->assign('csrf_token', Csrf::generateAndStoreToken());
                $ui->display('admin/plan/invoice.tpl');
                _log('[' . $admin['username'] . ']: ' . 'Recharge ' . $cust['username'] . ' [' . $in['plan_name'] . '][' . Lang::moneyFormat($in['price']) . ']', $admin['user_type'], $admin['id']);
            } else {
                r2(getUrl('plan/recharge'), 'e', "Failed to recharge account");
            }
        } else {
            r2(getUrl('plan/recharge'), 'e', $msg);
        }
        break;

    case 'view':
        $id = $routes['2'];
        $in = ORM::for_table('tbl_transactions')->where('id', $id)->find_one();
        $ui->assign('in', $in);
        if (!empty($routes['3']) && $routes['3'] == 'send') {
            $c = ORM::for_table('tbl_customers')->where('username', $in['username'])->find_one();
            if ($c) {
                Message::sendInvoice($c, $in);
                r2(getUrl('plan/view/') . $id, 's', "Success send to customer");
            }
            r2(getUrl('plan/view/') . $id, 'd', "Customer not found");
        }
        Package::createInvoice($in);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $UPLOAD_URL_PATH = str_replace($root_path, '', $UPLOAD_PATH);
        $logo = '';
        if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png')) {
            $logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.png';
            $imgsize = getimagesize($logo);
            $width = $imgsize[0];
            $height = $imgsize[1];
            $ui->assign('wlogo', $width);
            $ui->assign('hlogo', $height);
        }

        $ui->assign('public_url', getUrl("voucher/invoice/$id/" . md5($id . $db_pass)));
        $ui->assign('logo', $logo);
        $ui->assign('_title', 'View Invoice');
        $ui->display('admin/plan/invoice.tpl');
        break;


    case 'print':
        $csrf_token = _post('csrf_token');
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::check($csrf_token)) {
            _alert(Lang::T('Invalid CSRF token'), 'danger', 'dashboard');
        }
        $content = $_POST['content'];
        if (!empty($content)) {
            if ($_POST['nux'] == 'print') {
                //header("Location: nux://print?text=".urlencode($content));
                $ui->assign('nuxprint', "nux://print?text=" . urlencode($content));
            }
            $ui->assign('content', $content);
        } else {
            $id = _post('id');
            if (empty($id)) {
                $id = $routes['2'];
            }
            $d = ORM::for_table('tbl_transactions')->where('id', $id)->find_one();
            $ui->assign('in', $d);
            $ui->assign('date', Lang::dateAndTimeFormat($d['recharged_on'], $d['recharged_time']));
        }
        run_hook('print_invoice'); #HOOK
        $ui->display('admin/plan/invoice-print.tpl');
        break;

    case 'edit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = $routes['2'];
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if ($d) {
            $ui->assign('d', $d);
            $p = ORM::for_table('tbl_plans')->find_one($d['plan_id']);
            if (in_array($admin['user_type'], array('SuperAdmin', 'Admin'))) {
                $ps = ORM::for_table('tbl_plans')
                    ->where('type', $p['type'])
                    ->where('is_radius', $p['is_radius'])
                    ->find_many();
            } else {
                $ps = ORM::for_table('tbl_plans')
                    ->where("enabled", 1)
                    ->where('is_radius', $p['is_radius'])
                    ->where('type', $p['type'])
                    ->find_many();
            }
            $ui->assign('p', $ps);
            run_hook('view_edit_customer_plan'); #HOOK
            $ui->assign('_title', 'Edit Plan');
            $ui->assign('csrf_token', Csrf::generateAndStoreToken());
            $ui->display('admin/plan/edit.tpl');
        } else {
            r2(getUrl('plan/list'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = $routes['2'];
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if ($d) {
            run_hook('delete_customer_active_plan'); #HOOK
            $p = ORM::for_table('tbl_plans')->find_one($d['plan_id']);
            $c = User::_info($d['customer_id']);
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'Demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $p['device'])->remove_customer($c, $p);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            $d->delete();
            _log('[' . $admin['username'] . ']: ' . 'Delete Plan for Customer ' . $c['username'] . '  [' . $in['plan_name'] . '][' . Lang::moneyFormat($in['price']) . ']', $admin['user_type'], $admin['id']);
            r2(getUrl('plan/list'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'edit-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $msg = '';
        $id_plan = _post('id_plan');
        $recharged_on = _post('recharged_on');
        $expiration = _post('expiration');
        $time = _post('time');
        $id = _post('id');
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('plan/edit/') . $id, 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if (!$d) {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        $customer = User::_info($d['customer_id']);
        $oldPlanID = $d['plan_id'];
        $newPlan = ORM::for_table('tbl_plans')->where('id', $id_plan)->find_one();

        if (!$newPlan) {
            $msg .= ' Plan Not Found<br>';
        }

        if ($msg == '') {
            run_hook('edit_customer_plan'); // Hook 

            $d->expiration = $expiration;
            $d->time = $time;

            if ($d['status'] == 'off' && strtotime($expiration . ' ' . $time) > time()) {
                $d->status = 'on';
            }

            $p = null;
            if ($oldPlanID != $id_plan) {
                $d->plan_id = $newPlan['id'];
                $d->namebp = $newPlan['name_plan'];

                if ($d['status'] == 'on') {
                    $p = ORM::for_table('tbl_plans')->find_one($oldPlanID);
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'Demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            $p['plan_expired'] = 0;
                            (new $p['device'])->remove_customer($customer, $p);
                        } else {
                            throw new Exception(Lang::T("Devices Not Found"));
                        }
                    }

                    $dvc = Package::getDevice($newPlan);
                    if ($_app_stage != 'Demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            (new $newPlan['device'])->add_customer($customer, $newPlan);
                        } else {
                            throw new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                }
            }

            $planName = $d['namebp'];
            $d->save();

            // Send  Notifications
            if (isset($_POST['notify']) && $_POST['notify'] == true) {
                $templateKey = 'plan_change_message';
                if ($oldPlanID != $id_plan) {
                    $oldPlan = ORM::for_table('tbl_plans')->find_one($oldPlanID);
                    $oldPlanName = $oldPlan ? $oldPlan['name_plan'] : 'Old Plan';
                    $notifyMessage = Lang::getNotifText('plan_change_message');
                    if (empty($notifyMessage)) {
                        $notifyMessage = Lang::T('Great news') . ', [[name]]! ' .
                            Lang::T('Your plan has been successfully upgraded from ') . ' [[old_plan]] ' .
                            Lang::T('to') . ' [[new_plan]]. ' .
                            Lang::T('You can now enjoy seamless internet access until') . ' [[expiry]]. ' .
                            Lang::T('Thank you for choosing') . ' [[company]] ' .
                            Lang::T('for your internet needs') . ', ' .
                            Lang::T('Enjoy enhanced features and benefits starting today') . '!';
                    } else {
                        $notifyMessage = Lang::getNotifText('plan_change_message');
                    }
                    $notifyMessage = str_replace('[[old_plan]]', $oldPlanName, $notifyMessage);
                    $notifyMessage = str_replace('[[new_plan]]', $planName, $notifyMessage);
                } else {
                    $templateKey = 'edit_expiry_message';
                    $notifyMessage = Lang::getNotifText('edit_expiry_message');
                    if (empty($notifyMessage)) {
                        $notifyMessage = Lang::T('Dear') . ' [[name]], ' .
                            Lang::T('your') . ' [[plan]] ' .
                            Lang::T('has been extended! You can now enjoy seamless internet access until') . ' [[expiry]]. ' .
                            Lang::T('Thank you for choosing') . ' [[company]] ' .
                            Lang::T('for your internet needs') . '!';
                    } else {
                        $notifyMessage = Lang::getNotifText('edit_expiry_message');
                    }
                    $notifyMessage = str_replace('[[plan]]', $planName, $notifyMessage);
                }

                $notifyMessage = str_replace('[[company]]', $config['CompanyName'], $notifyMessage);
                $notifyMessage = str_replace('[[name]]', $customer['fullname'], $notifyMessage);
                $notifyMessage = str_replace('[[username]]', $customer['username'], $notifyMessage);
                $notifyMessage = str_replace('[[expiry]]', date('M d, Y h:i:s', strtotime($expiration . ' ' . $time)), $notifyMessage);

                $subject = $planName . ' ' . Lang::T('Expiry Extension Notification');

                $waOptions = Message::isWhatsappQueueEnabledForNotificationTemplate($templateKey)
                    ? ['queue' => true, 'queue_context' => 'notification']
                    : [];

                $channels = [
                    'sms' => [
                        'enabled' => isset($_POST['sms']),
                        'method' => 'sendSMS',
                        'args' => [$customer['phonenumber'], $notifyMessage]
                    ],
                    'whatsapp' => [
                        'enabled' => isset($_POST['wa']),
                        'method' => 'sendWhatsapp',
                        'args' => [$customer['phonenumber'], $notifyMessage, $waOptions]
                    ],
                    'email' => [
                        'enabled' => isset($_POST['mail']),
                        'method' => 'Message::sendEmail',
                        'args' => [$customer['email'], $subject, $notifyMessage, $d['email']]
                    ],
                    'inbox' => [
                        'enabled' => isset($_POST['inbox']),
                        'method' => 'Message::addToInbox',
                        'args' => [$customer['id'], $subject, $notifyMessage, 'Admin']
                    ],
                ];

                foreach ($channels as $channel => $message) {
                    if ($message['enabled']) {
                        try {
                            call_user_func_array($message['method'], $message['args']);
                            _log("Notification sent to {$customer['username']} via: " . implode(', ', array_keys(array_filter($channels, fn($c) => $c['enabled']))));
                        } catch (Exception $e) {
                            _log("Failed to send notify message via $channel: " . $e->getMessage());
                        }
                    }
                }
            }

            $price = isset($p['price']) ? Lang::moneyFormat($p['price']) : '';
            _log('[' . $admin['username'] . ']: Edit Plan for Customer ' . $d['username'] . ' to [' . $d['namebp'] . "][$price]", $admin['user_type'], $admin['id']);
            r2(getUrl('plan/list'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('plan/edit/') . $id, 'e', $msg);
        }
        break;

    case 'voucher':
        $ui->assign('_title', Lang::T('Voucher Cards'));
        $search = _req('search');
        $router = _req('router');
        if (!empty($router) && !_router_can_access_router($router, $admin, ['radius'])) {
            $router = '';
        }
        $customer = _req('customer');
        $plan = _req('plan');
        $status = _req('status');
        $batch_name = _req('batch_name');

        $ui->assign('batch_name', $batch_name);
        $ui->assign('router', $router);
        $ui->assign('customer', $customer);
        $ui->assign('status', $status);
        $ui->assign('plan', $plan);
        $ui->assign('_system_menu', 'cards');

        $query = ORM::for_table('tbl_plans')
            ->inner_join('tbl_voucher', ['tbl_plans.id', '=', 'tbl_voucher.id_plan']);

        if (($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $allowedVoucherRouters = $accessibleRouterNames;
            if (!empty($config['radius_enable'])) {
                $allowedVoucherRouters[] = 'radius';
            }
            $allowedVoucherRouters = array_values(array_unique(array_filter($allowedVoucherRouters)));
            if (empty($allowedVoucherRouters)) {
                $query->where('tbl_voucher.id', -1);
            } else {
                $query->where_in('tbl_voucher.routers', $allowedVoucherRouters);
            }
        }

        if (!empty($router)) {
            $query->where('tbl_voucher.routers', $router);
        }

        if ($status == '1' || $status == '0') {
            $query->where('tbl_voucher.status', $status);
        }

        if (!empty($plan)) {
            $query->where('tbl_voucher.id_plan', $plan);
        }

        if (!empty($customer)) {
            $query->where('tbl_voucher.user', $customer);
        }

        if (!empty($batch_name)) {
            $query->where('tbl_voucher.batch_name', $batch_name);
        }

        if (!empty($_COOKIE['voucher_per_page']) && $_COOKIE['voucher_per_page'] != $config['voucher_per_page']) {
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'voucher_per_page')->find_one();
            if ($d) {
                $d->value = $_COOKIE['voucher_per_page'];
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'voucher_per_page';
                $d->value = $_COOKIE['voucher_per_page'];
                $d->save();
            }
        }
        if (!empty($config['voucher_per_page']) && empty($_COOKIE['voucher_per_page'])) {
            $_COOKIE['voucher_per_page'] = $config['voucher_per_page'];
            setcookie('voucher_per_page', $config['voucher_per_page'], time() + (86400 * 30), "/");
        }

        $ui->assign('cookie', $_COOKIE['voucher_per_page']);

        $per_page = !empty($_COOKIE['voucher_per_page']) ? $_COOKIE['voucher_per_page'] : (!empty($config['voucher_per_page']) ? $config['voucher_per_page'] : '10');

        $append_url = "&search=" . urlencode($search) . "&router=" . urlencode($router) . "&customer=" . urlencode($customer) . "&plan=" . urlencode($plan) . "&status=" . urlencode($status);

        // option customers
        $ui->assign('customers', ORM::for_table('tbl_voucher')->distinct()->select("user")->whereNotEqual("user", '0')->findArray());
        // option plans
        $plns = ORM::for_table('tbl_voucher')->distinct()->select("id_plan")->findArray();
        if (count($plns) > 0) {
            $ui->assign('plans', ORM::for_table('tbl_plans')->selects(["id", 'name_plan'])->where_in('id', array_column($plns, 'id_plan'))->findArray());
        } else {
            $ui->assign('plans', []);
        }
        $ui->assign('routers', _router_filter_allowed_names(
            array_column(ORM::for_table('tbl_voucher')->distinct()->select("routers")->findArray(), 'routers'),
            $admin,
            ['radius']
        ));

        if ($search != '') {
            if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                $query->where_like('tbl_voucher.code', "%$search%");
            } else if ($admin['user_type'] == 'Agent') {
                $sales = [];
                $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
                foreach ($sls as $s) {
                    $sales[] = $s['id'];
                }
                $sales[] = $admin['id'];
                $query->where_in('generated_by', $sales)
                    ->where_like('tbl_voucher.code', "%$search%");
            }
        } else {
            if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            } else if ($admin['user_type'] == 'Agent') {
                $sales = [];
                $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
                foreach ($sls as $s) {
                    $sales[] = $s['id'];
                }
                $sales[] = $admin['id'];
                $query->where_in('generated_by', $sales);
            }
        }
        $d = Paginator::findMany($query, ["search" => $search], $per_page, $append_url);
        // extract admin
        $admins = [];
        foreach ($d as $k) {
            if (!empty($k['generated_by'])) {
                $admins[] = $k['generated_by'];
            }
        }
        if (count($admins) > 0) {
            $adms = ORM::for_table('tbl_users')->where_in('id', $admins)->find_many();
            unset($admins);
            foreach ($adms as $adm) {
                $tipe = $adm['user_type'];
                if ($tipe == 'Sales') {
                    $tipe = ' [S]';
                } else if ($tipe == 'Agent') {
                    $tipe = ' [A]';
                } else {
                    $tipe == '';
                }
                $admins[$adm['id']] = $adm['fullname'] . $tipe;
            }
        }

        $batches = ORM::for_table('tbl_voucher')
            ->select('batch_name')
            ->select('created_at')
            ->distinct()
            ->where_not_equal('batch_name', '')
            ->order_by_desc('created_at')
            ->find_many();
        $ui->assign('batches', $batches);
        $ui->assign('admins', $admins);
        $ui->assign('d', $d);
        $ui->assign('search', $search);
        $ui->assign('page', $page);
        run_hook('view_list_voucher'); #HOOK
        $ui->display('admin/voucher/list.tpl');
        break;

    case 'add-voucher':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('_title', Lang::T('Add Vouchers'));
        $c = ORM::for_table('tbl_customers')->find_many();
        $ui->assign('c', $c);
        $p = ORM::for_table('tbl_plans')->where('enabled', '1');
        $applyPlanRouterScope($p, 'routers', 'is_radius');
        $p = $p->find_many();
        $ui->assign('p', $p);
        $r = _router_get_accessible_routers($admin, true);
        $ui->assign('r', $r);
        run_hook('view_add_voucher'); #HOOK
        $ui->display('admin/voucher/add.tpl');
        break;

    case 'remove-voucher':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $time3months = strtotime('-3 months');
        $d = ORM::for_table('tbl_voucher')->where_equal('status', '1')
            ->where_raw("UNIX_TIMESTAMP(used_date) < $time3months")
            ->findMany();
        if ($d) {
            $jml = 0;
            foreach ($d as $v) {
                if (!ORM::for_table('tbl_user_recharges')->where_equal("method", 'Voucher - ' . $v['code'])->findOne()) {
                    $v->delete();
                    $jml++;
                }
            }
            r2(getUrl('plan/voucher'), 's', "$jml " . Lang::T('Data Deleted Successfully'));
        }
    case 'print-voucher':
        $from_id = _post('from_id');
        $planid = _post('planid');
        $pagebreak = _post('pagebreak');
        $limit = _post('limit');
        $vpl = _post('vpl');
        $batch = _post('batch');
        $group = _post('group');
        $selected_datetime = _post('selected_datetime');

        if (empty($vpl))
            $vpl = 3;
        if ($pagebreak < 1)
            $pagebreak = 12;
        if ($limit < 1)
            $limit = $pagebreak * 2;
        if (empty($from_id))
            $from_id = 0;

        $v = ORM::for_table('tbl_plans')
            ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
            ->where('tbl_voucher.status', '0');

        $vc = ORM::for_table('tbl_plans')
            ->left_outer_join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
            ->where('tbl_voucher.status', '0');

        if ($planid > 0) {
            $v = $v->where('tbl_plans.id', $planid);
            $vc = $vc->where('tbl_plans.id', $planid);
        }

        if ($from_id > 0) {
            $v = $v->where_gt('tbl_voucher.id', $from_id);
            $vc = $vc->where_gt('tbl_voucher.id', $from_id);
        }

        if (!empty($group)) {
            if ($group == 'datetime' && !empty($selected_datetime)) {
                $timestamp = strtotime($selected_datetime);
                if ($timestamp === false) {
                    throw new Exception("Invalid date format provided.");
                }
                $dateFilter = date('Y-m-d', $timestamp);
                $v = $v->where_raw("DATE(tbl_voucher.created_at) = ?", [$dateFilter]);
                $vc = $vc->where_raw("DATE(tbl_voucher.created_at) = ?", [$dateFilter]);
            } elseif ($group == 'batch' && !empty($batch)) {
                switch ($batch) {
                    case 'all':
                        $v = $v->where_not_equal('tbl_voucher.batch_name', '');
                        $vc = $vc->where_not_equal('tbl_voucher.batch_name', '');
                        break;
                    default:
                        $v = $v->where('tbl_voucher.batch_name', $batch);
                        $vc = $vc->where('tbl_voucher.batch_name', $batch);
                        break;
                }
            }
        }

        // Limit only to main query
        $v = $v->limit($limit);

        // Admin vs non-admin check
        if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            $v = $v->find_many();
            $vc = $vc->count();
        } else {
            $sales = [$admin['id']];
            $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
            foreach ($sls as $s) {
                $sales[] = $s['id'];
            }
            $v = $v->where_in('generated_by', $sales)->find_many();
            $vc = $vc->where_in('generated_by', $sales)->count();
        }

        // Voucher template and data
        $template = file_get_contents("pages/Voucher.html");
        $template = str_replace('[[company_name]]', $config['CompanyName'], $template);

        $voucher = [];
        $n = 1;
        foreach ($v as $vs) {
            $temp = $template;
            $temp = str_replace('[[qrcode]]', '<img src="qrcode/?data=' . $vs['code'] . '">', $temp);
            $temp = str_replace('[[price]]', Lang::moneyFormat($vs['price']), $temp);
            $temp = str_replace('[[voucher_code]]', $vs['code'], $temp);
            $temp = str_replace('[[plan]]', $vs['name_plan'], $temp);
            $temp = str_replace('[[counter]]', $n, $temp);
            $voucher[] = $temp;
            $n++;
        }

        // Additional data for the view
        $plans = ORM::for_table('tbl_plans')->find_many();

        $createdate = ORM::for_table('tbl_voucher')
            ->select_expr(
                "CASE WHEN DATE(created_at) = CURDATE() THEN 'Today' ELSE DATE(created_at) END",
                'created_datetime'
            )
            ->select_expr('COUNT(*)', 'voucher_count')
            ->group_by('created_datetime')
            ->order_by_desc('created_datetime')
            ->find_array();

        $batches = ORM::for_table('tbl_voucher')
            ->select('batch_name')
            ->select('created_at')
            ->distinct()
            ->where_not_equal('batch_name', '')
            ->order_by_desc('created_at')
            ->find_many();

        $ui->assign('_title', Lang::T('Hotspot Voucher'));
        $ui->assign('from_id', $from_id);
        $ui->assign('vpl', $vpl);
        $ui->assign('pagebreak', $pagebreak);
        $ui->assign('plans', $plans);
        $ui->assign('limit', $limit);
        $ui->assign('planid', $planid);
        $ui->assign('createdate', $createdate);
        $ui->assign('voucher', $voucher);
        $ui->assign('vc', $vc);
        $ui->assign('selected_datetime', $selected_datetime);
        $ui->assign('batches', $batches);
        $ui->assign('selected_batch', $batch);
        $ui->assign('group', $group);
        $ui->assign('jml', 0);

        run_hook('view_print_voucher'); #HOOK
        $ui->display('admin/print/voucher.tpl');
        break;
    case 'voucher-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('plan/add-voucher/'), 'e', 'You cannot perform this action in demo mode');
        }

        $type = _post('type');
        $plan = _post('plan');
        $voucher_format = _post('voucher_format');
        $prefix = _post('prefix');
        $server = _post('server');
        $numbervoucher = _post('numbervoucher');
        $lengthcode = _post('lengthcode');
        $printNow = _post('print_now', 'no');
        $voucherPerPage = _post('voucher_per_page', '36');
        $batch_name = _post('batch_name', '');

        $msg = '';
        if (empty($type) || empty($plan) || empty($server) || empty($numbervoucher) || empty($lengthcode)) {
            $msg .= Lang::T('All fields are required') . '<br>';
        }
        if (!Validator::UnsignedNumber($numbervoucher)) {
            $msg .= 'The Number of Vouchers must be a number' . '<br>';
        }
        if (!Validator::UnsignedNumber($lengthcode)) {
            $msg .= "The Length Code must be a number<br>";
        }
        if (!empty($server) && !_router_can_access_router($server, $admin, ['radius'])) {
            $msg .= Lang::T('Selected router is outside your allowed scope') . '<br>';
        }

        $planRow = ORM::for_table('tbl_plans')->find_one((int) $plan);
        if (!$planRow) {
            $msg .= Lang::T('Plan not found') . '<br>';
        } else {
            if (!empty($planRow['is_radius'])) {
                if ($server !== 'radius') {
                    $msg .= Lang::T('Invalid plan for selected router') . '<br>';
                }
            } else {
                if ($server !== (string) ($planRow['routers'] ?? '')) {
                    $msg .= Lang::T('Invalid plan for selected router') . '<br>';
                }
                if (!_router_can_access_router((string) ($planRow['routers'] ?? ''), $admin, ['radius'])) {
                    $msg .= Lang::T('Selected router is outside your allowed scope') . '<br>';
                }
            }
            if (!empty($type) && strcasecmp((string) $planRow['type'], (string) $type) !== 0) {
                $msg .= Lang::T('Invalid plan type') . '<br>';
            }
        }

        if ($msg == '') {
            // Update or create voucher prefix
            if (!empty($prefix)) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', 'voucher_prefix')->find_one();
                if ($d) {
                    $d->value = $prefix;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = 'voucher_prefix';
                    $d->value = $prefix;
                    $d->save();
                }
            }

            run_hook('create_voucher'); // HOOK
            $vouchers = [];
            $newVoucherIds = [];

            if ($voucher_format == 'numbers') {
                if ($lengthcode < 6) {
                    $msg .= "The Length Code must be more than 6 for numbers<br>";
                }
                $vouchers = generateUniqueNumericVouchers($numbervoucher, $lengthcode);
            } else {
                for ($i = 0; $i < $numbervoucher; $i++) {
                    $code = strtoupper(substr(md5(time() . rand(10000, 99999)), 0, $lengthcode));
                    if ($voucher_format == 'low') {
                        $code = strtolower($code);
                    } else if ($voucher_format == 'rand') {
                        $code = Lang::randomUpLowCase($code);
                    }
                    $vouchers[] = $code;
                }
            }

            foreach ($vouchers as $code) {
                $d = ORM::for_table('tbl_voucher')->create();
                $d->type = $type;
                $d->routers = $server;
                $d->id_plan = $plan;
                $d->code = "$prefix$code";
                $d->user = '0';
                $d->status = '0';
                $d->generated_by = $admin['id'];
                $d->batch_name = $batch_name;
                $d->save();
                $newVoucherIds[] = $d->id();
            }

            if ($printNow == 'yes' && count($newVoucherIds) > 0) {
                $template = file_get_contents("pages/Voucher.html");
                $template = str_replace('[[company_name]]', $config['CompanyName'], $template);

                $vouchersToPrint = ORM::for_table('tbl_voucher')
                    ->left_outer_join('tbl_plans', ['tbl_plans.id', '=', 'tbl_voucher.id_plan'])
                    ->where_in('tbl_voucher.id', $newVoucherIds)
                    ->find_many();

                $voucherHtmls = [];
                $n = 1;

                foreach ($vouchersToPrint as $vs) {
                    $temp = $template;
                    $temp = str_replace('[[qrcode]]', '<img src="qrcode/?data=' . $vs['code'] . '">', $temp);
                    $temp = str_replace('[[price]]', Lang::moneyFormat($vs['price']), $temp);
                    $temp = str_replace('[[voucher_code]]', $vs['code'], $temp);
                    $temp = str_replace('[[plan]]', $vs['name_plan'], $temp);
                    $temp = str_replace('[[counter]]', $n, $temp);
                    $voucherHtmls[] = $temp;
                    $n++;
                }

                $vc = count($voucherHtmls);
                $ui->assign('voucher', $voucherHtmls);
                $ui->assign('vc', $vc);
                $ui->assign('jml', 0);
                $ui->assign('from_id', 0);
                $ui->assign('vpl', '3');
                $ui->assign('pagebreak', $voucherPerPage);
                $ui->display('admin/print/voucher.tpl');
            }

            if ($numbervoucher == 1) {
                r2(getUrl('plan/voucher-view/') . $d->id(), 's', Lang::T('Create Vouchers Successfully'));
            }

            r2(getUrl('plan/voucher'), 's', Lang::T('Create Vouchers Successfully'));
        } else {
            r2(getUrl('plan/add-voucher/') . $id, 'e', $msg);
        }
        break;

    case 'voucher-delete-many':
        header('Content-Type: application/json');

        $admin = Admin::_info();

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            echo json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $voucherIds = json_decode($_POST['voucherIds'], true);

            if (is_array($voucherIds) && !empty($voucherIds)) {
                $voucherIds = array_map('intval', $voucherIds);

                try {
                    ORM::for_table('tbl_voucher')
                        ->where_in('id', $voucherIds)
                        ->delete_many();
                } catch (Exception $e) {
                    echo json_encode(['status' => 'error', 'message' => Lang::T('Failed to delete vouchers.')]);
                    exit;
                }

                // Return success response
                echo json_encode(['status' => 'success', 'message' => Lang::T("Vouchers Deleted Successfully.")]);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'message' => Lang::T("Invalid or missing voucher IDs.")]);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => Lang::T("Invalid request method.")]);
        }
        break;

    case 'voucher-view':
        $id = $routes[2];
        if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            $voucher = ORM::for_table('tbl_voucher')->find_one($id);
        } else {
            $sales = [];
            $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
            foreach ($sls as $s) {
                $sales[] = $s['id'];
            }
            $sales[] = $admin['id'];
            $voucher = ORM::for_table('tbl_voucher')
                ->find_one($id);
            if (!in_array($voucher['generated_by'], $sales)) {
                r2(getUrl('plan/voucher/'), 'e', Lang::T('Voucher Not Found'));
            }
        }
        if (!$voucher) {
            r2(getUrl('plan/voucher/'), 'e', Lang::T('Voucher Not Found'));
        }
        $plan = ORM::for_table('tbl_plans')->find_one($voucher['id_plan']);
        if ($voucher && $plan) {
            $content = Lang::pad($config['CompanyName'], ' ', 2) . "\n";
            $content .= Lang::pad($config['address'], ' ', 2) . "\n";
            $content .= Lang::pad($config['phone'], ' ', 2) . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pads('ID', $voucher['id'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Code'), $voucher['code'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Name'), $plan['name_plan'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Type'), $voucher['type'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Price'), Lang::moneyFormat($plan['price']), ' ') . "\n";
            $content .= Lang::pads(Lang::T('Sales'), $admin['fullname'] . ' #' . $admin['id'], ' ') . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pad($config['note'], ' ', 2) . "\n";
            $ui->assign('print', $content);
            $config['printer_cols'] = 30;
            $content = Lang::pad($config['CompanyName'], ' ', 2) . "\n";
            $content .= Lang::pad($config['address'], ' ', 2) . "\n";
            $content .= Lang::pad($config['phone'], ' ', 2) . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pads('ID', $voucher['id'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Code'), $voucher['code'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Name'), $plan['name_plan'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Type'), $voucher['type'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Price'), Lang::moneyFormat($plan['price']), ' ') . "\n";
            $content .= Lang::pads(Lang::T('Sales'), $admin['fullname'] . ' #' . $admin['id'], ' ') . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pad($config['note'], ' ', 2) . "\n";
            $ui->assign('_title', Lang::T('View'));
            $ui->assign('whatsapp', urlencode("```$content```"));
            $ui->display('admin/voucher/view.tpl');
        } else {
            r2(getUrl('plan/voucher/'), 'e', Lang::T('Voucher Not Found'));
        }
        break;
    case 'voucher-delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = $routes['2'];
        run_hook('delete_voucher'); #HOOK
        $d = ORM::for_table('tbl_voucher')->find_one($id);
        if ($d) {
            $d->delete();
            r2(getUrl('plan/voucher'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'refill':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('xfooter', $select2_customer);
        $ui->assign('_title', Lang::T('Refill Account'));
        run_hook('view_refill'); #HOOK
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/plan/refill.tpl');

        break;

    case 'refill-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('plan/refill'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        $code = Text::alphanumeric(_post('code'), "-_.,");
        $user = ORM::for_table('tbl_customers')->where('id', _post('id_customer'))->find_one();
        $v1 = ORM::for_table('tbl_voucher')->whereRaw("BINARY code = '$code'")->where('status', 0)->find_one();

        run_hook('refill_customer'); #HOOK
        if ($v1) {
            if (Package::rechargeUser($user['id'], $v1['routers'], $v1['id_plan'], "Voucher", $code)) {
                $v1->status = "1";
                $v1->user = $user['username'];
                $v1->save();
                $in = ORM::for_table('tbl_transactions')->where('username', $user['username'])->order_by_desc('id')->find_one();
                Package::createInvoice($in);
                $ui->assign('csrf_token', Csrf::generateAndStoreToken());
                $ui->display('admin/plan/invoice.tpl');
            } else {
                r2(getUrl('plan/refill'), 'e', "Failed to refill account");
            }
        } else {
            r2(getUrl('plan/refill'), 'e', Lang::T('Voucher Not Valid'));
        }
        break;
    case 'deposit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('_title', Lang::T('Refill Balance'));
        $ui->assign('xfooter', $select2_customer);
        if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            $ui->assign('p', ORM::for_table('tbl_plans')->where('type', 'Balance')->find_many());
        } else {
            $ui->assign('p', ORM::for_table('tbl_plans')->where('enabled', '1')->where('type', 'Balance')->find_many());
        }
        run_hook('view_deposit'); #HOOK
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/plan/deposit.tpl');
        break;
    case 'deposit-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('plan/deposit'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        $user = _post('id_customer');
        $amount = _post('amount');
        $plan = _post('id_plan');
        $note = _post('note');
        $svoucher = _req('svoucher');
        $c = ORM::for_table('tbl_customers')->find_one($user);
        if (App::getVoucherValue($svoucher)) {
            $in = ORM::for_table('tbl_transactions')->find_one(App::getVoucherValue($svoucher));
            Package::createInvoice($in);
            $ui->assign('csrf_token', Csrf::generateAndStoreToken());
            $ui->display('admin/plan/invoice.tpl');
            die();
        }

        run_hook('deposit_customer'); #HOOK
        if (!empty($user) && strlen($amount) > 0 && $amount != 0) {
            $plan = [];
            $plan['name_plan'] = Lang::T('Balance');
            $plan['price'] = $amount;
            $trxId = Package::rechargeBalance($c, $plan, "Deposit", $admin['fullname'], $note);
            if ($trxId > 0) {
                $in = ORM::for_table('tbl_transactions')->find_one($trxId);
                Package::createInvoice($in);
                if (!empty($svoucher)) {
                    App::setVoucher($svoucher, $trxId);
                }
                $ui->assign('csrf_token', Csrf::generateAndStoreToken());
                $ui->display('admin/plan/invoice.tpl');
            } else {
                r2(getUrl('plan/refill'), 'e', "Failed to refill account");
            }
        } else if (!empty($user) && !empty($plan)) {
            $p = ORM::for_table('tbl_plans')->find_one($plan);
            $trxId = Package::rechargeBalance($c, $p, "Deposit", $admin['fullname'], $note);
            if ($trxId > 0) {
                $in = ORM::for_table('tbl_transactions')->find_one($trxId);
                Package::createInvoice($in);
                if (!empty($svoucher)) {
                    App::setVoucher($svoucher, $trxId);
                }
                $ui->assign('csrf_token', Csrf::generateAndStoreToken());
                $ui->display('admin/plan/invoice.tpl');
            } else {
                r2(getUrl('plan/refill'), 'e', "Failed to refill account");
            }
        } else {
            r2(getUrl('plan/refill'), 'e', "All field is required");
        }
        break;
    case 'extend':
        $id = $routes[2];
        $days = $routes[3];
        $svoucher = $_GET['svoucher'];
        if (App::getVoucherValue($svoucher)) {
            r2(getUrl('plan'), 's', "Extend already done");
        }
        $tur = ORM::for_table('tbl_user_recharges')->find_one($id);
        $status = $tur['status'];
        if ($status == 'off') {
            if (strtotime($tur['expiration'] . ' ' . $tur['time']) > time()) {
                // not expired
                $expiration = date('Y-m-d', strtotime($tur['expiration'] . " +$days day"));
            } else {
                //expired
                $expiration = date('Y-m-d', strtotime(" +$days day"));
            }
            App::setVoucher($svoucher, $id);
            $c = ORM::for_table('tbl_customers')->findOne($tur['customer_id']);
            if ($c) {
                $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
                if ($p) {
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'Demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            global $isChangePlan;
                            $isChangePlan = true;
                            (new $p['device'])->add_customer($c, $p);
                        } else {
                            new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                    $tur->expiration = $expiration;
                    $tur->status = "on";
                    $tur->save();
                } else {
                    r2(getUrl('plan'), 's', "Plan not found");
                }
            } else {
                r2(getUrl('plan'), 's', "Customer not found");
            }
            Message::sendTelegram("#u$tur[username] #id$tur[customer_id]  #extend by $admin[fullname] #" . $p['type'] . " \n" . $p['name_plan'] .
                "\nLocation: " . $p['routers'] .
                "\nCustomer: " . $c['fullname'] .
                "\nNew Expired: " . Lang::dateAndTimeFormat($expiration, $tur['time']));
            _log("$admin[fullname] extend Customer $tur[customer_id] $tur[username] #$tur[customer_id] for $days days", $admin['user_type'], $admin['id']);
            r2(getUrl('plan'), 's', "Extend until $expiration");
        } else {
            r2(getUrl('plan'), 's', "Customer is not expired yet");
        }
        break;
    default:
        $ui->assign('_title', Lang::T('Customer'));

        $search = _req('search');
        $status = _req('status', '-');
        $router = _req('router');
        if (!empty($router) && !_router_can_access_router($router, $admin, ['radius'])) {
            $router = '';
        }
        $plan = _req('plan');

        $buildFilters = static function ($searchValue, $statusValue, $routerValue, $planValue) {
            $filters = [];

            if ($searchValue !== '') {
                $filters['search'] = $searchValue;
            }

            if ($routerValue !== '') {
                $filters['router'] = $routerValue;
            }

            if ($planValue !== '') {
                $filters['plan'] = $planValue;
            }

            if ($statusValue !== '-' && $statusValue !== '' && in_array($statusValue, ['on', 'off'], true)) {
                $filters['status'] = $statusValue;
            }

            return $filters;
        };

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf_token = Csrf::getTokenFromRequest();
            if ($csrf_token !== '' && !Csrf::check($csrf_token)) {
                _msglog('e', Lang::T('Invalid CSRF token'));
            }

            $search = _post('search', $search);
            $status = _post('status', $status);
            $router = _post('router', $router);
            if (!empty($router) && !_router_can_access_router($router, $admin, ['radius'])) {
                $router = '';
            }
            $plan = _post('plan', $plan);

            $queryParams = $buildFilters($search, $status, $router, $plan);

            $redirectUrl = getUrl('plan/list');
            if (!empty($queryParams)) {
                $redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&')
                    . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
            }

            header('Location: ' . $redirectUrl);
            exit;
        }

        $filters = $buildFilters($search, $status, $router, $plan);

        $filterQueryString = http_build_query($filters, '', '&', PHP_QUERY_RFC3986);
        $append_url = $filterQueryString !== '' ? '&' . $filterQueryString : '';
        $ui->assign('append_url', $append_url);
        $ui->assign('plan', $plan);
        $ui->assign('status', $status);
        $ui->assign('router', $router);
        $ui->assign('routers', _router_filter_allowed_names(
            array_column(
                ORM::for_table('tbl_user_recharges')
                    ->distinct()
                    ->select('tbl_user_recharges.routers', 'routers')
                    ->whereNotEqual('tbl_user_recharges.routers', '')
                    ->findArray(),
                'routers'
            ),
            $admin,
            ['radius']
        ));

        $plns = ORM::for_table('tbl_user_recharges')
            ->distinct()
            ->select('tbl_user_recharges.plan_id', 'plan_id')
            ->findArray();
        $ids = array_column($plns, 'plan_id');
        if (count($ids)) {
            $ui->assign('plans', ORM::for_table('tbl_plans')->select('id')->select('name_plan')->where_id_in($ids)->findArray());
        } else {
            $ui->assign('plans', []);
        }
        $query = ORM::for_table('tbl_user_recharges')
            ->select('tbl_user_recharges.*')
            ->select('tbl_customers.fullname', 'customer_fullname')
            ->left_outer_join('tbl_customers', ['tbl_user_recharges.customer_id', '=', 'tbl_customers.id'])
            ->order_by_desc('tbl_user_recharges.id');

        if (($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $allowedRechargeRouters = $accessibleRouterNames;
            if (!empty($config['radius_enable'])) {
                $allowedRechargeRouters[] = 'radius';
            }
            $allowedRechargeRouters = array_values(array_unique(array_filter($allowedRechargeRouters)));
            if (empty($allowedRechargeRouters)) {
                $query->where('tbl_user_recharges.id', -1);
            } else {
                $query->where_in('tbl_user_recharges.routers', $allowedRechargeRouters);
            }
        }

        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where_raw(
                '(
                    tbl_user_recharges.username LIKE ?
                    OR tbl_customers.fullname LIKE ?
                    OR tbl_customers.address LIKE ?
                    OR tbl_customers.phonenumber LIKE ?
                    OR tbl_customers.email LIKE ?
                )',
                [
                    "%$searchTerm%",
                    "%$searchTerm%",
                    "%$searchTerm%",
                    "%$searchTerm%",
                    "%$searchTerm%",
                ]
            );
        }
        if (isset($filters['router'])) {
            $query->where('tbl_user_recharges.routers', $filters['router']);
        }
        if (isset($filters['plan'])) {
            $query->where('tbl_user_recharges.plan_id', $filters['plan']);
        }
        if (isset($filters['status'])) {
            $query->where('tbl_user_recharges.status', $filters['status']);
        }
        $d = Paginator::findMany($query, $filters, 25);
        run_hook('view_list_billing'); #HOOK
        $ui->assign('d', $d);
        $ui->assign('search', htmlspecialchars($search, ENT_QUOTES, 'UTF-8'));
        $ui->display('admin/plan/active.tpl');
        break;
}
