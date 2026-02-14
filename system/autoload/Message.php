<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use PEAR2\Net\RouterOS;

require $root_path . 'system/autoload/mail/Exception.php';
require $root_path . 'system/autoload/mail/PHPMailer.php';
require $root_path . 'system/autoload/mail/SMTP.php';

class Message
{

    public static function stripWhatsappTemplateBlocks($text)
    {
        if (!is_string($text)) {
            return '';
        }
        return trim((string) preg_replace('/\\[\\[wa\\]\\].*?\\[\\[\\/wa\\]\\]/is', '', $text));
    }

    /**
     * Convert a WhatsApp template (plain text, [[wa]] block, or JSON payload) to a plain text message.
     * Used for non-interactive fallback channels (SMS/Email) and for interactive-to-text fallback.
     */
    public static function whatsappTemplateToPlainText($text)
    {
        if (!is_string($text)) {
            return '';
        }

        $payload = self::parseWhatsappPayloadFromText($text);
        if (is_array($payload)) {
            // If the template is an interactive/WA payload, prefer its explicit text fallback.
            return trim((string) self::extractWhatsappTextFallback($payload));
        }

        return trim((string) preg_replace('/\\[\\[wa\\]\\].*?\\[\\[\\/wa\\]\\]/is', '', $text));
    }

    public static function buildWhatsappCopyCodeInteractivePayload($text, $copyCode, $buttonText = 'Salin Code')
    {
        $text = trim((string) $text);
        $copyCode = trim((string) $copyCode);
        $buttonText = trim((string) $buttonText);
        if ($buttonText === '') {
            $buttonText = 'Salin Code';
        }

        if ($text === '' && $copyCode !== '') {
            $text = $copyCode;
        }

        return [
            'action' => 'send',
            'interactive' => [
                'type' => 'template',
                'text' => $text,
                'buttons' => [
                    [
                        'type' => 'copy',
                        'text' => $buttonText,
                        'copyCode' => $copyCode,
                    ],
                ],
            ],
        ];
    }

    public static function renderOtpMessage($otp, $purpose = '', $purposeKey = '')
    {
        global $config;
        global $_c;

        $otp = trim((string) $otp);
        $purpose = trim((string) $purpose);
        $purposeKey = strtolower(trim((string) $purposeKey));

        $templateContext = null;
        if ($purposeKey !== '') {
            $templateContext = ['purpose' => $purposeKey];
        }

        $template = Lang::getNotifText('otp_message', $templateContext);
        if (!is_string($template)) {
            $template = '';
        }

        if (trim($template) === '') {
            $company = (string) ($config['CompanyName'] ?? 'Company');
            $template = $company . "\n\nKode OTP: [[otp]]";
        }

        $companyName = (string) ($config['CompanyName'] ?? '');
        $now = time();
        $otpExpirySeconds = isset($_c['otp_expiry']) ? (int) $_c['otp_expiry'] : 0;
        $otpWaitSeconds = isset($_c['otp_wait']) ? (int) $_c['otp_wait'] : 0;

        $otpExpiresAt = '';
        if ($otpExpirySeconds > 0) {
            $otpExpiresAt = Lang::dateTimeFormat(date('Y-m-d H:i:s', $now + $otpExpirySeconds));
        }
        $otpRequestAllowedAt = '';
        if ($otpWaitSeconds > 0) {
            $otpRequestAllowedAt = Lang::dateTimeFormat(date('Y-m-d H:i:s', $now + $otpWaitSeconds));
        }

        $rendered = strtr($template, [
            '[[company]]' => $companyName,
            '[[company_name]]' => $companyName,
            '[[otp]]' => $otp,
            '[[purpose]]' => $purpose,
            // OTP timing placeholders.
            '[[otp_expires_at]]' => $otpExpiresAt,
            '[[otp_expired_at]]' => $otpExpiresAt,
            '[[otp_request_allowed_at]]' => $otpRequestAllowedAt,
            '[[otp_request_at]]' => $otpRequestAllowedAt,
            '[[otp_expiry_seconds]]' => (string) max(0, $otpExpirySeconds),
            '[[otp_expiry]]' => (string) max(0, $otpExpirySeconds),
            '[[otp_wait_seconds]]' => (string) max(0, $otpWaitSeconds),
            '[[otp_wait]]' => (string) max(0, $otpWaitSeconds),
        ]);

        // Safety: if admin removes [[otp]] from template, still include the code.
        if ($otp !== '' && strpos($rendered, $otp) === false) {
            $rendered .= "\n\nKode OTP: " . $otp;
        }

        return $rendered;
    }

    public static function sendTelegram($txt, $chat_id = null, $topik = '')
    {
        global $config;
        run_hook('send_telegram', [$txt, $chat_id, $topik]); #HOOK
        if (!empty($config['telegram_bot'])) {
            if (empty($chat_id)) {
                $chat_id = $config['telegram_target_id'];
            }
            if (!empty($topik)) {
                $topik = "message_thread_id=$topik&";
            }
            return Http::getData('https://api.telegram.org/bot' . $config['telegram_bot'] . '/sendMessage?' . $topik . 'chat_id=' . $chat_id . '&text=' . urlencode($txt));
        }
    }


    public static function sendSMS($phone, $txt)
    {
        global $config;
        if (empty($txt)) {
            return "";
        }
        run_hook('send_sms', [$phone, $txt]); #HOOK
        if (!empty($config['sms_url'])) {
            if (strlen($config['sms_url']) > 4 && substr($config['sms_url'], 0, 4) != "http") {
                if (strlen($txt) > 160) {
                    $txts = str_split($txt, 160);
                    try {
                        foreach ($txts as $txt) {
                            self::sendSMS($phone, $txt);
                            self::logMessage('SMS', $phone, $txt, 'Success');
                        }
                    } catch (Throwable $e) {
                        // ignore, add to logs
                        self::logMessage('SMS', $phone, $txt, 'Error', $e->getMessage());
                    }
                } else {
                    try {
                        self::MikrotikSendSMS($config['sms_url'], $phone, $txt);
                        self::logMessage('MikroTikSMS', $phone, $txt, 'Success');
                    } catch (Throwable $e) {
                        // ignore, add to logs
                        self::logMessage('MikroTikSMS', $phone, $txt, 'Error', $e->getMessage());
                    }
                }
            } else {
                $smsurl = str_replace('[number]', urlencode($phone), $config['sms_url']);
                $smsurl = str_replace('[text]', urlencode($txt), $smsurl);
                try {
                    $response = Http::getData($smsurl);
                    self::logMessage('SMS HTTP Response', $phone, $txt, 'Success', $response);
                    return $response;
                } catch (Throwable $e) {
                    self::logMessage('SMS HTTP Request', $phone, $txt, 'Error', $e->getMessage());
                }
            }
        }
    }

    public static function MikrotikSendSMS($router_name, $to, $message)
    {
        global $_app_stage, $client_m, $config;
        if ($_app_stage == 'demo') {
            return null;
        }
        if (!isset($client_m)) {
            $mikrotik = ORM::for_table('tbl_routers')->where('name', $router_name)->find_one();
            $iport = explode(":", $mikrotik['ip_address']);
            $client_m = new RouterOS\Client($iport[0], $mikrotik['username'], $mikrotik['password'], ($iport[1]) ? $iport[1] : null);
        }
        if (empty($config['mikrotik_sms_command'])) {
            $config['mikrotik_sms_command'] = "/tool sms send";
        }
        $smsRequest = new RouterOS\Request($config['mikrotik_sms_command']);
        $smsRequest
            ->setArgument('phone-number', $to)
            ->setArgument('message', $message);
        $client_m->sendSync($smsRequest);
    }

    public static function sendWhatsapp($phone, $txt = '', $options = [])
    {
        global $config;
        $queueRequested = !empty($options['queue']);
        $skipQueue = !empty($options['skip_queue']);
        $queueContext = $options['queue_context'] ?? '';
        $wasInteractive = false; // indicates template/payload contained interactive parts and was downgraded to text
        $payloadOptions = $options;
        unset($payloadOptions['queue'], $payloadOptions['skip_queue'], $payloadOptions['queue_context']);
        $payload = null;
        $phoneForLog = $phone;
        $textForLog = $txt;

        $payloadFromText = false;

        if (is_array($phone)) {
            $payload = $phone;
            $phoneForLog = isset($payload['to']) ? $payload['to'] : '';
            $textForLog = isset($payload['text']) ? $payload['text'] : (isset($payload['body']) ? $payload['body'] : '');
        } elseif (is_array($txt)) {
            $payload = $txt;
            $phoneForLog = isset($payload['to']) ? $payload['to'] : $phone;
            $textForLog = isset($payload['text']) ? $payload['text'] : (isset($payload['body']) ? $payload['body'] : '');
        } elseif (is_string($txt)) {
            $parsedPayload = self::parseWhatsappPayloadFromText($txt);
            if (is_array($parsedPayload)) {
                $payload = $parsedPayload;
                $payloadFromText = true;
                if (isset($payload['to'])) {
                    $phoneForLog = $payload['to'];
                }
                $fallbackText = self::extractWhatsappTextFallback($payload);
                if ($fallbackText !== '') {
                    $textForLog = $fallbackText;
                }
            }
        }

        if (is_array($payload) && $textForLog === '') {
            $textForLog = self::extractWhatsappTextFallback($payload);
        }

        $method = strtolower(trim((string)($config['wa_gateway_method'] ?? '')));
        $hasGatewayConfig = !empty($config['wa_gateway_url']) || !empty($config['wa_gateway_secret']);
        $useGateway = ($method === 'post') || ($method === '' && $hasGatewayConfig);

        if ($payload === null && empty($txt) && (!$useGateway || empty($payloadOptions))) {
            return "kosong";
        }

        run_hook('send_whatsapp', [$phoneForLog, $textForLog]); // HOOK

        if ($queueRequested && !$skipQueue) {
            $queuePayload = self::prepareWhatsappQueuePayload($payload, $phone, $txt, $payloadOptions, $payloadFromText);
            if (empty($queuePayload)) {
                return "kosong";
            }
            $context = $queueContext !== '' ? $queueContext : 'whatsapp';
            self::enqueueWhatsappPayload($queuePayload, $phoneForLog, $textForLog, $context);
            return true;
        }

        if ($useGateway) {
            // If the message is interactive (e.g., template contains [[wa]] block), we try sending it as-is.
            // If gateway/device rejects interactive payload, we fallback to sending plain text (when possible).
            $interactiveFallbackText = '';
            $shouldFallbackToText = false;

            if ($payload === null) {
                $payload = $payloadOptions;
                if (!is_array($payload)) {
                    $payload = [];
                }
                if (!isset($payload['action'])) {
                    $payload['action'] = 'send';
                }
                if (!isset($payload['to'])) {
                    $payload['to'] = Lang::phoneFormat($phone);
                }
                if (
                    !isset($payload['text']) &&
                    !isset($payload['body']) &&
                    !isset($payload['message']) &&
                    !isset($payload['interactive']) &&
                    empty($payload['requestPhoneNumber'])
                ) {
                    $payload['text'] = $txt;
                } elseif (!isset($payload['text']) && !isset($payload['body']) && !empty($txt)) {
                    $payload['text'] = $txt;
                }
	            } else {
	                if (!isset($payload['action'])) {
	                    $payload['action'] = 'send';
	                }
	                if (!isset($payload['to']) && (!isset($payload['contacts']) || !is_array($payload['contacts']))) {
	                    $toCandidate = is_string($phone) ? $phone : (is_string($phoneForLog) ? $phoneForLog : '');
	                    if ($toCandidate !== '') {
	                        $payload['to'] = Lang::phoneFormat($toCandidate);
	                    }
	                }
	                if (
	                    $payloadFromText &&
	                    !isset($payload['text']) &&
                    !isset($payload['body']) &&
                    !isset($payload['message']) &&
                    !isset($payload['interactive']) &&
                    empty($payload['requestPhoneNumber'])
                ) {
                    $fallbackText = self::extractWhatsappTextFallback($payload);
                    if ($fallbackText !== '') {
                        $payload['text'] = $fallbackText;
                    }
                }
            }

            // Decide whether this payload is interactive/non-text and can be downgraded to plain text.
            if (is_array($payload)) {
                $action = strtolower(trim((string) ($payload['action'] ?? 'send')));
                $hasInteractive = isset($payload['interactive']) && is_array($payload['interactive']);
                $hasRawMessage = isset($payload['message']) && ($payload['message'] !== '' && $payload['message'] !== null);
                $hasRequestPhone = !empty($payload['requestPhoneNumber']);
                $hasContacts = isset($payload['contacts']) && is_array($payload['contacts']);

                if ($action === 'send' && !$hasContacts && ($hasInteractive || $hasRawMessage || $hasRequestPhone)) {
                    $interactiveFallbackText = trim((string) self::extractWhatsappTextFallback($payload));
                    if ($interactiveFallbackText !== '') {
                        $shouldFallbackToText = true;
                    }
                }
            }

            $gatewayErrorMeta = null;
            $result = self::sendWhatsappGatewayPayload(
                $payload,
                $phoneForLog,
                $textForLog,
                $gatewayErrorMeta,
                $shouldFallbackToText
            );
            if ($result !== false || !$shouldFallbackToText) {
                return $result;
            }

            // Interactive send failed; retry once with plain text fallback.
            $toCandidate = is_string($phone) ? $phone : (is_string($phoneForLog) ? $phoneForLog : '');
            $fallbackPayload = [
                'action' => 'send',
                'to' => isset($payload['to']) ? $payload['to'] : ($toCandidate !== '' ? Lang::phoneFormat($toCandidate) : ''),
                'text' => $interactiveFallbackText,
            ];
            if (isset($payload['thread_id'])) {
                $fallbackPayload['thread_id'] = $payload['thread_id'];
            }
            if (isset($payload['session_id'])) {
                $fallbackPayload['session_id'] = $payload['session_id'];
            }

            $fallbackResult = self::sendWhatsappGatewayPayload($fallbackPayload, $phoneForLog, $interactiveFallbackText);
            if ($fallbackResult !== false) {
                $errorMessage = '';
                if (is_array($gatewayErrorMeta)) {
                    $code = trim((string)($gatewayErrorMeta['code'] ?? ''));
                    $msg = trim((string)($gatewayErrorMeta['message'] ?? ''));
                    if ($msg !== '' && $code !== '') {
                        $errorMessage = '[' . $code . '] ' . $msg;
                    } elseif ($msg !== '') {
                        $errorMessage = $msg;
                    }
                }
                self::logMessage(
                    'WhatsApp Fallback',
                    $phoneForLog,
                    $interactiveFallbackText,
                    'Success',
                    $errorMessage !== ''
                        ? ('Interactive template failed, delivered as plain text fallback. ' . $errorMessage)
                        : 'Interactive template failed, delivered as plain text fallback.'
                );
                return $fallbackResult;
            }

            if (is_array($gatewayErrorMeta)) {
                $code = trim((string)($gatewayErrorMeta['code'] ?? ''));
                $msg = trim((string)($gatewayErrorMeta['message'] ?? ''));
                $payloadForLog = $gatewayErrorMeta['payload'] ?? '';
                $errorMessage = $msg !== '' ? $msg : 'Interactive send failed';
                if ($code !== '') {
                    $errorMessage = '[' . $code . '] ' . $errorMessage;
                }
                self::logMessage('WhatsApp Gateway Response', $phoneForLog, $textForLog, 'Error', $errorMessage);
                if (is_string($payloadForLog) && $payloadForLog !== '') {
                    self::logMessage('WhatsApp Gateway Payload', $phoneForLog, $payloadForLog, 'Error', 'Debug payload');
                }
            }
            return false;
        }

        if ($payload !== null) {
            $fallbackText = self::extractWhatsappTextFallback($payload);
            if ($fallbackText === '') {
                self::logMessage('WhatsApp HTTP Request', $phoneForLog, $textForLog, 'Error', 'Interactive payload requires POST gateway');
                return false;
            }
            $wasInteractive = is_array($payload) && (
                (isset($payload['interactive']) && is_array($payload['interactive'])) ||
                (isset($payload['message']) && ($payload['message'] !== '' && $payload['message'] !== null)) ||
                !empty($payload['requestPhoneNumber'])
            );
            $txt = $fallbackText;
            $textForLog = $fallbackText;
            $payload = null;
	        }

	        if (!empty($config['wa_url'])) {
	            $toCandidate = is_string($phone) ? $phone : (is_string($phoneForLog) ? $phoneForLog : '');
	            $waurl = str_replace('[number]', urlencode(Lang::phoneFormat($toCandidate)), $config['wa_url']);
	            $waurl = str_replace('[text]', urlencode($txt), $waurl);

            try {
                $response = Http::getData($waurl);
                $responseData = json_decode($response, true);
                if (is_array($responseData)) {
                    $ok = self::resolveGatewayResponseOk($responseData);
                    if ($ok === false) {
                        $errorMessage = $responseData['message'] ?? $responseData['error'] ?? $response;
                        if (is_array($errorMessage)) {
                            $errorMessage = json_encode($errorMessage);
                        }
                        self::logMessage('WhatsApp HTTP Response', $phone, $txt, 'Error', $errorMessage);
                        return false;
                    }
                    if ($ok === true) {
                        self::logMessage('WhatsApp HTTP Response', $phone, $txt, 'Success', $response);
                        if (!empty($wasInteractive)) {
                            self::logMessage(
                                'WhatsApp Fallback',
                                $phoneForLog,
                                $txt,
                                'Success',
                                'Interactive template downgraded to plain text because gateway method does not support interactive payloads.'
                            );
                        }
                        return $response;
                    }
                }
                if (
                    stripos($response, 'not registered') !== false ||
                    stripos($response, 'failed') !== false
                ) {
                    self::logMessage('WhatsApp HTTP Response', $phone, $txt, 'Error', $response);
                    return false;
                }
                self::logMessage('WhatsApp HTTP Response', $phone, $txt, 'Success', $response);
                if (!empty($wasInteractive)) {
                    self::logMessage(
                        'WhatsApp Fallback',
                        $phoneForLog,
                        $txt,
                        'Success',
                        'Interactive template downgraded to plain text because gateway method does not support interactive payloads.'
                    );
                }
                return $response;
            } catch (Throwable $e) {
                self::logMessage('WhatsApp HTTP Request', $phone, $txt, 'Error', $e->getMessage());
            }
        }
    }

    private static function sendWhatsappGatewayPayload($payload, $phoneForLog, $textForLog, &$errorMeta = null, $suppressErrorLog = false)
    {
        global $config;
        $payload = is_array($payload) ? $payload : [];
        $errorMeta = [];

        $gatewayUrl = isset($config['wa_gateway_url']) ? trim($config['wa_gateway_url']) : '';
        $gatewaySecret = isset($config['wa_gateway_secret']) ? trim($config['wa_gateway_secret']) : '';
        if ($gatewayUrl === '') {
            $errorMeta = [
                'code' => 'CONFIG',
                'message' => 'WhatsApp Gateway URL is empty',
            ];
            if (!$suppressErrorLog) {
                self::logMessage('WhatsApp Gateway', $phoneForLog, $textForLog, 'Error', $errorMeta['message']);
            }
            return false;
        }

        $endpoint = $gatewayUrl;
        if (strpos($endpoint, '{secret}') !== false) {
            if ($gatewaySecret === '') {
                $errorMeta = [
                    'code' => 'CONFIG',
                    'message' => 'WhatsApp Gateway secret is missing',
                ];
                if (!$suppressErrorLog) {
                    self::logMessage('WhatsApp Gateway', $phoneForLog, $textForLog, 'Error', $errorMeta['message']);
                }
                return false;
            }
            $endpoint = str_replace('{secret}', rawurlencode($gatewaySecret), $endpoint);
        } elseif (strpos($endpoint, ':secret') !== false) {
            if ($gatewaySecret === '') {
                $errorMeta = [
                    'code' => 'CONFIG',
                    'message' => 'WhatsApp Gateway secret is missing',
                ];
                if (!$suppressErrorLog) {
                    self::logMessage('WhatsApp Gateway', $phoneForLog, $textForLog, 'Error', $errorMeta['message']);
                }
                return false;
            }
            $endpoint = str_replace(':secret', rawurlencode($gatewaySecret), $endpoint);
        } elseif (strpos($endpoint, '[secret]') !== false) {
            if ($gatewaySecret === '') {
                $errorMeta = [
                    'code' => 'CONFIG',
                    'message' => 'WhatsApp Gateway secret is missing',
                ];
                if (!$suppressErrorLog) {
                    self::logMessage('WhatsApp Gateway', $phoneForLog, $textForLog, 'Error', $errorMeta['message']);
                }
                return false;
            }
            $endpoint = str_replace('[secret]', rawurlencode($gatewaySecret), $endpoint);
        } elseif (stripos($endpoint, '/ext/') === false && stripos($endpoint, '/wa') === false) {
            if ($gatewaySecret === '') {
                $errorMeta = [
                    'code' => 'CONFIG',
                    'message' => 'WhatsApp Gateway secret is missing',
                ];
                if (!$suppressErrorLog) {
                    self::logMessage('WhatsApp Gateway', $phoneForLog, $textForLog, 'Error', $errorMeta['message']);
                }
                return false;
            }
            $endpoint = rtrim($endpoint, '/') . '/ext/' . rawurlencode($gatewaySecret) . '/wa';
        }

        $payload = self::normalizeWhatsappGatewayPayload($payload);
        [$payload, $idempotencyKey] = self::ensureWhatsappIdempotencyKey($payload, 'wa');
        $payloadForLog = self::serializeWhatsappPayloadForLog($payload);
        $mediaUsage = self::registerWaTempMediaUsageFromPayload($payload, $phoneForLog);

        $headers = [];
        $basicAuth = null;
        $authType = isset($config['wa_gateway_auth_type']) ? strtolower(trim($config['wa_gateway_auth_type'])) : 'none';

        if ($authType === '') {
            $authType = 'none';
        }

        switch ($authType) {
            case 'basic':
                $user = trim((string)($config['wa_gateway_auth_username'] ?? ''));
                $pass = trim((string)($config['wa_gateway_auth_password'] ?? ''));
                if ($user === '' || $pass === '') {
                    $errorMeta = [
                        'code' => 'AUTH_CONFIG_INCOMPLETE',
                        'message' => 'Basic auth requires username and password',
                        'payload' => $payloadForLog,
                    ];
                    if (!$suppressErrorLog) {
                        self::logMessage('WhatsApp Gateway', $phoneForLog, $textForLog, 'Error', $errorMeta['message']);
                    }
                    return false;
                }
                $basicAuth = $user . ':' . $pass;
                break;
            case 'header':
                $headerName = trim((string)($config['wa_gateway_auth_header_name'] ?? ''));
                $token = trim((string)($config['wa_gateway_auth_token'] ?? ''));
                if ($headerName === '' || $token === '') {
                    $errorMeta = [
                        'code' => 'AUTH_CONFIG_INCOMPLETE',
                        'message' => 'Header auth requires header name and token',
                        'payload' => $payloadForLog,
                    ];
                    if (!$suppressErrorLog) {
                        self::logMessage('WhatsApp Gateway', $phoneForLog, $textForLog, 'Error', $errorMeta['message']);
                    }
                    return false;
                }
                $headers[] = $headerName . ': ' . $token;
                break;
            case 'jwt':
                $token = trim((string)($config['wa_gateway_auth_token'] ?? ''));
                if ($token === '') {
                    $errorMeta = [
                        'code' => 'AUTH_CONFIG_INCOMPLETE',
                        'message' => 'JWT auth requires token',
                        'payload' => $payloadForLog,
                    ];
                    if (!$suppressErrorLog) {
                        self::logMessage('WhatsApp Gateway', $phoneForLog, $textForLog, 'Error', $errorMeta['message']);
                    }
                    return false;
                }
                $headers[] = 'Authorization: Bearer ' . $token;
                break;
            case 'none':
            default:
                break;
        }
        if ($idempotencyKey !== '') {
            $hasIdempotencyHeader = false;
            foreach ($headers as $headerLine) {
                if (stripos($headerLine, 'Idempotency-Key:') === 0) {
                    $hasIdempotencyHeader = true;
                    break;
                }
            }
            if (!$hasIdempotencyHeader) {
                $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
            }
        }

        try {
            $response = Http::postJsonData($endpoint, $payload, $headers, $basicAuth);
            $responseData = json_decode($response, true);
            if (is_array($responseData)) {
                $ok = self::resolveGatewayResponseOk($responseData);
                if ($ok === false) {
                    $errorCode = $responseData['code'] ?? null;
                    $errorMessage = $responseData['message'] ?? $responseData['error'] ?? $response;
                    if (is_array($errorMessage)) {
                        $errorMessage = json_encode($errorMessage);
                    }
                    $errorMeta = [
                        'code' => (string) ($errorCode ?? ''),
                        'message' => (string) $errorMessage,
                        'response' => $response,
                        'payload' => $payloadForLog,
                    ];
                    if (!$suppressErrorLog) {
                        self::logMessage('WhatsApp Gateway Response', $phoneForLog, $textForLog, 'Error', $errorMessage);
                        self::logMessage('WhatsApp Gateway Payload', $phoneForLog, $payloadForLog, 'Error', 'Debug payload');
                    }
                    self::updateWaTempMediaUsage($mediaUsage, false, $phoneForLog);
                    self::cleanupExpiredWhatsappMedia();
                    if ($errorCode === 'NUMBER_NOT_REGISTERED') {
                        return false;
                    }
                    return false;
                }
                self::logMessage('WhatsApp Gateway Response', $phoneForLog, $textForLog, 'Success', $response);
                self::updateWaTempMediaUsage($mediaUsage, true, $phoneForLog);
                self::cleanupExpiredWhatsappMedia();
                return $response;
            }

            if (
                stripos($response, 'not registered') !== false ||
                stripos($response, 'failed') !== false
            ) {
                $errorMeta = [
                    'code' => '',
                    'message' => (string) $response,
                    'response' => $response,
                    'payload' => $payloadForLog,
                ];
                if (!$suppressErrorLog) {
                    self::logMessage('WhatsApp Gateway Response', $phoneForLog, $textForLog, 'Error', $response);
                    self::logMessage('WhatsApp Gateway Payload', $phoneForLog, $payloadForLog, 'Error', 'Debug payload');
                }
                self::updateWaTempMediaUsage($mediaUsage, false, $phoneForLog);
                self::cleanupExpiredWhatsappMedia();
                return false;
            }
            self::logMessage('WhatsApp Gateway Response', $phoneForLog, $textForLog, 'Success', $response);
            self::updateWaTempMediaUsage($mediaUsage, true, $phoneForLog);
            self::cleanupExpiredWhatsappMedia();
            return $response;
        } catch (Throwable $e) {
            $errorMeta = [
                'code' => 'EXCEPTION',
                'message' => $e->getMessage(),
                'payload' => $payloadForLog,
            ];
            if (!$suppressErrorLog) {
                self::logMessage('WhatsApp Gateway Request', $phoneForLog, $textForLog, 'Error', $e->getMessage());
                self::logMessage('WhatsApp Gateway Payload', $phoneForLog, $payloadForLog, 'Error', 'Debug payload');
            }
            self::updateWaTempMediaUsage($mediaUsage, false, $phoneForLog);
            self::cleanupExpiredWhatsappMedia();
        }

        return false;
    }

    private static function normalizeWhatsappGatewayPayload($payload)
    {
        if (!is_array($payload)) {
            return [];
        }

        if (isset($payload['threadId']) && !isset($payload['thread_id'])) {
            $payload['thread_id'] = $payload['threadId'];
        }
        if (isset($payload['sessionId']) && !isset($payload['session_id'])) {
            $payload['session_id'] = $payload['sessionId'];
        }
        if (isset($payload['autoRetry']) && !isset($payload['auto_retry'])) {
            $payload['auto_retry'] = $payload['autoRetry'];
        }
        if (isset($payload['messageSendAs']) && !isset($payload['message_send_as'])) {
            $payload['message_send_as'] = $payload['messageSendAs'];
        }
        if (isset($payload['request_phone_number']) && !isset($payload['requestPhoneNumber'])) {
            $payload['requestPhoneNumber'] = $payload['request_phone_number'];
        }
        if (isset($payload['allow_empty_text']) && !isset($payload['allowEmptyText'])) {
            $payload['allowEmptyText'] = $payload['allow_empty_text'];
        }
        foreach (['threadId', 'sessionId', 'autoRetry', 'messageSendAs', 'request_phone_number', 'allow_empty_text'] as $legacyKey) {
            if (isset($payload[$legacyKey])) {
                unset($payload[$legacyKey]);
            }
        }

        if (isset($payload['action'])) {
            $payload['action'] = strtolower(trim((string)$payload['action']));
        } elseif (isset($payload['contacts'])) {
            $payload['action'] = 'upsert';
        } else {
            $payload['action'] = 'send';
        }

        if (isset($payload['to']) && is_string($payload['to'])) {
            $payload['to'] = Lang::phoneFormat($payload['to']);
        }

        if (!empty($payload['contacts']) && is_array($payload['contacts'])) {
            foreach ($payload['contacts'] as $index => $contact) {
                if (!is_array($contact)) {
                    continue;
                }
                foreach (['phone', 'number'] as $key) {
                    if (isset($contact[$key]) && is_string($contact[$key]) && Validator::UnsignedNumber($contact[$key])) {
                        $payload['contacts'][$index][$key] = Lang::phoneFormat($contact[$key]);
                    }
                }
            }
        }

        if (isset($payload['interactive']) && is_array($payload['interactive'])) {
            $payload['interactive'] = self::sanitizeWhatsappInteractive($payload['interactive']);
            if (empty($payload['interactive'])) {
                unset($payload['interactive']);
            }
        }

        return $payload;
    }

    private static function ensureWhatsappIdempotencyKey($payload, $context = '')
    {
        if (!is_array($payload)) {
            return [[], ''];
        }

        $key = '';
        if (isset($payload['idempotency_key']) && is_scalar($payload['idempotency_key'])) {
            $key = trim((string)$payload['idempotency_key']);
        }
        if ($key === '' && isset($payload['idempotencyKey']) && is_scalar($payload['idempotencyKey'])) {
            $key = trim((string)$payload['idempotencyKey']);
        }
        if ($key === '') {
            $key = self::generateWhatsappIdempotencyKey($context);
        }
        if ($key !== '' && strlen($key) > 128) {
            $key = substr($key, 0, 128);
        }

        $payload['idempotency_key'] = $key;
        if (isset($payload['idempotencyKey'])) {
            unset($payload['idempotencyKey']);
        }

        return [$payload, $key];
    }

    private static function generateWhatsappIdempotencyKey($context = '')
    {
        $prefix = trim((string)$context);
        if ($prefix !== '') {
            $prefix = preg_replace('/[^A-Za-z0-9_.-]/', '', $prefix);
        }

        if (function_exists('random_bytes')) {
            $random = bin2hex(random_bytes(16));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $random = bin2hex(openssl_random_pseudo_bytes(16));
        } else {
            $random = md5(uniqid((string)mt_rand(), true));
        }

        if ($prefix !== '') {
            return $prefix . '-' . $random;
        }
        return $random;
    }

    private static function prepareWhatsappQueuePayload($payload, $phone, $txt, array $payloadOptions, $payloadFromText)
    {
        if ($payload === null) {
            $payload = $payloadOptions;
            if (!is_array($payload)) {
                $payload = [];
            }
            if (!isset($payload['action'])) {
                $payload['action'] = 'send';
            }
            if (!isset($payload['to'])) {
                $payload['to'] = Lang::phoneFormat($phone);
            }
            if (
                !isset($payload['text']) &&
                !isset($payload['body']) &&
                !isset($payload['message']) &&
                !isset($payload['interactive']) &&
                empty($payload['requestPhoneNumber'])
            ) {
                $payload['text'] = $txt;
            } elseif (!isset($payload['text']) && !isset($payload['body']) && !empty($txt)) {
                $payload['text'] = $txt;
            }
        } else {
            if (!isset($payload['action'])) {
                $payload['action'] = 'send';
            }
            if (!isset($payload['to']) && (!isset($payload['contacts']) || !is_array($payload['contacts']))) {
                $payload['to'] = Lang::phoneFormat($phone);
            }
            if (
                $payloadFromText &&
                !isset($payload['text']) &&
                !isset($payload['body']) &&
                !isset($payload['message']) &&
                !isset($payload['interactive']) &&
                empty($payload['requestPhoneNumber'])
            ) {
                $fallbackText = self::extractWhatsappTextFallback($payload);
                if ($fallbackText !== '') {
                    $payload['text'] = $fallbackText;
                }
            }
        }

        return is_array($payload) ? $payload : [];
    }

    private static function ensureWhatsappQueueTable()
    {
        $db = ORM::get_db();
        $db->exec("CREATE TABLE IF NOT EXISTS tbl_wa_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            recipient VARCHAR(255) NOT NULL,
            message_content TEXT NULL,
            payload LONGTEXT NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            max_retries INT NOT NULL DEFAULT 3,
            retry_interval INT NOT NULL DEFAULT 60,
            next_retry_at DATETIME NOT NULL,
            last_error TEXT NULL,
            context VARCHAR(50) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            sent_at DATETIME NULL,
            KEY idx_wa_queue_status_next (status, next_retry_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    }

    private static function getWhatsappQueueSettings()
    {
        global $config;
        $maxRetries = isset($config['wa_queue_max_retries']) ? (int) $config['wa_queue_max_retries'] : 3;
        $retryInterval = isset($config['wa_queue_retry_interval']) ? (int) $config['wa_queue_retry_interval'] : 60;
        if ($maxRetries < 1) {
            $maxRetries = 1;
        }
        if ($retryInterval < 10) {
            $retryInterval = 10;
        }
        return [$maxRetries, $retryInterval];
    }

    public static function isWhatsappQueueEnabledForNotifications()
    {
        global $_notifmsg;
        $flag = $_notifmsg['wa_queue_enabled'] ?? '';
        if (is_bool($flag)) {
            return $flag;
        }
        $flag = strtolower(trim((string)$flag));
        return in_array($flag, ['1', 'yes', 'true', 'on'], true);
    }

    public static function isWhatsappQueueEnabledForNotificationTemplate($templateKey = '')
    {
        global $_notifmsg;
        if (!self::isWhatsappQueueEnabledForNotifications()) {
            return false;
        }
        $templateKey = trim((string)$templateKey);
        if ($templateKey === '') {
            return true;
        }
        $key = 'wa_queue_' . $templateKey;
        if (!array_key_exists($key, $_notifmsg)) {
            return true;
        }
        $flag = $_notifmsg[$key];
        $normalized = self::normalizeBooleanValue($flag);
        if ($normalized === null) {
            return true;
        }
        return $normalized;
    }

    private static function enqueueWhatsappPayload(array $payload, $recipient, $messageContent, $context = 'whatsapp')
    {
        self::ensureWhatsappQueueTable();
        [$maxRetries, $retryInterval] = self::getWhatsappQueueSettings();
        $now = date('Y-m-d H:i:s');
        $nextRetryAt = $now;
        [$payload, $idempotencyKey] = self::ensureWhatsappIdempotencyKey($payload, $context);

        $queue = ORM::for_table('tbl_wa_queue')->create();
        $queue->status = 'pending';
        $queue->recipient = self::sanitizeForLog($recipient);
        $queue->message_content = self::sanitizeForLog($messageContent);
        $queue->payload = json_encode($payload);
        $queue->attempts = 0;
        $queue->max_retries = $maxRetries;
        $queue->retry_interval = $retryInterval;
        $queue->next_retry_at = $nextRetryAt;
        $queue->context = $context;
        $queue->created_at = $now;
        $queue->updated_at = $now;
        $queue->save();
        return $queue->id();
    }

    public static function processWhatsappQueue($limit = 20)
    {
        self::ensureWhatsappQueueTable();
        $now = date('Y-m-d H:i:s');
        $items = ORM::for_table('tbl_wa_queue')
            ->where_in('status', ['pending', 'retry'])
            ->where_lte('next_retry_at', $now)
            ->order_by_asc('next_retry_at')
            ->limit($limit)
            ->find_many();

        foreach ($items as $item) {
            $maxRetries = (int) $item->max_retries;
            $retryInterval = (int) $item->retry_interval;
            if ($maxRetries < 1) {
                $maxRetries = 1;
            }
            if ($retryInterval < 10) {
                $retryInterval = 10;
            }
            if ((int) $item->attempts >= $maxRetries) {
                $item->status = 'failed';
                $item->last_error = $item->last_error ?: 'Max retries exceeded';
                $item->updated_at = $now;
                $item->save();
                continue;
            }

            $item->status = 'processing';
            $item->updated_at = $now;
            $item->save();

            $payload = json_decode($item->payload, true);
            if (!is_array($payload)) {
                $payload = [];
            }
            $result = self::sendWhatsapp($payload, '', ['skip_queue' => true]);
            if ($result === false || $result === 'kosong') {
                $item->attempts = (int) $item->attempts + 1;
                if ($item->attempts >= $maxRetries) {
                    $item->status = 'failed';
                } else {
                    $item->status = 'retry';
                    $item->next_retry_at = date('Y-m-d H:i:s', time() + $retryInterval);
                }
                $item->last_error = is_string($result) ? $result : 'Send failed';
                $item->updated_at = date('Y-m-d H:i:s');
                $item->save();
                continue;
            }

            $item->status = 'sent';
            $item->sent_at = date('Y-m-d H:i:s');
            $item->updated_at = $item->sent_at;
            $item->save();
        }
    }

    private static function resolveGatewayResponseOk(array $responseData)
    {
        $okRaw = $responseData['ok'] ?? $responseData['success'] ?? null;
        $ok = self::normalizeBooleanValue($okRaw);
        if ($ok !== null) {
            return $ok;
        }

        $statusRaw = $responseData['status'] ?? $responseData['result'] ?? null;
        if (is_string($statusRaw)) {
            $status = strtolower(trim($statusRaw));
            if (in_array($status, ['ok', 'success', 'sent', 'delivered'], true)) {
                return true;
            }
            if (in_array($status, ['error', 'failed', 'fail', 'invalid'], true)) {
                return false;
            }
        }

        if (isset($responseData['error']) || isset($responseData['errors']) || isset($responseData['code'])) {
            return false;
        }

        return null;
    }

    private static function normalizeBooleanValue($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }
        if (is_float($value)) {
            return (int)$value === 1;
        }
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if ($normalized === '') {
                return null;
            }
            if (in_array($normalized, ['true', '1', 'yes', 'ok', 'success'], true)) {
                return true;
            }
            if (in_array($normalized, ['false', '0', 'no', 'error', 'failed', 'fail', 'invalid'], true)) {
                return false;
            }
        }
        return null;
    }

    private static function sanitizeWhatsappInteractive($interactive)
    {
        if (!is_array($interactive)) {
            return null;
        }

        if (isset($interactive['type'])) {
            $interactive['type'] = strtolower(trim((string)$interactive['type']));
        }

        $interactiveType = $interactive['type'] ?? '';

        if ($interactiveType === 'native_flow') {
            if (isset($interactive['buttons']) && is_array($interactive['buttons'])) {
                $buttons = [];
                foreach ($interactive['buttons'] as $button) {
                    if (!is_array($button)) {
                        continue;
                    }
                    $name = trim((string) ($button['name'] ?? ''));
                    $params = $button['buttonParamsJson'] ?? '';
                    if ($name === '' || $params === '') {
                        continue;
                    }
                    if (is_array($params)) {
                        $params = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    }
                    $params = trim((string) $params);
                    if ($params === '') {
                        continue;
                    }
                    $buttons[] = [
                        'name' => $name,
                        'buttonParamsJson' => $params,
                    ];
                }
                $interactive['buttons'] = $buttons;
            }

            if (empty($interactive['buttons'])) {
                return null;
            }

            return $interactive;
        }

        if (isset($interactive['buttons']) && is_array($interactive['buttons'])) {
            $buttons = [];
            foreach ($interactive['buttons'] as $button) {
                if (!is_array($button)) {
                    continue;
                }
                $buttonType = strtolower(trim((string)($button['type'] ?? '')));
                if ($buttonType !== '') {
                    $button['type'] = $buttonType;
                }

                if ($buttonType === 'url') {
                    $url = trim((string)($button['url'] ?? ''));
                    if ($url === '' || !preg_match('/^https?:\\/\\//i', $url)) {
                        continue;
                    }
                    $button['url'] = $url;
                } elseif ($buttonType === 'call') {
                    $phone = trim((string)($button['phoneNumber'] ?? ''));
                    if ($phone === '') {
                        continue;
                    }
                    $button['phoneNumber'] = $phone;
                } elseif ($buttonType === 'quick') {
                    $text = trim((string)($button['text'] ?? ($button['title'] ?? '')));
                    $id = trim((string)($button['id'] ?? ''));
                    if ($text === '' && $id === '') {
                        continue;
                    }
                    if ($text === '' && $id !== '') {
                        $button['text'] = $id;
                    } elseif ($text !== '' && empty($button['text'])) {
                        $button['text'] = $text;
                    }
                } else {
                    $text = trim((string)($button['text'] ?? ($button['title'] ?? '')));
                    $id = trim((string)($button['id'] ?? ''));
                    if ($text === '' && $id === '') {
                        continue;
                    }
                    if ($text === '' && $id !== '') {
                        $button['text'] = $id;
                    } elseif ($text !== '' && empty($button['text'])) {
                        $button['text'] = $text;
                    }
                }

                $buttons[] = $button;
            }
            $interactive['buttons'] = $buttons;
        }

        if (isset($interactive['type']) && $interactive['type'] === 'list') {
            if (isset($interactive['sections']) && is_array($interactive['sections'])) {
                $sections = [];
                foreach ($interactive['sections'] as $section) {
                    if (!is_array($section)) {
                        continue;
                    }
                    $rows = [];
                    if (isset($section['rows']) && is_array($section['rows'])) {
                        foreach ($section['rows'] as $row) {
                            if (!is_array($row)) {
                                continue;
                            }
                            $id = trim((string)($row['id'] ?? ''));
                            $title = trim((string)($row['title'] ?? ''));
                            if ($id === '' && $title === '') {
                                continue;
                            }
                            if ($id === '' && $title !== '') {
                                $id = $title;
                            }
                            $clean = [
                                'id' => $id,
                                'title' => $title !== '' ? $title : $id
                            ];
                            if (!empty($row['description'])) {
                                $clean['description'] = $row['description'];
                            }
                            $rows[] = $clean;
                        }
                    }
                    if (!empty($rows)) {
                        $section['rows'] = $rows;
                        $sections[] = $section;
                    }
                }
                $interactive['sections'] = $sections;
            }
        }

        if ($interactiveType === 'list') {
            if (empty($interactive['sections'])) {
                return null;
            }
        } else {
            if (empty($interactive['buttons'])) {
                return null;
            }
        }

        return $interactive;
    }

    private static function serializeWhatsappPayloadForLog($payload)
    {
        if (!is_array($payload)) {
            return '';
        }
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            $encoded = print_r($payload, true);
        }
        $maxLen = 4000;
        if (is_string($encoded) && strlen($encoded) > $maxLen) {
            $encoded = substr($encoded, 0, $maxLen) . '...';
        }
        return $encoded;
    }

    private static function ensureWaTempMediaTables()
    {
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
                CREATE TABLE IF NOT EXISTS tbl_wa_media_usage (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    media_id VARCHAR(64) NOT NULL,
                    recipient VARCHAR(100) NULL,
                    status ENUM('pending','success','failed') DEFAULT 'pending',
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NULL,
                    INDEX media_id_idx (media_id),
                    INDEX recipient_idx (recipient)
                );
            ");
        } catch (Throwable $e) {
            // ignore table creation errors
        }
    }

    private static function extractWaTempMediaIdsFromPayload($payload)
    {
        if (!is_array($payload)) {
            return [];
        }
        $ids = [];
        $url = '';
        if (isset($payload['interactive']['headerMedia']['url'])) {
            $url = (string) $payload['interactive']['headerMedia']['url'];
        }
        if ($url !== '') {
            $path = parse_url($url, PHP_URL_PATH) ?: $url;
            if (preg_match('~/system/uploads/wa_tmp/([^/]+)/~', $path, $m)) {
                $ids[$m[1]] = true;
            }
        }
        return array_keys($ids);
    }

    private static function registerWaTempMediaUsageFromPayload($payload, $recipient)
    {
        $mediaIds = self::extractWaTempMediaIdsFromPayload($payload);
        if (empty($mediaIds)) {
            return [];
        }
        self::ensureWaTempMediaTables();
        $usage = [];
        $now = date('Y-m-d H:i:s');
        foreach ($mediaIds as $mediaId) {
            $media = ORM::for_table('tbl_wa_media_tmp')->where('media_id', $mediaId)->find_one();
            if (!$media || $media['status'] !== 'active') {
                continue;
            }
            $media->last_used_at = $now;
            $media->save();
            $u = ORM::for_table('tbl_wa_media_usage')->create();
            $u->media_id = $mediaId;
            $u->recipient = $recipient;
            $u->status = 'pending';
            $u->created_at = $now;
            $u->save();
            $usage[$mediaId] = $u->id();
        }
        return $usage;
    }

    private static function updateWaTempMediaUsage($usage, $success, $recipient)
    {
        if (empty($usage)) {
            return;
        }
        self::ensureWaTempMediaTables();
        $now = date('Y-m-d H:i:s');
        foreach ($usage as $mediaId => $usageId) {
            $row = ORM::for_table('tbl_wa_media_usage')->find_one($usageId);
            if ($row) {
                $row->status = $success ? 'success' : 'failed';
                $row->updated_at = $now;
                $row->save();
            }
            if ($success && $recipient !== '') {
                $others = ORM::for_table('tbl_wa_media_usage')
                    ->where('media_id', $mediaId)
                    ->where('recipient', $recipient)
                    ->where_not_equal('status', 'success')
                    ->find_many();
                foreach ($others as $other) {
                    $other->status = 'success';
                    $other->updated_at = $now;
                    $other->save();
                }
            }
            self::cleanupWaTempMediaIfDone($mediaId);
        }
    }

    private static function cleanupWaTempMediaIfDone($mediaId)
    {
        self::ensureWaTempMediaTables();
        $pending = ORM::for_table('tbl_wa_media_usage')
            ->where('media_id', $mediaId)
            ->where_not_equal('status', 'success')
            ->count();
        if ($pending > 0) {
            return;
        }
        $media = ORM::for_table('tbl_wa_media_tmp')->where('media_id', $mediaId)->find_one();
        if (!$media || $media['status'] !== 'active') {
            return;
        }
        self::deleteWaTempMediaFile($media['file_path']);
        $media->status = 'cleaned';
        $media->save();
    }

    public static function cleanupExpiredWhatsappMedia()
    {
        self::ensureWaTempMediaTables();
        $now = date('Y-m-d H:i:s');
        $expired = ORM::for_table('tbl_wa_media_tmp')
            ->where_lt('expires_at', $now)
            ->where_not_equal('status', 'cleaned')
            ->find_many();
        foreach ($expired as $media) {
            self::deleteWaTempMediaFile($media['file_path']);
            $media->status = 'expired';
            $media->save();
        }
    }

    private static function deleteWaTempMediaFile($filePath)
    {
        if (!is_string($filePath) || $filePath === '') {
            return;
        }
        $path = $filePath;
        if (is_file($path)) {
            @unlink($path);
        }
        $dir = dirname($path);
        if (is_dir($dir)) {
            @rmdir($dir);
        }
    }

    private static function parseWhatsappPayloadFromText($text)
    {
        if (!is_string($text)) {
            return null;
        }
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }
        $firstChar = $trimmed[0];
        if ($firstChar !== '{' && $firstChar !== '[') {
            $blockPayload = self::parseWhatsappTemplateBlock($trimmed);
            if (is_array($blockPayload)) {
                return $blockPayload;
            }
            return null;
        }
        $decoded = json_decode($trimmed, true);
        if (!is_array($decoded)) {
            $blockPayload = self::parseWhatsappTemplateBlock($trimmed);
            if (is_array($blockPayload)) {
                return $blockPayload;
            }
            return null;
        }
        if (self::isAssocArray($decoded)) {
            return self::looksLikeWhatsappPayload($decoded) ? $decoded : null;
        }
        if (count($decoded) > 0 && self::looksLikeWhatsappContactList($decoded)) {
            return ['contacts' => $decoded];
        }
        return null;
    }

    private static function looksLikeWhatsappPayload($payload)
    {
        $keys = [
            'action', 'to', 'text', 'body', 'message', 'interactive',
            'requestPhoneNumber', 'contacts', 'thread_id', 'session_id',
            'threadId', 'sessionId'
        ];
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return true;
            }
        }
        return false;
    }

    private static function looksLikeWhatsappContactList($list)
    {
        foreach ($list as $item) {
            if (!is_array($item)) {
                return false;
            }
            foreach (['phone', 'number', 'handle', 'to', 'id'] as $key) {
                if (isset($item[$key])) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function isAssocArray($array)
    {
        if (!is_array($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private static function extractWhatsappTextFallback($payload)
    {
        if (!is_array($payload)) {
            return '';
        }
        if (isset($payload['text']) && is_string($payload['text']) && $payload['text'] !== '') {
            return $payload['text'];
        }
        if (isset($payload['body']) && is_string($payload['body']) && $payload['body'] !== '') {
            return $payload['body'];
        }
        if (isset($payload['message'])) {
            if (is_string($payload['message']) && $payload['message'] !== '') {
                return $payload['message'];
            }
            if (is_array($payload['message']) && isset($payload['message']['conversation']) && is_string($payload['message']['conversation'])) {
                return $payload['message']['conversation'];
            }
        }
        if (isset($payload['interactive']) && is_array($payload['interactive'])) {
            if (isset($payload['interactive']['text']) && is_string($payload['interactive']['text']) && $payload['interactive']['text'] !== '') {
                return $payload['interactive']['text'];
            }
            if (isset($payload['interactive']['body']) && is_string($payload['interactive']['body']) && $payload['interactive']['body'] !== '') {
                return $payload['interactive']['body'];
            }
        }
        return '';
    }

    private static function parseWhatsappTemplateBlock($text)
    {
        if (!is_string($text)) {
            return null;
        }

        $matches = [];
        if (!preg_match('/\\[\\[wa\\]\\](.*?)\\[\\[\\/wa\\]\\]/is', $text, $matches)) {
            return null;
        }

        $block = trim($matches[1]);
        if ($block === '') {
            return null;
        }

        $outsideText = trim(preg_replace('/\\[\\[wa\\]\\].*?\\[\\[\\/wa\\]\\]/is', '', $text));

        $lines = preg_split('/\\r?\\n/', $block);
        $payload = [
            'action' => 'send'
        ];
        $interactive = [];
        $sections = [];
        $currentSectionIndex = null;
        $textLines = [];
        $inlineText = '';
        $requestPhoneNumber = false;
        $templateButtonsUsed = false;

        $textStarted = false;
        $multilineKey = null;
        $multilineBuffer = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($multilineKey !== null) {
                $lineContent = $line;
                $isEnd = false;
                if (preg_match('/\\)\\s*$/', $lineContent)) {
                    $isEnd = true;
                    $lineContent = preg_replace('/\\)\\s*$/', '', $lineContent);
                }
                $multilineBuffer[] = $lineContent;
                if (!$isEnd) {
                    continue;
                }
                $key = $multilineKey;
                $value = implode("\n", $multilineBuffer);
                $multilineKey = null;
                $multilineBuffer = [];
            } else {
                if ($line === '') {
                    if ($textStarted) {
                        $textLines[] = '';
                    }
                    continue;
                }
                if (strpos($line, '#') === 0) {
                    continue;
                }

                $key = null;
                $value = null;

                if (preg_match('/^\\[([A-Za-z0-9_]+)\\]\\s*\\((.*)\\)\\s*$/', $line, $matchesLine)) {
                    $key = strtolower($matchesLine[1]);
                    $value = trim($matchesLine[2]);
                } elseif (preg_match('/^\\[([A-Za-z0-9_]+)\\]\\s*\\((.*)$/', $line, $matchesLine)) {
                    $maybeKey = strtolower($matchesLine[1]);
                    if (in_array($maybeKey, ['text', 'body'], true)) {
                        $multilineKey = $maybeKey;
                        $multilineBuffer = [trim($matchesLine[2])];
                        continue;
                    }
                } elseif (preg_match('/^([A-Za-z0-9_]+)([:=])(.*)$/', $line, $matchesLine)) {
                    $key = strtolower($matchesLine[1]);
                    $value = trim($matchesLine[3]);
                } else {
                    $textLines[] = $line;
                    $textStarted = true;
                    continue;
                }
            }

            if ($key === 'type') {
                $typeValue = strtolower($value);
                if (!in_array($typeValue, ['buttons', 'list', 'template', 'native_flow'], true)) {
                    $textLines[] = $line;
                    $textStarted = true;
                    continue;
                }
                $interactive['type'] = $typeValue;
                continue;
            }

            if ($key === 'text' || $key === 'body') {
                if ($value !== '') {
                    $textLines[] = $value;
                    $textStarted = true;
                }
                continue;
            }

            if ($key === 'title') {
                $interactive['title'] = $value;
                continue;
            }

            if ($key === 'buttontext' || $key === 'button_text') {
                $interactive['buttonText'] = $value;
                continue;
            }

            if ($key === 'footer') {
                $interactive['footer'] = $value;
                continue;
            }

            if ($key === 'headertext') {
                $interactive['headerText'] = $value;
                continue;
            }

            if ($key === 'headertype') {
                $interactive['headerType'] = (int)$value;
                continue;
            }

            if ($key === 'headermedia') {
                $interactive['headerMedia'] = [
                    'type' => 'image',
                    'url' => $value
                ];
                continue;
            }

            if ($key === 'section') {
                $sections[] = [
                    'title' => $value,
                    'rows' => []
                ];
                $currentSectionIndex = count($sections) - 1;
                continue;
            }

            if ($key === 'row') {
                $parts = array_map('trim', explode('|', $value));
                if ($currentSectionIndex === null) {
                    $sections[] = [
                        'title' => 'Menu',
                        'rows' => []
                    ];
                    $currentSectionIndex = count($sections) - 1;
                }
                $row = [
                    'id' => $parts[0] ?? '',
                    'title' => $parts[1] ?? ''
                ];
                if (!empty($parts[2])) {
                    $row['description'] = $parts[2];
                }
                $sections[$currentSectionIndex]['rows'][] = $row;
                continue;
            }

            if ($key === 'button') {
                $interactiveType = strtolower($interactive['type'] ?? '');
                $parts = array_map('trim', explode('|', $value));
                if ($interactiveType === 'native_flow') {
                    $interactive['buttons'][] = [
                        'name' => $parts[0] ?? '',
                        'buttonParamsJson' => $parts[1] ?? ''
                    ];
                } elseif ($interactiveType === 'template' || in_array(strtolower($parts[0] ?? ''), ['quick', 'url', 'call', 'copy'], true)) {
                    if ($interactiveType === '') {
                        $interactive['type'] = 'template';
                    }
                    $btnType = strtolower($parts[0] ?? '');
                    if ($btnType === 'quick') {
                        $interactive['buttons'][] = [
                            'type' => 'quick',
                            'id' => $parts[1] ?? '',
                            'text' => $parts[2] ?? ($parts[1] ?? '')
                        ];
                    } elseif ($btnType === 'url') {
                        $interactive['buttons'][] = [
                            'type' => 'url',
                            'text' => $parts[1] ?? '',
                            'url' => $parts[2] ?? ''
                        ];
                    } elseif ($btnType === 'call') {
                        $interactive['buttons'][] = [
                            'type' => 'call',
                            'text' => $parts[1] ?? '',
                            'phoneNumber' => $parts[2] ?? ''
                        ];
                    } elseif ($btnType === 'copy') {
                        $interactive['buttons'][] = [
                            'type' => 'copy',
                            'text' => $parts[1] ?? '',
                            'copyCode' => $parts[2] ?? ''
                        ];
                    }
                    $templateButtonsUsed = true;
                } else {
                    $interactive['buttons'][] = [
                        'id' => $parts[0] ?? '',
                        'text' => $parts[1] ?? ($parts[0] ?? '')
                    ];
                }
                continue;
            }

            if ($key === 'requestphonenumber') {
                $requestPhoneNumber = in_array(strtolower($value), ['1', 'true', 'yes'], true);
                continue;
            }

            if ($key === 'allowemptytext') {
                $payload['allowEmptyText'] = in_array(strtolower($value), ['1', 'true', 'yes'], true);
                continue;
            }

            if ($key === 'thread_id' || $key === 'threadid') {
                $payload['thread_id'] = $value;
                continue;
            }

            if ($key === 'session_id' || $key === 'sessionid') {
                $payload['session_id'] = $value;
                continue;
            }

            if ($key === 'to') {
                $payload['to'] = $value;
                continue;
            }

            $textLines[] = $line;
            $textStarted = true;
        }

        if (!empty($textLines)) {
            $inlineText = implode("\n", $textLines);
        }

        if ($requestPhoneNumber) {
            $payload['requestPhoneNumber'] = true;
        }

        if (!empty($interactive) || !empty($sections)) {
            if (empty($interactive['type'])) {
                if (!empty($sections)) {
                    $interactive['type'] = 'list';
                } elseif ($templateButtonsUsed) {
                    $interactive['type'] = 'template';
                } elseif (!empty($interactive['buttons'])) {
                    $interactive['type'] = 'buttons';
                }
            }
            if (!empty($inlineText)) {
                $interactive['text'] = $interactive['text'] ?? $inlineText;
            }
            if (!empty($sections)) {
                $interactive['sections'] = $sections;
            }
            $payload['interactive'] = $interactive;
        } elseif ($inlineText !== '') {
            $payload['text'] = $inlineText;
        }

        if (!isset($payload['text']) && $outsideText !== '') {
            $payload['text'] = $outsideText;
        }

        if (empty($payload['interactive']) && empty($payload['text']) && empty($payload['requestPhoneNumber'])) {
            return null;
        }

        return $payload;
    }

    public static function sendEmail($to, $subject, $body, $attachmentPath = null)
    {
        global $config, $PAGES_PATH, $debug_mail;
        if (empty($body)) {
            return "";
        }
        if (empty($to)) {
            return "";
        }
        run_hook('send_email', [$to, $subject, $body]); #HOOK
        if (empty($config['smtp_host'])) {
            $attr = "";
            if (!empty($config['mail_from'])) {
                $attr .= "From: " . $config['mail_from'] . "\r\n";
            }
            if (!empty($config['mail_reply_to'])) {
                $attr .= "Reply-To: " . $config['mail_reply_to'] . "\r\n";
            }
            mail($to, $subject, $body, $attr);
            self::logMessage('Email', $to, $body, 'Success');
            return true;
        } else {
            $mail = new PHPMailer();
            $mail->isSMTP();
            if (isset($debug_mail) && $debug_mail == 'Dev') {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'];
            $mail->Password = $config['smtp_pass'];
            $mail->SMTPSecure = $config['smtp_ssltls'];
            $mail->Port = $config['smtp_port'];
            if (!empty($config['mail_from'])) {
                $mail->setFrom($config['mail_from']);
            }
            if (!empty($config['mail_reply_to'])) {
                $mail->addReplyTo($config['mail_reply_to']);
            }

            $mail->addAddress($to);
            $mail->Subject = $subject;
            // Attachments
            if (!empty($attachmentPath)) {
                $mail->addAttachment($attachmentPath);
            }

            if (!file_exists($PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html')) {
                if (!copy($PAGES_PATH . '_template' . DIRECTORY_SEPARATOR . 'Email.html', $PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html')) {
                    file_put_contents($PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html', Http::getData('https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/pages_template/Email.html'));
                }
            }

            if (file_exists($PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html')) {
                $html = file_get_contents($PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html');
                $html = str_replace('[[Subject]]', $subject, $html);
                $html = str_replace('[[Company_Address]]', nl2br($config['address']), $html);
                $html = str_replace('[[Company_Name]]', nl2br($config['CompanyName']), $html);
                $html = str_replace('[[Body]]', nl2br($body), $html);
                $mail->isHTML(true);
                $mail->Body = $html;
                $mail->Body = $html;
            } else {
                $mail->isHTML(false);
                $mail->Body = $body;
            }
            if (!$mail->send()) {
                $errorMessage = Lang::T("Email not sent, Mailer Error: ") . $mail->ErrorInfo;
                self::logMessage('Email', $to, $body, 'Error', $errorMessage);
                return false;
            } else {
                self::logMessage('Email', $to, $body, 'Success');
                return true;
            }

            //<p style="font-family: Helvetica, sans-serif; font-size: 16px; font-weight: normal; margin: 0; margin-bottom: 16px;">
        }
    }

    public static function sendPackageNotification($customer, $package, $price, $message, $via, $templateKey = '')
    {
        global $ds, $config;
        if (empty($message)) {
            return "";
        }
        $msg = str_replace('[[name]]', $customer['fullname'], $message);
        $msg = str_replace('[[username]]', $customer['username'], $msg);
        $msg = str_replace('[[plan]]', $package, $msg);
        $msg = str_replace('[[package]]', $package, $msg);
        $msg = str_replace('[[price]]', Lang::moneyFormat($price), $msg);
        // Calculate bills and additional costs
        list($bills, $add_cost) = User::getBills($customer['id']);

        // Initialize note and total variables
        $note = "";
        $total = $price;

        // Add bills to the note if there are any additional costs
        if ($add_cost != 0) {
            foreach ($bills as $k => $v) {
                $note .= $k . " : " . Lang::moneyFormat($v) . "\n";
            }
            $total += $add_cost;
        }

        // Calculate tax
        $tax = 0;
        $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
        if ($tax_enable === 'yes') {
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : null;
            $custom_tax_rate = isset($config['custom_tax_rate']) ? (float) $config['custom_tax_rate'] : null;

            $tax_rate = ($tax_rate_setting === 'custom') ? $custom_tax_rate : $tax_rate_setting;
            $tax = Package::tax($price, $tax_rate);

            if ($tax != 0) {
                $note .= "Tax : " . Lang::moneyFormat($tax) . "\n";
                $total += $tax;
            }
        }

        // Add total to the note
        $note .= "Total : " . Lang::moneyFormat($total) . "\n";

        // Replace placeholders in the message
        $msg = str_replace('[[bills]]', $note, $msg);

        if ($ds) {
            $msg = str_replace('[[expired_date]]', Lang::dateAndTimeFormat($ds['expiration'], $ds['time']), $msg);
        } else {
            $msg = str_replace('[[expired_date]]', "", $msg);
        }

        if (strpos($msg, '[[payment_link]]') !== false) {
            // token only valid for 1 day, for security reason
            $token = User::generateToken($customer['id'], 1);
            if (!empty($token['token'])) {
                $tur = ORM::for_table('tbl_user_recharges')
                    ->where('customer_id', $customer['id'])
                    ->where('namebp', $package)
                    ->find_one();
                if ($tur) {
                    $url = '?_route=home&recharge=' . $tur['id'] . '&uid=' . urlencode($token['token']);
                    $msg = str_replace('[[payment_link]]', $url, $msg);
                }
            } else {
                $msg = str_replace('[[payment_link]]', '', $msg);
            }
        }


        if (
            !empty($customer['phonenumber']) && strlen($customer['phonenumber']) > 5
            && !empty($message) && in_array($via, ['sms', 'wa'])
        ) {
            if ($via == 'sms') {
                Message::sendSMS($customer['phonenumber'], $msg);
            } else if ($via == 'email') {
                self::sendEmail($customer['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Internet Plan Reminder"), $msg);
            } else if ($via == 'wa') {
                $options = self::isWhatsappQueueEnabledForNotificationTemplate($templateKey) ? ['queue' => true, 'queue_context' => 'notification'] : [];
                Message::sendWhatsapp($customer['phonenumber'], $msg, $options);
            }
        }
        return "$via: $msg";
    }

    public static function sendBalanceNotification($cust, $target, $balance, $balance_now, $message, $via, $templateKey = '')
    {
        global $config;
        $msg = str_replace('[[name]]', $target['fullname'] . ' (' . $target['username'] . ')', $message);
        $msg = str_replace('[[current_balance]]', Lang::moneyFormat($balance_now), $msg);
        $msg = str_replace('[[balance]]', Lang::moneyFormat($balance), $msg);
        $phone = $cust['phonenumber'];
        if (
            !empty($phone) && strlen($phone) > 5
            && !empty($message) && in_array($via, ['sms', 'wa', 'email'])
        ) {
            if ($via == 'sms') {
                Message::sendSMS($phone, $msg);
            } else if ($via == 'email') {
                self::sendEmail($cust['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Balance Notification"), $msg);
            } else if ($via == 'wa') {
                $options = self::isWhatsappQueueEnabledForNotificationTemplate($templateKey) ? ['queue' => true, 'queue_context' => 'notification'] : [];
                Message::sendWhatsapp($phone, $msg, $options);
            }
            self::addToInbox($cust['id'], Lang::T('Balance Notification'), $msg);
        }
        return "$via: $msg";
    }

    public static function sendInvoice($cust, $trx)
    {
        global $config, $db_pass;
        $planType = (string) ($trx['type'] ?? '');
        $planId = (int) ($trx['plan_id'] ?? 0);
        if ($planId < 1) {
            $planName = trim((string) ($trx['plan_name'] ?? ''));
            if ($planName !== '') {
                try {
                    $q = ORM::for_table('tbl_plans')->where('name_plan', $planName);
                    $typeNorm = strtoupper(trim((string) $planType));
                    if ($typeNorm !== '') {
                        $q->where_raw('UPPER(TRIM(`type`)) = ?', [$typeNorm]);
                    }
                    $planRow = $q->order_by_desc('id')->find_one();
                    if ($planRow) {
                        $planId = (int) ($planRow['id'] ?? 0);
                    }
                } catch (Throwable $e) {
                    // ignore lookup errors, fall back to type-only override
                }
            }
        }

        $textInvoice = Lang::getNotifText('invoice_paid', [
            'plan_id' => $planId,
            'type' => $planType,
        ]);
        $textInvoice = str_replace('[[company_name]]', $config['CompanyName'], $textInvoice);
        $textInvoice = str_replace('[[address]]', $config['address'], $textInvoice);
        $textInvoice = str_replace('[[phone]]', $config['phone'], $textInvoice);
        $textInvoice = str_replace('[[invoice]]', $trx['invoice'], $textInvoice);
        $textInvoice = str_replace('[[date]]', Lang::dateAndTimeFormat($trx['recharged_on'], $trx['recharged_time']), $textInvoice);
        $textInvoice = str_replace('[[trx_date]]', Lang::dateAndTimeFormat($trx['recharged_on'], $trx['recharged_time']), $textInvoice);
        if (!empty($trx['note'])) {
            $textInvoice = str_replace('[[note]]', $trx['note'], $textInvoice);
        }
        $gc = explode("-", $trx['method']);
        $textInvoice = str_replace('[[payment_gateway]]', trim($gc[0]), $textInvoice);
        $textInvoice = str_replace('[[payment_channel]]', trim($gc[1]), $textInvoice);
        $textInvoice = str_replace('[[type]]', $trx['type'], $textInvoice);
        $textInvoice = str_replace('[[plan_name]]', $trx['plan_name'], $textInvoice);
        $textInvoice = str_replace('[[plan_price]]', Lang::moneyFormat($trx['price']), $textInvoice);
        $textInvoice = str_replace('[[name]]', $cust['fullname'], $textInvoice);
        $textInvoice = str_replace('[[note]]', $cust['note'], $textInvoice);
        $textInvoice = str_replace('[[user_name]]', $trx['username'], $textInvoice);
        $textInvoice = str_replace('[[user_password]]', $cust['password'], $textInvoice);
        $textInvoice = str_replace('[[username]]', $trx['username'], $textInvoice);
        $textInvoice = str_replace('[[password]]', $cust['password'], $textInvoice);
        $textInvoice = str_replace('[[expired_date]]', Lang::dateAndTimeFormat($trx['expiration'], $trx['time']), $textInvoice);
        $textInvoice = str_replace('[[footer]]', $config['note'], $textInvoice);

        $inv_url = "?_route=voucher/invoice/$trx[id]/" . md5($trx['id'] . $db_pass);
        $textInvoice = str_replace('[[invoice_link]]', $inv_url, $textInvoice);

        // Calculate bills and additional costs
        list($bills, $add_cost) = User::getBills($cust['id']);

        // Initialize note and total variables
        $note = "";
        $total = $trx['price'];

        // Add bills to the note if there are any additional costs
        if ($add_cost != 0) {
            foreach ($bills as $k => $v) {
                $note .= $k . " : " . Lang::moneyFormat($v) . "\n";
            }
            $total += $add_cost;
        }

        // Calculate tax
        $tax = 0;
        $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
        if ($tax_enable === 'yes') {
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : null;
            $custom_tax_rate = isset($config['custom_tax_rate']) ? (float) $config['custom_tax_rate'] : null;

            $tax_rate = ($tax_rate_setting === 'custom') ? $custom_tax_rate : $tax_rate_setting;
            $tax = Package::tax($trx['price'], $tax_rate);

            if ($tax != 0) {
                $note .= "Tax : " . Lang::moneyFormat($tax) . "\n";
                $total += $tax;
            }
        }

        // Add total to the note
        $note .= "Total : " . Lang::moneyFormat($total) . "\n";

        // Replace placeholders in the message
        $textInvoice = str_replace('[[bills]]', $note, $textInvoice);

        if ($config['user_notification_payment'] == 'sms') {
            Message::sendSMS($cust['phonenumber'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'email') {
            self::sendEmail($cust['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Invoice") . ' #' . $trx['invoice'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'wa') {
            $options = self::isWhatsappQueueEnabledForNotificationTemplate('invoice_paid') ? ['queue' => true, 'queue_context' => 'invoice'] : [];
            Message::sendWhatsapp($cust['phonenumber'], $textInvoice, $options);
        }
    }


    public static function addToInbox($to_customer_id, $subject, $body, $from = 'System')
    {
        $user = User::find($to_customer_id);
        try {
            $v = ORM::for_table('tbl_customers_inbox')->create();
            $v->from = $from;
            $v->customer_id = $to_customer_id;
            $v->subject = $subject;
            $v->date_created = date('Y-m-d H:i:s');
            $v->body = nl2br($body);
            $v->save();
            self::logMessage("Inbox", $user->username, $body, "Success");
            return true;
        } catch (Throwable $e) {
            $errorMessage = Lang::T("Error adding message to inbox: " . $e->getMessage());
            self::logMessage('Inbox', $user->username, $body, 'Error', $errorMessage);
            return false;
        }
    }

    public static function getMessageType($type, $message)
    {
        if (strpos($message, "<divider>") === false) {
            return $message;
        }
        $msgs = array_values(explode("<divider>", (string) $message));
        $type = strtoupper(trim((string) $type));

        if (count($msgs) >= 3) {
            // Backward-compatible convention:
            // 0: Hotspot (default), 1: PPPoE, 2: VPN
            if ($type === "PPPOE") {
                return $msgs[1];
            }
            if ($type === "VPN") {
                return $msgs[2];
            }
            return $msgs[0];
        }

        if (count($msgs) >= 2) {
            if ($type === "PPPOE") {
                return $msgs[1];
            }
            return $msgs[0];
        }

        return $message;
    }

    public static function logMessage($messageType, $recipient, $messageContent, $status, $errorMessage = null)
    {
        $log = ORM::for_table('tbl_message_logs')->create();
        $log->message_type = self::sanitizeForLog($messageType);
        $log->recipient = self::sanitizeForLog($recipient);
        $log->message_content = self::sanitizeForLog($messageContent);
        $log->status = self::sanitizeForLog($status);
        $log->error_message = self::sanitizeForLog($errorMessage);
        $log->save();
    }

    private static function sanitizeForLog($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $normalized = $value;

        if (function_exists('mb_detect_encoding')) {
            $encoding = mb_detect_encoding($normalized, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252'], true);
            if ($encoding !== false && $encoding !== 'UTF-8') {
                $normalized = mb_convert_encoding($normalized, 'UTF-8', $encoding);
            }
        }

        if (function_exists('mb_check_encoding') && !mb_check_encoding($normalized, 'UTF-8')) {
            if (function_exists('iconv')) {
                $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $normalized);
                if ($converted !== false) {
                    $normalized = $converted;
                }
            }

            if (function_exists('mb_check_encoding') && !mb_check_encoding($normalized, 'UTF-8')) {
                $normalized = preg_replace('/[\x00-\x1F\x7F]/u', '', $normalized);
            }
        }

        return $normalized;
    }
}
