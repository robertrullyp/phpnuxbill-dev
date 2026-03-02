<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$ui->assign('_title', Lang::T('Voucher'));
$ui->assign('_system_menu', 'voucher');

$action = $routes['1'];
if(!_auth(false)){
    if($action== 'invoice'){
        $id = $routes[2];
        $sign = $routes[3];
        if($sign != md5($id. $db_pass)) {
            die("beda");
        }
        if (empty($id)) {
            $in = ORM::for_table('tbl_transactions')->order_by_desc('id')->find_one();
        } else {
            $in = ORM::for_table('tbl_transactions')->where('id', $id)->find_one();
        }
        if ($in) {
            Package::createInvoice($in);
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
            $ui->assign('public_url', getUrl("voucher/invoice/$id/".md5($id. $db_pass)));
            $ui->assign('logo', $logo);
            $ui->display('customer/invoice-customer.tpl');
            die();
        } else {
            r2(getUrl('voucher/list-activated'), 'e', Lang::T('Not Found'));
        }
    }else{
        r2(getUrl('login'));
    }
}

$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {

    case 'activation':
        run_hook('view_activate_voucher'); #HOOK
        $ui->assign('code', alphanumeric(_get('code'), "-_.,"));
        $ui->display('customer/activation.tpl');
        break;

    case 'activation-post':
        $code = alphanumeric(_post('code'), "-_.,");
        $v1 = ORM::for_table('tbl_voucher')->whereRaw("BINARY code = '$code'")->where('status', 0)->find_one();
        run_hook('customer_activate_voucher'); #HOOK
        if ($v1) {
            if (Package::rechargeUser($user['id'], $v1['routers'], $v1['id_plan'], "Voucher", $code)) {
                $v1->status = "1";
                $v1->used_date = date('Y-m-d H:i:s');
                $v1->user = $user['username'];
                $v1->save();
                r2(getUrl('voucher/list-activated'), 's', Lang::T('Activation Vouchers Successfully'));
            } else {
                r2(getUrl('voucher/activation'), 'e', "Failed to refill account");
            }
        } else {
            r2(getUrl('voucher/activation'), 'e', Lang::T('Voucher Not Valid'));
        }
        break;

    case 'list-activated':
        $ui->assign('_system_menu', 'list-activated');
        $query = ORM::for_table('tbl_transactions')->where('user_id', $user['id'])->order_by_desc('id');
        $d = Paginator::findMany($query);

        if (empty($d) || $d < 5) {
            $query = ORM::for_table('tbl_transactions')->where('username', $user['username'])->order_by_desc('id');
            $d = Paginator::findMany($query);
        }

        if (!empty($d)) {
            $transactionIds = [];
            foreach ($d as $row) {
                $transactionId = (int) ($row['id'] ?? 0);
                if ($transactionId > 0) {
                    $transactionIds[] = $transactionId;
                }
            }
            $transactionIds = array_values(array_unique($transactionIds));

            $usageMap = [];
            if (!empty($transactionIds) && class_exists('PppoeUsage') && PppoeUsage::isStorageReady()) {
                try {
                    $usageRows = ORM::for_table('tbl_recharge_usage_cycles')
                        ->select('transaction_id')
                        ->select_expr('MAX(`usage_tx_bytes`)', 'usage_tx_bytes')
                        ->select_expr('MAX(`usage_rx_bytes`)', 'usage_rx_bytes')
                        ->where_in('transaction_id', $transactionIds)
                        ->group_by('transaction_id')
                        ->find_array();
                    foreach ($usageRows as $usageRow) {
                        $key = (int) ($usageRow['transaction_id'] ?? 0);
                        if ($key < 1) {
                            continue;
                        }
                        $usageMap[$key] = [
                            'tx' => max(0, (int) ($usageRow['usage_tx_bytes'] ?? 0)),
                            'rx' => max(0, (int) ($usageRow['usage_rx_bytes'] ?? 0)),
                        ];
                    }
                } catch (Throwable $e) {
                    $usageMap = [];
                }
            }

            foreach ($d as $row) {
                $transactionId = (int) ($row['id'] ?? 0);
                $txUsage = 0;
                $rxUsage = 0;
                if (isset($usageMap[$transactionId])) {
                    $txUsage = $usageMap[$transactionId]['tx'];
                    $rxUsage = $usageMap[$transactionId]['rx'];
                }
                $row->set('usage_tx_bytes', $txUsage);
                $row->set('usage_rx_bytes', $rxUsage);
            }
        }

        $ui->assign('d', $d);
        $ui->assign('_title', Lang::T('Activation History'));
        run_hook('customer_view_activation_list'); #HOOK
        $ui->display('customer/activation-list.tpl');

        break;
    case 'invoice':
        $id = $routes[2];
        if (empty($id)) {
            $in = ORM::for_table('tbl_transactions')->where('username', $user['username'])->order_by_desc('id')->find_one();
        } else {
            $in = ORM::for_table('tbl_transactions')->where('username', $user['username'])->where('id', $id)->find_one();
        }
        if ($in) {
            Package::createInvoice($in);
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
            $ui->assign('public_url', getUrl("voucher/invoice/$id/".md5($id. $db_pass)));
            $ui->assign('logo', $logo);
            $ui->display('customer/invoice-customer.tpl');
        } else {
            r2(getUrl('voucher/list-activated'), 'e', Lang::T('Not Found'));
        }
        break;
    default:
        $ui->display('admin/404.tpl');
}
