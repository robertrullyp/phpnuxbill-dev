<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Custom Fields'));
$ui->assign('_system_menu', 'settings');

$action = $routes['1'];
$ui->assign('_admin', $admin);

$fieldPath = $UPLOAD_PATH . DIRECTORY_SEPARATOR . "customer_field.json";

switch ($action) {
    case 'save':
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            r2(getUrl('customfield'), 'e', Lang::T('Invalid request method'));
        }

        $names = $_POST['name'] ?? null;
        if (!is_array($names)) {
            r2(getUrl('customfield'), 'e', Lang::T('Invalid payload'));
        }

        $orders = $_POST['order'] ?? [];
        $types = $_POST['type'] ?? [];
        $placeholders = $_POST['placeholder'] ?? [];
        $values = $_POST['value'] ?? [];
        $registers = $_POST['register'] ?? [];
        $requireds = $_POST['required'] ?? [];

        $datas = [];
        $count = count($names);
        for ($n = 0; $n < $count; $n++) {
            $rawName = $names[$n] ?? '';
            if (empty($rawName)) {
                continue;
            }

            $datas[] = [
                'order' => $orders[$n] ?? $n,
                'name' => Text::alphanumeric(strtolower(str_replace(' ', '_', $rawName)), '_'),
                'type' => $types[$n] ?? 'text',
                'placeholder' => $placeholders[$n] ?? '',
                'value' => $values[$n] ?? '',
                'register' => $registers[$n] ?? 'no',
                'required' => $requireds[$n] ?? 'no',
            ];
        }

        if (count($datas) > 1) {
            usort($datas, function ($item1, $item2) {
                return $item1['order'] <=> $item2['order'];
            });
        }

        if (file_put_contents($fieldPath, json_encode($datas), LOCK_EX) !== false) {
            r2(getUrl('customfield'), 's', 'Successfully saved custom fields!');
        } else {
            r2(getUrl('customfield'), 'e', 'Failed to save custom fields!');
        }
        break;
    default:
        $fields = [];
        if(file_exists($fieldPath)){
            $fields = json_decode(file_get_contents($fieldPath), true);
        }
        $ui->assign('fields', $fields);
        $ui->display('admin/settings/customfield.tpl');
        break;
}
