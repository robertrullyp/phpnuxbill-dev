<?php


class active_internet_plan
{
    public function getWidget()
    {
        global $ui, $user, $config;

        $rawBills = User::_billing();
        $bills = [];
        foreach ($rawBills as $billRow) {
            if ($billRow instanceof ORM) {
                $billRow = $billRow->as_array();
            } elseif (!is_array($billRow)) {
                continue;
            }
            $billRow['genieacs_eligible'] = class_exists('GenieACS')
                ? GenieACS::isEligiblePppoeBill($billRow)
                : false;
            $bills[] = $billRow;
        }
        $ui->assign('_bills', $bills);

        $tcf = ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $user['id'])
            ->find_many();
        $vpn = ORM::for_table('tbl_port_pool')
            ->find_one();
        $ui->assign('cf', $tcf);
        $ui->assign('vpn', $vpn);

        $genieacs = [
            'enabled' => false,
            'can_manage' => false,
            'device_id' => '',
            'device_label' => '',
            'ssid' => '',
            'password' => '',
            'ssid_path' => '',
            'password_path' => '',
            'error' => '',
        ];
        if (class_exists('GenieACS') && GenieACS::isEnabled($config)) {
            $genieacs['enabled'] = true;
            $genieacs['device_id'] = GenieACS::getAssignedDeviceId($user['id']);
            $genieacs['device_label'] = GenieACS::getAssignedDeviceLabel($user['id'], $genieacs['device_id']);

            $hasEligiblePppoeBill = false;
            foreach ($bills as $bill) {
                if (!empty($bill['genieacs_eligible'])) {
                    $hasEligiblePppoeBill = true;
                    break;
                }
            }

            if ($hasEligiblePppoeBill && $genieacs['device_id'] !== '') {
                $genieacs['can_manage'] = true;
                $wifi = GenieACS::readWifiCredentials($config, $genieacs['device_id']);
                if (!empty($wifi['success'])) {
                    $genieacs['ssid'] = trim((string) ($wifi['ssid'] ?? ''));
                    $genieacs['password'] = trim((string) ($wifi['password'] ?? ''));
                    $genieacs['ssid_path'] = trim((string) ($wifi['ssid_path'] ?? ''));
                    $genieacs['password_path'] = trim((string) ($wifi['password_path'] ?? ''));
                    $genieacs['device_label'] = trim((string) ($wifi['device_label'] ?? $genieacs['device_label']));
                    if ($genieacs['ssid'] !== '' || $genieacs['password'] !== '') {
                        GenieACS::saveWifiCache($user['id'], $genieacs['ssid'], $genieacs['password']);
                    }
                } else {
                    $cachedWifi = GenieACS::getWifiCache($user['id']);
                    $genieacs['ssid'] = $cachedWifi['ssid'];
                    $genieacs['password'] = $cachedWifi['password'];
                    $genieacs['error'] = trim((string) ($wifi['error'] ?? ''));
                }
            }
        }
        $ui->assign('genieacs', $genieacs);

        return $ui->fetch('widget/customers/active_internet_plan.tpl');
    }
}
