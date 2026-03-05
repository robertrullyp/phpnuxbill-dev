<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Send Message'));
$ui->assign('_system_menu', 'message');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (empty($action)) {
    $action = 'send';
}

$isRequestTruthy = static function ($keys, array $source = null) {
    if (!is_array($keys)) {
        $keys = [$keys];
    }
    if ($source === null) {
        $source = $_POST;
    }
    foreach ($keys as $key) {
        if (!array_key_exists($key, $source)) {
            continue;
        }
        return Package::isTruthyValue($source[$key]);
    }
    return false;
};

$resolveResendChannel = static function ($messageType) {
    $type = strtolower(trim((string) $messageType));
    if ($type === '') {
        return 'wa';
    }
    if (strpos($type, 'sms') !== false || strpos($type, 'mikrotik') !== false) {
        return 'sms';
    }
    if (strpos($type, 'inbox') !== false) {
        return 'inbox';
    }
    if (strpos($type, 'email') !== false) {
        return 'email';
    }
    if (
        strpos($type, 'whatsapp') !== false ||
        strpos($type, 'wa ') === 0 ||
        strpos($type, 'wa_') === 0 ||
        strpos($type, 'wa-') === 0
    ) {
        return 'wa';
    }
    return 'wa';
};

$extractWaPayload = static function ($message) {
    $trimmed = ltrim((string) $message);
    if ($trimmed === '') {
        return null;
    }
    if ($trimmed[0] !== '{' && $trimmed[0] !== '[') {
        return null;
    }
    $decoded = json_decode($trimmed, true);
    if (!is_array($decoded)) {
        return null;
    }
    $isAssoc = array_keys($decoded) !== range(0, count($decoded) - 1);
    if (!$isAssoc) {
        return null;
    }
    $payloadKeys = ['action', 'to', 'text', 'body', 'message', 'interactive', 'requestPhoneNumber', 'contacts'];
    foreach ($payloadKeys as $payloadKey) {
        if (array_key_exists($payloadKey, $decoded)) {
            return $decoded;
        }
    }
    return null;
};

$sendResendByChannel = static function ($channel, $recipient, $message, $subject = '') use ($extractWaPayload) {
    $result = [
        'raw_result' => false,
        'blocking_error' => '',
    ];
    if ($channel === 'wa') {
        $payload = $extractWaPayload($message);
        if (is_array($payload)) {
            unset($payload['idempotency_key'], $payload['idempotencyKey']);
            if (!isset($payload['action'])) {
                $payload['action'] = 'send';
            }
            $payload['to'] = Lang::phoneFormat($recipient);
            $result['raw_result'] = Message::sendWhatsapp($payload, '', ['queue_context' => 'resend']);
        } else {
            $result['raw_result'] = Message::sendWhatsapp($recipient, $message, ['queue_context' => 'resend']);
        }
        return $result;
    }

    if ($channel === 'sms') {
        $result['raw_result'] = Message::sendSMS($recipient, $message);
        return $result;
    }

    if ($channel === 'email') {
        if ($subject === '') {
            $subject = Lang::T('Notification Message');
        }
        if (function_exists('mb_substr')) {
            $subject = mb_substr($subject, 0, 255, 'UTF-8');
        } else {
            $subject = substr($subject, 0, 255);
        }
        $result['raw_result'] = Message::sendEmail($recipient, $subject, $message);
        return $result;
    }

    if ($channel === 'inbox') {
        if ($subject === '') {
            $subject = Lang::T('Notification Message');
        }
        if (function_exists('mb_substr')) {
            $subject = mb_substr($subject, 0, 255, 'UTF-8');
        } else {
            $subject = substr($subject, 0, 255);
        }
        $customer = ORM::for_table('tbl_customers')->where('username', $recipient)->find_one();
        if (!$customer && ctype_digit((string) $recipient)) {
            $customer = ORM::for_table('tbl_customers')->find_one((int) $recipient);
        }
        if (!$customer) {
            $result['blocking_error'] = Lang::T('Customer not found');
            return $result;
        }
        $result['raw_result'] = Message::addToInbox((int) $customer['id'], $subject, $message, 'Admin');
        return $result;
    }

    $result['blocking_error'] = Lang::T('Unsupported channel for resend');
    return $result;
};

$collectResendAttemptLogs = static function ($minLogId, $recipient, $channel) use ($resolveResendChannel) {
    $query = ORM::for_table('tbl_message_logs')
        ->where_gt('id', (int) $minLogId);
    $recipientCandidates = [(string) $recipient];
    $formattedRecipient = trim((string) Lang::phoneFormat($recipient));
    if ($formattedRecipient !== '' && !in_array($formattedRecipient, $recipientCandidates, true)) {
        $recipientCandidates[] = $formattedRecipient;
    }
    if (count($recipientCandidates) === 1) {
        $query->where('recipient', $recipientCandidates[0]);
    } else {
        $query->where_in('recipient', $recipientCandidates);
    }

    $attemptLogs = $query
        ->order_by_desc('id')
        ->limit(50)
        ->find_array();
    $filtered = [];
    foreach ($attemptLogs as $attemptLog) {
        if ($resolveResendChannel($attemptLog['message_type'] ?? '') === $channel) {
            $filtered[] = $attemptLog;
        }
    }
    return $filtered;
};

$resolveResendAttemptOutcome = static function (array $attemptLogs, $rawSendResult, $fallbackError = '') {
    $latestStatus = null;
    foreach ($attemptLogs as $attemptLog) {
        $status = strtolower(trim((string) ($attemptLog['status'] ?? '')));
        if ($status === 'success') {
            $latestStatus = true;
            break;
        }
        if ($status === 'error') {
            $latestStatus = false;
            break;
        }
    }

    $isSuccess = ($latestStatus === null) ? !empty($rawSendResult) : $latestStatus;
    $latestError = '';

    if (!$isSuccess) {
        foreach ($attemptLogs as $attemptLog) {
            $errorMessage = trim((string) ($attemptLog['error_message'] ?? ''));
            if ($errorMessage === '') {
                continue;
            }
            $errorLower = strtolower($errorMessage);
            $typeLower = strtolower(trim((string) ($attemptLog['message_type'] ?? '')));
            if ($errorLower === 'debug payload' || strpos($typeLower, 'payload') !== false) {
                continue;
            }
            $latestError = $errorMessage;
            break;
        }

        if ($latestError === '') {
            foreach ($attemptLogs as $attemptLog) {
                $errorMessage = trim((string) ($attemptLog['error_message'] ?? ''));
                if ($errorMessage !== '') {
                    $latestError = $errorMessage;
                    break;
                }
            }
        }

        if ($latestError === '' && is_string($rawSendResult)) {
            $latestError = trim($rawSendResult);
        }
        if ($latestError === '') {
            $latestError = trim((string) $fallbackError);
        }
        if ($latestError === '') {
            $latestError = Lang::T('Failed to resend message');
        }
    }

    return [
        'success' => $isSuccess,
        'error_message' => $latestError,
    ];
};

$resendAndSyncLog = static function ($log, $channel, $recipient, $message, $subject = '') use (
    $sendResendByChannel,
    $collectResendAttemptLogs,
    $resolveResendAttemptOutcome
) {
    $startMaxLogId = (int) ORM::for_table('tbl_message_logs')->max('id');
    $sendResult = $sendResendByChannel($channel, $recipient, $message, $subject);

    if ($sendResult['blocking_error'] !== '') {
        $outcome = [
            'success' => false,
            'error_message' => $sendResult['blocking_error'],
        ];
    } else {
        $attemptLogs = $collectResendAttemptLogs($startMaxLogId, $recipient, $channel);
        $outcome = $resolveResendAttemptOutcome(
            $attemptLogs,
            $sendResult['raw_result'],
            $sendResult['blocking_error']
        );
    }

    $log->recipient = $recipient;
    $log->message_content = $message;
    $log->status = $outcome['success'] ? 'Success' : 'Error';
    $log->error_message = $outcome['success'] ? '' : $outcome['error_message'];
    $log->sent_at = date('Y-m-d H:i:s');
    $log->save();

    return $outcome;
};

switch ($action) {
    case 'send':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

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
        if (isset($routes['2']) && !empty($routes['2'])) {
            $ui->assign('cust', ORM::for_table('tbl_customers')->find_one($routes['2']));
        }
        $id = $routes['2'];
        $ui->assign('id', $id);
        $ui->assign('xfooter', $select2_customer);
        $ui->display('admin/message/single.tpl');
        break;

    case 'send-post':
        // Check user permissions
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $id_customer = $_POST['id_customer'] ?? '';
        $message = $_POST['message'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $smsSelected = $isRequestTruthy('sms');
        $waSelected = $isRequestTruthy('wa');
        $emailSelected = $isRequestTruthy(['email', 'mail']);
        $inboxSelected = $isRequestTruthy('inbox');


        // Validate subject based on the selected channel
        if (empty($id_customer)) {
            r2(getUrl('message/send'), 'e', Lang::T('Please select a customer'));
        }

        if (empty($subject) && ($emailSelected || $inboxSelected)) {
            r2(getUrl('message/send'), 'e', Lang::T('Subject is required'));
        }

        if (empty($message)) {
            r2(getUrl('message/send'), 'e', Lang::T('Message is required'));
        }

        if (!($smsSelected || $waSelected || $emailSelected || $inboxSelected)) {
            r2(getUrl('message/send'), 'e', Lang::T('Please select at least one channel type'));
        }

        $customer = ORM::for_table('tbl_customers')->find_one($id_customer);
        if (!$customer) {
            r2(getUrl('message/send'), 'e', Lang::T('Customer not found'));
        }

        // Replace placeholders in message and subject
        $currentMessage = str_replace(
            ['[[name]]', '[[user_name]]', '[[phone]]', '[[company_name]]'],
            [$customer['fullname'], $customer['username'], $customer['phonenumber'], $config['CompanyName']],
            $message
        );

        $currentSubject = str_replace(
            ['[[name]]', '[[user_name]]', '[[phone]]', '[[company_name]]'],
            [$customer['fullname'], $customer['username'], $customer['phonenumber'], $config['CompanyName']],
            $subject
        );

        if (strpos($message, '[[payment_link]]') !== false) {
            $token = User::generateToken($customer['id'], 1);
            if (!empty($token['token'])) {
                $tur = ORM::for_table('tbl_user_recharges')
                    ->where('customer_id', $customer['id'])
                    ->find_one();
                if ($tur) {
                    $url = '?_route=home&recharge=' . $tur['id'] . '&uid=' . urlencode($token['token']);
                    $currentMessage = str_replace('[[payment_link]]', $url, $currentMessage);
                }
            } else {
                $currentMessage = str_replace('[[payment_link]]', '', $currentMessage);
            }
        }

        // Send the message through the selected channels
        $smsSent = $waSent = $emailSent = $inboxSent = false;

        if ($smsSelected) {
            $smsSent = Message::sendSMS($customer['phonenumber'], $currentMessage);
        }

        if ($waSelected) {
            $queueWa = $isRequestTruthy('wa_queue');
            $waOptions = $queueWa ? ['queue' => true, 'queue_context' => 'manual'] : [];
            $waSent = Message::sendWhatsapp($customer['phonenumber'], $currentMessage, $waOptions);
        }

        if ($emailSelected) {
            $emailSent = Message::sendEmail($customer['email'], $currentSubject, $currentMessage);
        }

        if ($inboxSelected) {
            $inboxSent = Message::addToInbox($customer['id'], $currentSubject, $currentMessage, 'Admin');
        }

        // Check if any message was sent successfully
        if ($smsSent || $waSent || $emailSent || $inboxSent) {
            r2(getUrl('message/send'), 's', Lang::T('Message Sent Successfully'));
        } else {
            r2(getUrl('message/send'), 'e', Lang::T('Failed to send message'));
        }

        break;

    case 'wa_media_upload':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        global $UPLOAD_PATH;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'message' => 'Invalid request method']);
            exit;
        }
        if (empty($_FILES['media'])) {
            echo json_encode(['ok' => false, 'message' => 'No file uploaded']);
            exit;
        }
        $file = $_FILES['media'];
        if (!empty($file['error'])) {
            echo json_encode(['ok' => false, 'message' => 'Upload failed']);
            exit;
        }
        $maxSize = 16 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            echo json_encode(['ok' => false, 'message' => 'File too large (max 16MB)']);
            exit;
        }
        $tmpName = $file['tmp_name'];
        $mime = function_exists('mime_content_type') ? mime_content_type($tmpName) : ($file['type'] ?? '');
        $allowed = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/3gpp',
            'application/pdf'
        ];
        if (!in_array($mime, $allowed, true)) {
            echo json_encode(['ok' => false, 'message' => 'Unsupported file type']);
            exit;
        }
        $mediaId = bin2hex(random_bytes(8));
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/3gpp' => '3gp',
            'application/pdf' => 'pdf'
        ];
        $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
        $baseName = preg_replace('/[^A-Za-z0-9_-]/', '_', $baseName);
        if ($baseName === '') {
            $baseName = 'media';
        }
        $ext = $extMap[$mime] ?? pathinfo($file['name'], PATHINFO_EXTENSION);
        $ext = preg_replace('/[^A-Za-z0-9]/', '', $ext);
        if ($ext === '') {
            $ext = 'bin';
        }
        $safeName = $baseName . '.' . $ext;
        $destDir = rtrim($UPLOAD_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wa_tmp' . DIRECTORY_SEPARATOR . $mediaId;
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            echo json_encode(['ok' => false, 'message' => 'Failed to create upload folder']);
            exit;
        }
        $destPath = $destDir . DIRECTORY_SEPARATOR . $safeName;
        if (!move_uploaded_file($tmpName, $destPath)) {
            echo json_encode(['ok' => false, 'message' => 'Failed to store upload']);
            exit;
        }
        $publicUrl = rtrim(APP_URL, '/') . '/system/uploads/wa_tmp/' . $mediaId . '/' . $safeName;
        $expiresAt = date('Y-m-d H:i:s', time() + 7 * 24 * 60 * 60);
        Message::cleanupExpiredWhatsappMedia();
        try {
            $db = ORM::get_db();
            $db->exec("
                CREATE TABLE IF NOT EXISTS tbl_wa_media_tmp (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    media_id VARCHAR(64) UNIQUE NOT NULL,
                    file_path TEXT NOT NULL,
                    public_url TEXT NOT NULL,
                    mime_type VARCHAR(100),
                    size INT DEFAULT 0,
                    status ENUM('active','cleaned','expired') DEFAULT 'active',
                    created_at DATETIME NOT NULL,
                    expires_at DATETIME NOT NULL,
                    last_used_at DATETIME NULL
                );
            ");
        } catch (Throwable $e) {
            // ignore
        }
        $media = ORM::for_table('tbl_wa_media_tmp')->create();
        $media->media_id = $mediaId;
        $media->file_path = $destPath;
        $media->public_url = $publicUrl;
        $media->mime_type = $mime;
        $media->size = (int) $file['size'];
        $media->status = 'active';
        $media->created_at = date('Y-m-d H:i:s');
        $media->expires_at = $expiresAt;
        $media->save();
        echo json_encode([
            'ok' => true,
            'media_id' => $mediaId,
            'url' => $publicUrl,
            'mime' => $mime,
            'expires_at' => $expiresAt
        ]);
        exit;

    case 'resend':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $logId = $routes['2'] ?? _get('id');
        $log = ORM::for_table('tbl_message_logs')->find_one($logId);
        if (!$log) {
            r2(getUrl('logs/message'), 'e', Lang::T('Log not found'));
        }
        $type = strtolower((string) $log['message_type']);
        $channel = $resolveResendChannel($log['message_type']);
        $resendSubject = Lang::T('Notification Message');
        $payloadUsed = false;
        if ($channel === 'wa') {
            $content = trim((string) $log['message_content']);
            $looksInteractive = false;
            if ($content !== '') {
                $firstChar = $content[0];
                $looksInteractive = ($firstChar === '{' || $firstChar === '[' || stripos($content, '[[wa]]') !== false);
            }
            if (!$looksInteractive && strpos($type, 'response') !== false) {
                $sentAt = $log['sent_at'] ?? '';
                $baseTime = $sentAt ? strtotime($sentAt) : time();
                if ($baseTime !== false) {
                    $start = date('Y-m-d H:i:s', $baseTime - 600);
                    $end = date('Y-m-d H:i:s', $baseTime + 600);
                    $payloadLog = ORM::for_table('tbl_message_logs')
                        ->where('message_type', 'WhatsApp Gateway Payload')
                        ->where('recipient', $log['recipient'])
                        ->where_gte('sent_at', $start)
                        ->where_lte('sent_at', $end)
                        ->order_by_desc('id')
                        ->find_one();
                    if ($payloadLog && !empty($payloadLog['message_content'])) {
                        $payloadContent = trim((string) $payloadLog['message_content']);
                        $decoded = null;
                        $hasContent = false;
                        if ($payloadContent !== '' && ($payloadContent[0] === '{' || $payloadContent[0] === '[')) {
                            $decoded = json_decode($payloadContent, true);
                            if (is_array($decoded) && array_keys($decoded) !== range(0, count($decoded) - 1)) {
                                if (!empty($decoded['interactive'])) {
                                    $hasContent = true;
                                } elseif (!empty($decoded['text']) || !empty($decoded['body'])) {
                                    $hasContent = true;
                                } elseif (!empty($decoded['message'])) {
                                    $hasContent = true;
                                } elseif (!empty($decoded['requestPhoneNumber'])) {
                                    $hasContent = true;
                                } elseif (!empty($decoded['contacts'])) {
                                    $hasContent = true;
                                }
                            }
                        }
                        if ($hasContent) {
                            $log['message_content'] = $payloadLog['message_content'];
                            $payloadUsed = true;
                        }
                    }
                }
            }
        }
        $ui->assign('log', $log);
        $ui->assign('channel', $channel);
        $ui->assign('resend_subject', $resendSubject);
        $ui->assign('payload_used', $payloadUsed);
        $ui->display('admin/message/resend.tpl');
        break;

    case 'resend-now':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $logId = $routes['2'] ?? _get('id');
        $log = ORM::for_table('tbl_message_logs')->find_one($logId);
        if (!$log) {
            r2(getUrl('logs/message'), 'e', Lang::T('Log not found'));
        }
        $status = strtolower(trim((string) ($log['status'] ?? '')));
        if ($status !== 'error') {
            r2(getUrl('logs/message'), 'e', Lang::T('Only error logs can be resent'));
        }
        $channel = $resolveResendChannel($log['message_type']);
        if (!in_array($channel, ['wa', 'sms', 'email', 'inbox'], true)) {
            r2(getUrl('logs/message'), 'e', Lang::T('Unsupported channel for resend'));
        }

        $recipient = trim((string) $log['recipient']);
        $message = trim((string) $log['message_content']);
        if ($recipient === '' || $message === '') {
            $log->status = 'Error';
            $log->error_message = Lang::T('Recipient and message are required');
            $log->sent_at = date('Y-m-d H:i:s');
            $log->save();
            r2(getUrl('logs/message'), 'e', Lang::T('Recipient and message are required'));
        }

        $outcome = $resendAndSyncLog($log, $channel, $recipient, $message, Lang::T('Notification Message'));
        if (!empty($outcome['success'])) {
            r2(getUrl('logs/message'), 's', Lang::T('Message resent successfully'));
        }
        r2(getUrl('logs/message'), 'e', Lang::T('Failed to resend message'));
        break;

    case 'resend-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $logId = _post('log_id');
        $recipient = trim((string) _post('recipient'));
        $message = trim((string) _post('message'));
        $subject = trim((string) _post('subject'));
        $channel = trim((string) _post('channel'));
        if ($recipient === '' || $message === '') {
            r2(getUrl('message/resend/' . $logId), 'e', Lang::T('Recipient and message are required'));
        }
        $log = ORM::for_table('tbl_message_logs')->find_one($logId);
        if (!$log) {
            r2(getUrl('logs/message'), 'e', Lang::T('Log not found'));
        }
        if ($channel === '') {
            $channel = $resolveResendChannel($log['message_type']);
        }
        if (!in_array($channel, ['wa', 'sms', 'email', 'inbox'], true)) {
            r2(getUrl('message/resend/' . $logId), 'e', Lang::T('Unsupported channel for resend'));
        }
        $outcome = $resendAndSyncLog($log, $channel, $recipient, $message, $subject);
        if (!empty($outcome['success'])) {
            r2(getUrl('logs/message'), 's', Lang::T('Message resent successfully'));
        }
        r2(getUrl('message/resend/' . $logId), 'e', $outcome['error_message']);
        break;

    case 'send_bulk':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $serviceTypes = ['PPPoE', 'Hotspot', 'VPN', 'Others'];
        $customerServiceTypes = ORM::for_table('tbl_customers')
            ->select('service_type')
            ->distinct()
            ->find_array();
        foreach ($customerServiceTypes as $customerServiceType) {
            $serviceType = trim((string) ($customerServiceType['service_type'] ?? ''));
            if ($serviceType !== '' && !in_array($serviceType, $serviceTypes, true)) {
                $serviceTypes[] = $serviceType;
            }
        }

        $ui->assign('routers', _router_get_accessible_routers($admin, true));
        $ui->assign('service_types', $serviceTypes);
        $ui->display('admin/message/bulk.tpl');
        break;

    case 'send_bulk_ajax':
        // Check user permissions
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            die(json_encode(['status' => 'error', 'message' => 'Permission denied']));
        }

        set_time_limit(0);

        // Get request parameters
        $group = trim((string) ($_REQUEST['group'] ?? ''));
        $message = trim((string) ($_REQUEST['message'] ?? ''));
        $batch = (int) ($_REQUEST['batch'] ?? 100);
        $batch = $batch > 0 ? $batch : 100;
        $page = (int) ($_REQUEST['page'] ?? 0);
        $page = $page >= 0 ? $page : 0;
        $router = $_REQUEST['router'] ?? null;
        $test = $isRequestTruthy('test', $_REQUEST);
        $serviceInput = $_REQUEST['service'] ?? [];
        $subject = trim((string) ($_REQUEST['subject'] ?? ''));
        $routerName = '';
        $selectedChannels = [];
        $queueWa = $isRequestTruthy('wa_queue', $_REQUEST);

        if (!is_array($serviceInput)) {
            $serviceInput = explode(',', (string) $serviceInput);
        }
        $serviceInput = array_values(array_unique(array_filter(array_map('trim', $serviceInput), static function ($value) {
            return $value !== '';
        })));
        $serviceAllSelected = in_array('all', $serviceInput, true);
        $selectedServices = $serviceAllSelected
            ? []
            : array_values(array_filter($serviceInput, static function ($value) {
                return $value !== 'all';
            }));
        $serviceLabel = $serviceAllSelected ? Lang::T('All') : implode(', ', $selectedServices);
        $applyServiceFilter = static function ($query, $column) use ($serviceAllSelected, $selectedServices) {
            if ($serviceAllSelected) {
                return;
            }
            if (empty($selectedServices)) {
                $query->where_raw('1 = 0');
                return;
            }
            $query->where_in($column, $selectedServices);
        };

        $smsSelected = $isRequestTruthy('sms', $_REQUEST);
        $waSelected = $isRequestTruthy(['wa', 'whatsapp'], $_REQUEST);
        $emailSelected = $isRequestTruthy(['email', 'mail'], $_REQUEST);
        $inboxSelected = $isRequestTruthy('inbox', $_REQUEST);

        if ($emailSelected) {
            $selectedChannels[] = 'email';
        }
        if ($smsSelected) {
            $selectedChannels[] = 'sms';
        }
        if ($waSelected) {
            $selectedChannels[] = 'wa';
        }
        if ($inboxSelected) {
            $selectedChannels[] = 'inbox';
        }

        if (empty($selectedChannels)) {
            die(json_encode(['status' => 'error', 'message' => Lang::T('Please select at least one channel type')]));
        }

        if (empty($group) || $message === '' || (!$serviceAllSelected && empty($selectedServices))) {
            die(json_encode(['status' => 'error', 'message' => LANG::T('All fields are required')]));
        }

        if (($admin['user_type'] ?? '') !== 'SuperAdmin' && empty($router)) {
            die(json_encode(['status' => 'error', 'message' => LANG::T('Please select router')]));
        }

        if (array_intersect($selectedChannels, ['email', 'inbox']) && empty($subject)) {
            die(json_encode(['status' => 'error', 'message' => LANG::T('Subject is required') . '.']));
        }

        // Get batch of customers based on group
        $startpoint = $page * $batch;
        $customers = [];
        $totalCustomers = 0;

        if (isset($router) && !empty($router)) {
            switch ($router) {
                case 'radius':
                    $routerName = 'Radius';
                    break;
                default:
                    if (!_router_can_access_router((string) $router, $admin, ['radius'])) {
                        die(json_encode(['status' => 'error', 'message' => LANG::T('Invalid router')]));
                    }
                    $routerRow = ORM::for_table('tbl_routers')->find_one((int) $router);
                    if (!$routerRow || !_router_can_access_router((string) $routerRow->name, $admin, ['radius'])) {
                        die(json_encode(['status' => 'error', 'message' => LANG::T('Invalid router')]));
                    }
                    $routerName = $routerRow->name;
                    break;
            }
        }

        if (isset($router) && !empty($router)) {
            $query = ORM::for_table('tbl_user_recharges')
                ->left_outer_join('tbl_customers', 'tbl_user_recharges.customer_id = tbl_customers.id')
                ->where('tbl_user_recharges.routers', $routerName);
            $applyServiceFilter($query, 'tbl_customers.service_type');

            switch ($group) {
                case 'all':
                    break;
                case 'new':
                    $query->where_raw("DATE(tbl_user_recharges.recharged_on) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
                    break;
                case 'expired':
                    $query->where('tbl_user_recharges.status', 'off');
                    break;
                case 'active':
                    $query->where('tbl_user_recharges.status', 'on');
                    break;
                default:
                    die(json_encode(['status' => 'error', 'message' => LANG::T('Invalid group')]));
            }

            $totalCustomers = $query->count();

            $query->offset($startpoint)
                ->limit($batch);

            // Fetch the customers
            $query->selects([
                ['tbl_customers.phonenumber', 'phonenumber'],
                ['tbl_user_recharges.customer_id', 'customer_id'],
                ['tbl_customers.fullname', 'fullname'],
                ['tbl_customers.username', 'username'],
                ['tbl_customers.email', 'email'],
                ['tbl_customers.service_type', 'service_type'],
            ]);
            $customers = $query->find_array();
        } else {
            switch ($group) {
                case 'all':
                    $totalCustomersQuery = ORM::for_table('tbl_customers');
                    $applyServiceFilter($totalCustomersQuery, 'service_type');
                    $totalCustomers = $totalCustomersQuery->count();
                    $customers = $totalCustomersQuery
                        ->selects([
                            ['id', 'customer_id'],
                            ['phonenumber', 'phonenumber'],
                            ['fullname', 'fullname'],
                            ['username', 'username'],
                            ['email', 'email'],
                            ['service_type', 'service_type'],
                        ])
                        ->offset($startpoint)
                        ->limit($batch)
                        ->find_array();
                    break;

                case 'new':
                    $totalCustomersQuery = ORM::for_table('tbl_customers')
                        ->where_raw("DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)");
                    $applyServiceFilter($totalCustomersQuery, 'service_type');
                    $totalCustomers = $totalCustomersQuery->count();
                    $customers = $totalCustomersQuery
                        ->selects([
                            ['id', 'customer_id'],
                            ['phonenumber', 'phonenumber'],
                            ['fullname', 'fullname'],
                            ['username', 'username'],
                            ['email', 'email'],
                            ['service_type', 'service_type'],
                        ])
                        ->offset($startpoint)
                        ->limit($batch)
                        ->find_array();
                    break;

                case 'expired':
                    $totalCustomersQuery = ORM::for_table('tbl_user_recharges')
                        ->left_outer_join('tbl_customers', 'tbl_user_recharges.customer_id = tbl_customers.id')
                        ->where('tbl_user_recharges.status', 'off');
                    $applyServiceFilter($totalCustomersQuery, 'tbl_customers.service_type');
                    $totalCustomers = $totalCustomersQuery->count();
                    $customers = $totalCustomersQuery
                        ->selects([
                            ['tbl_customers.id', 'customer_id'],
                            ['tbl_customers.phonenumber', 'phonenumber'],
                            ['tbl_customers.fullname', 'fullname'],
                            ['tbl_customers.username', 'username'],
                            ['tbl_customers.email', 'email'],
                            ['tbl_customers.service_type', 'service_type'],
                        ])
                        ->offset($startpoint)
                        ->limit($batch)
                        ->find_array();
                    break;

                case 'active':
                    $totalCustomersQuery = ORM::for_table('tbl_user_recharges')
                        ->left_outer_join('tbl_customers', 'tbl_user_recharges.customer_id = tbl_customers.id')
                        ->where('tbl_user_recharges.status', 'on');
                    $applyServiceFilter($totalCustomersQuery, 'tbl_customers.service_type');
                    $totalCustomers = $totalCustomersQuery->count();
                    $customers = $totalCustomersQuery
                        ->selects([
                            ['tbl_customers.id', 'customer_id'],
                            ['tbl_customers.phonenumber', 'phonenumber'],
                            ['tbl_customers.fullname', 'fullname'],
                            ['tbl_customers.username', 'username'],
                            ['tbl_customers.email', 'email'],
                            ['tbl_customers.service_type', 'service_type'],
                        ])
                        ->offset($startpoint)
                        ->limit($batch)
                        ->find_array();
                    break;

                default:
                    die(json_encode(['status' => 'error', 'message' => LANG::T('Invalid group')]));
                    break;
            }
        }

        // Ensure $customers is always an array
        if (!$customers) {
            $customers = [];
        }

        // Send messages
        $totalSMSSent = 0;
        $totalSMSFailed = 0;
        $totalWhatsappSent = 0;
        $totalWhatsappFailed = 0;
        $totalEmailSent = 0;
        $totalEmailFailed = 0;
        $totalInboxSent = 0;
        $totalInboxFailed = 0;
        $batchStatus = [];
        //$subject = $config['CompanyName'] . ' ' . Lang::T('Notification Message');
        $from = 'Admin';
        $currentMessage = '';

        foreach ($customers as $customer) {
            $customerId = (int) ($customer['customer_id'] ?? $customer['id'] ?? 0);
            $customerName = (string) ($customer['fullname'] ?? '');
            $customerUsername = (string) ($customer['username'] ?? '');
            $customerPhone = (string) ($customer['phonenumber'] ?? '');
            $customerEmail = (string) ($customer['email'] ?? '');
            $currentMessage = str_replace(
                ['[[name]]', '[[user_name]]', '[[phone]]', '[[company_name]]'],
                [$customerName, $customerUsername, $customerPhone, $config['CompanyName']],
                $message
            );

            $currentSubject = str_replace(
                ['[[name]]', '[[user_name]]', '[[phone]]', '[[company_name]]'],
                [$customerName, $customerUsername, $customerPhone, $config['CompanyName']],
                $subject
            );

            $phoneNumber = preg_replace('/\D/', '', $customerPhone);

            if (empty($phoneNumber)) {
                $batchStatus[] = [
                    'name' => $customerName,
                    'phone' => '',
                    'status' => 'No Phone Number'
                ];
                continue;
            }

            if ($test) {
                $batchStatus[] = [
                    'name' => $customerName,
                    'sent' => $customerPhone,
                    'channel' => implode(', ', array_map('ucfirst', $selectedChannels)),
                    'status' => 'Test Mode',
                    'message' => $currentMessage,
                    'service' => $serviceLabel,
                    'router' => $routerName,
                ];
            } else {
                if ($smsSelected) {
                    if (Message::sendSMS($customerPhone, $currentMessage)) {
                        $totalSMSSent++;
                        $batchStatus[] = [
                            'name' => $customerName,
                            'sent' => $customerPhone,
                            'channel' => 'SMS',
                            'status' => 'SMS Sent',
                            'message' => $currentMessage,
                            'service' => $serviceLabel,
                            'router' => $routerName,
                        ];
                    } else {
                        $totalSMSFailed++;
                        $batchStatus[] = [
                            'name' => $customerName,
                            'sent' => $customerPhone,
                            'channel' => 'SMS',
                            'status' => 'SMS Failed',
                            'message' => $currentMessage,
                            'service' => $serviceLabel,
                            'router' => $routerName,
                        ];
                    }
                }

                if ($waSelected) {
                    $waOptions = $queueWa ? ['queue' => true, 'queue_context' => 'bulk'] : [];
                    if (Message::sendWhatsapp($customerPhone, $currentMessage, $waOptions)) {
                        $totalWhatsappSent++;
                        $batchStatus[] = [
                            'name' => $customerName,
                            'sent' => $customerPhone,
                            'channel' => 'WhatsApp',
                            'status' => $queueWa ? 'WhatsApp Queued' : 'WhatsApp Sent',
                            'message' => $currentMessage,
                            'service' => $serviceLabel,
                            'router' => $routerName,
                        ];
                    } else {
                        $totalWhatsappFailed++;
                        $batchStatus[] = [
                            'name' => $customerName,
                            'sent' => $customerPhone,
                            'channel' => 'WhatsApp',
                            'status' => 'WhatsApp Failed',
                            'message' => $currentMessage,
                            'service' => $serviceLabel,
                            'router' => $routerName,
                        ];
                    }
                }

                if ($emailSelected) {
                    if (Message::sendEmail($customerEmail, $currentSubject, $currentMessage)) {
                        $totalEmailSent++;
                        $batchStatus[] = [
                            'name' => $customerName,
                            'sent' => $customerEmail,
                            'channel' => 'Email',
                            'status' => 'Email Sent',
                            'message' => $currentMessage,
                            'service' => $serviceLabel,
                            'router' => $routerName,
                        ];
                    } else {
                        $totalEmailFailed++;
                        $batchStatus[] = [
                            'name' => $customerName,
                            'sent' => $customerEmail,
                            'channel' => 'Email',
                            'status' => 'Email Failed',
                            'message' => $currentMessage,
                            'service' => $serviceLabel,
                            'router' => $routerName,
                        ];
                    }
                }

                if ($inboxSelected) {
                    if ($customerId > 0 && Message::addToInbox($customerId, $currentSubject, $currentMessage, $from)) {
                        $totalInboxSent++;
                        $batchStatus[] = [
                            'name' => $customerName,
                            'sent' => $customerUsername,
                            'channel' => 'Inbox',
                            'status' => 'Inbox Message Sent',
                            'message' => $currentMessage,
                            'service' => $serviceLabel,
                            'router' => $routerName,
                        ];
                    } else {
                        $totalInboxFailed++;
                        $batchStatus[] = [
                            'name' => $customerName,
                            'sent' => $customerUsername,
                            'channel' => 'Inbox',
                            'status' => 'Inbox Message Failed',
                            'message' => $currentMessage,
                            'service' => $serviceLabel,
                            'router' => $routerName,
                        ];
                    }
                }
            }
        }

        // Calculate if there are more customers to process
        $hasMore = ($startpoint + $batch) < $totalCustomers;

        // Return JSON response
        echo json_encode([
            'status' => 'success',
            'page' => $page + 1,
            'batchStatus' => $batchStatus,
            'message' => $currentMessage,
            'totalSent' => $totalSMSSent + $totalWhatsappSent + $totalEmailSent + $totalInboxSent,
            'totalFailed' => $totalSMSFailed + $totalWhatsappFailed + $totalEmailFailed + $totalInboxFailed,
            'hasMore' => $hasMore,
        ]);
        break;

    case 'send_bulk_selected':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Set headers
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');

            // Get the posted data
            $customerIds = $_POST['customer_ids'] ?? [];
            $via = strtolower(trim((string) ($_POST['message_type'] ?? '')));
            if ($via === 'mail') {
                $via = 'email';
            } elseif ($via === 'whatsapp') {
                $via = 'wa';
            }
            $subject = $_POST['subject'] ?? '';
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';
            $queueWa = $isRequestTruthy('wa_queue');
            if (empty($customerIds) || empty($message) || empty($via)) {
                echo json_encode(['status' => 'error', 'message' => Lang::T('Invalid customer IDs, Message, or Message Type.')]);
                exit;
            }

            if (!in_array($via, ['all', 'sms', 'wa', 'email', 'inbox'], true)) {
                echo json_encode(['status' => 'error', 'message' => Lang::T('Invalid message type.')]);
                exit;
            }

            if (($via === 'all' || $via === 'email' || $via === 'inbox') && empty($subject)) {
                die(json_encode(['status' => 'error', 'message' => LANG::T('Subject is required to send message using') . ' ' . $via . '.']));
            }

            // Prepare to send messages
            $sentCount = 0;
            $failedCount = 0;
            $from = 'Admin';

            foreach ($customerIds as $customerId) {
                $customer = ORM::for_table('tbl_customers')->where('id', $customerId)->find_one();
                if ($customer) {
                    $messageSent = false;

                    // Check the message type and send accordingly
                    try {
                        if ($via === 'sms' || $via === 'all') {
                            $messageSent = Message::sendSMS($customer['phonenumber'], $message);
                        }
                        if (!$messageSent && ($via === 'wa' || $via === 'all')) {
                            $waOptions = $queueWa ? ['queue' => true, 'queue_context' => 'bulk_selected'] : [];
                            $messageSent = Message::sendWhatsapp($customer['phonenumber'], $message, $waOptions);
                        }
                        if (!$messageSent && ($via === 'inbox' || $via === 'all')) {
                            Message::addToInbox($customer['id'], $subject, $message, $from);
                            $messageSent = true;
                        }
                        if (!$messageSent && ($via === 'email' || $via === 'all')) {
                            $messageSent = Message::sendEmail($customer['email'], $subject, $message);
                        }
                    } catch (Throwable $e) {
                        $messageSent = false;
                        $failedCount++;
                        sendTelegram('Failed to send message to ' . $e->getMessage());
                        _log('Failed to send message to ' . $customer['fullname'] . ': ' . $e->getMessage());
                        continue;
                    }

                    if ($messageSent) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                } else {
                    $failedCount++;
                }
            }

            // Prepare the response
            echo json_encode([
                'status' => 'success',
                'totalSent' => $sentCount,
                'totalFailed' => $failedCount
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => Lang::T('Invalid request method.')]);
        }
        break;
    default:
        r2(getUrl('message/send_sms'), 'e', 'action not defined');
}
