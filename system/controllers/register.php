<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

if ($_c['disable_registration'] == 'noreg') {
    _alert(Lang::T('Registration Disabled'), 'danger', "login");
}
if (isset($routes['1'])) {
    $do = $routes['1'];
} else {
    $do = 'register-display';
}

$otpPath = $CACHE_PATH . File::pathFixer('/sms/');

switch ($do) {
    case 'post':
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('register'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        Csrf::generateAndStoreToken();
        $otp_code = _post('otp_code');
        $phone_number = alphanumeric(_post('phone_number'), "+_.@-");
        if ($_c['registration_username'] === 'phone') {
            $username = Lang::phoneFormat($phone_number);
        } else {
            $username = alphanumeric(_post('username'), "+_.@-");
        }
        $email = _post('email');
        if ($_c['registration_username'] === 'email') {
            $email = $username;
        }
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $address = _post('address');

        $msg = '';
        if ($_c['registration_username'] !== 'phone' && Validator::Length($username, 35, 2) == false) {
            $msg .= "Username should be between 3 to 55 characters<br>";
        }
        if ($_c['registration_username'] === 'email' && !Validator::Email($username)) {
            $msg .= 'Email is not Valid<br>';
        }
        if ($config['man_fields_fname'] == 'yes') {
            if (Validator::Length($fullname, 36, 2) == false) {
                $msg .= "Full Name should be between 3 to 25 characters<br>";
            }
        }
        if (!Validator::Length($password, 35, 2)) {
            $msg .= "Password should be between 3 to 35 characters<br>";
        }
        if ($config['man_fields_email'] == 'yes') {
            if (!Validator::Email($email)) {
                $msg .= 'Email is not Valid<br>';
            }
        }
        if ($password != $cpassword) {
            $msg .= Lang::T('Passwords does not match') . '<br>';
        }

        // OTP verification if OTP is enabled
        if ($_c['sms_otp_registration'] == 'yes') {
            $otpPath .= sha1("$phone_number$db_pass") . ".txt";
            run_hook('validate_otp'); #HOOK
            // Expire after configured time
            if (file_exists($otpPath) && time() - filemtime($otpPath) > (int)$_c['otp_expiry']) {
                unlink($otpPath);
                r2(getUrl('register'), 'e', 'Verification code expired');
            } else if (file_exists($otpPath)) {
                $code = file_get_contents($otpPath);
                if ($code != $otp_code) {
                    $ui->assign('username', $username);
                    $ui->assign('fullname', $fullname);
                    $ui->assign('address', $address);
                    $ui->assign('email', $email);
                    $ui->assign('phone_number', $phone_number);
                    $ui->assign('notify', 'Wrong Verification code');
                    $ui->assign('notify_t', 'd');
                    $ui->assign('_title', Lang::T('Register'));
                    $ui->assign('csrf_token', Csrf::generateAndStoreToken());
                    $ui->display('customer/register-otp.tpl');
                    exit();
                } else {
                    unlink($otpPath);
                }
            } else {
                r2(getUrl('register'), 'e', 'No Verification code');
            }
        }

        // Validate phone number format
        if ($_c['sms_otp_registration'] == 'yes' || $_c['registration_username'] === 'phone') {
            if (!Validator::PhoneWithCountry($phone_number)) {
                $msg .= Lang::T('Invalid phone number; start with 62 or 0') . '<br>';
            }
        }

        if ($_c['registration_username'] === 'phone') {
            $formatted = $username;
            $d = ORM::for_table('tbl_customers')->where('phonenumber', $username)->find_one();
            if ($d) {
                $msg .= Lang::T('Account already exists') . '<br>';
            }
        } else {
            $formatted = Lang::phoneFormat($phone_number);
            $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
            if ($d) {
                $msg .= Lang::T('Account already exists') . '<br>';
            }
            if ($_c['sms_otp_registration'] == 'yes') {
                $d = ORM::for_table('tbl_customers')->where('phonenumber', $formatted)->find_one();
                if ($d) {
                    $msg .= Lang::T('Phone number already exists') . '<br>';
                }
            }
        }
        // Check if phone number already exists
        $d = ORM::for_table('tbl_customers')->where('phonenumber', $phone_number)->find_one();
        if ($d) {
            $msg .= Lang::T('Phone number already registered by another customer') . '<br>';
        }

        if ($msg == '') {
            $d = ORM::for_table('tbl_customers')->create();
            $d->username = alphanumeric($username, "+_.@-");
            $d->password = $password;
            $d->fullname = $fullname;
            $d->address = $address;
            if ($_c['registration_username'] === 'email') {
                $d->email = $d->username;
            } else {
                $d->email = $email;
            }
            $d->phonenumber = $formatted;
            if ($d->save()) {
                $user = $d->id();
                if ($config['photo_register'] == 'yes' && !empty($_FILES['photo']['name']) && file_exists($_FILES['photo']['tmp_name'])) {
                    if (function_exists('imagecreatetruecolor')) {
                        $hash = md5_file($_FILES['photo']['tmp_name']);
                        $subfolder = substr($hash, 0, 2);
                        $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
                        if (!file_exists($folder)) {
                            mkdir($folder);
                        }
                        $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR;
                        if (!file_exists($folder)) {
                            mkdir($folder);
                        }
                        $imgPath = $folder . $hash . '.jpg';
                        File::resizeCropImage($_FILES['photo']['tmp_name'], $imgPath, 1600, 1600, 100);
                        $d->photo = '/photos/' . $subfolder . '/' . $hash . '.jpg';
                        $d->save();
                    }
                }
                if (file_exists($_FILES['photo']['tmp_name']))
                    unlink($_FILES['photo']['tmp_name']);
                User::setFormCustomField($user);
                run_hook('register_user'); #HOOK
                if (isset($config['welcome_package_enable']) && $config['welcome_package_enable'] == 'yes' && !empty($config['welcome_package_plan'])) {
                    $plan = ORM::for_table('tbl_plans')->find_one($config['welcome_package_plan']);
                    if ($plan) {
                        Package::rechargeUser($user, $plan['routers'], $plan['id'], 'Welcome', 'Auto');
                    }
                }

	                // Send welcome message on successful self-registration (optional).
	                if (isset($config['reg_send_welcome_message']) && $config['reg_send_welcome_message'] === 'yes') {
	                    $welcomeMessage = Lang::getNotifText('welcome_message', ['purpose' => 'self_register']);
	                    $welcomeMessage = str_replace('[[company]]', $config['CompanyName'], $welcomeMessage);
	                    $welcomeMessage = str_replace('[[name]]', $d['fullname'], $welcomeMessage);
	                    $welcomeMessage = str_replace('[[username]]', $d['username'], $welcomeMessage);
	                    $welcomeMessage = str_replace('[[password]]', $d['password'], $welcomeMessage);
	                    $welcomeMessage = str_replace('[[url]]', APP_URL . '/?_route=login', $welcomeMessage);
	                    $subject = "Welcome to " . $config['CompanyName'];

	                    $phone = trim((string) ($d['phonenumber'] ?? ''));
	                    $emailTarget = trim((string) ($d['email'] ?? ''));

	                    $hasViaConfig = array_key_exists('reg_welcome_via_whatsapp', $config)
	                        || array_key_exists('reg_welcome_via_sms', $config)
	                        || array_key_exists('reg_welcome_via_email', $config)
	                        || array_key_exists('reg_welcome_via_inbox', $config);

	                    if ($hasViaConfig) {
	                        $viaWhatsapp = (isset($config['reg_welcome_via_whatsapp']) && $config['reg_welcome_via_whatsapp'] === 'yes');
	                        $viaSms = (isset($config['reg_welcome_via_sms']) && $config['reg_welcome_via_sms'] === 'yes');
	                        $viaEmail = (isset($config['reg_welcome_via_email']) && $config['reg_welcome_via_email'] === 'yes');
	                        $viaInbox = (isset($config['reg_welcome_via_inbox']) && $config['reg_welcome_via_inbox'] === 'yes');
	                    } else {
	                        // Backward-compatibility: default to OTP method behavior (WA/SMS),
	                        // and only use email when phone is missing.
	                        $otpType = strtolower(trim((string) ($config['phone_otp_type'] ?? 'sms')));
	                        if (!in_array($otpType, ['sms', 'whatsapp', 'both'], true)) {
	                            $otpType = 'sms';
	                        }
	                        $viaWhatsapp = ($otpType === 'whatsapp' || $otpType === 'both');
	                        $viaSms = ($otpType === 'sms' || $otpType === 'both');
	                        $viaEmail = ($phone === '');
	                        $viaInbox = false;
	                    }

	                    $waOptions = Message::isWhatsappQueueEnabledForNotificationTemplate('welcome_message')
	                        ? ['queue' => true, 'queue_context' => 'notification']
	                        : [];

	                    if ($viaWhatsapp && $phone !== '') {
	                        try {
	                            Message::sendWhatsapp($phone, $welcomeMessage, $waOptions);
	                        } catch (Throwable $e) {
	                            _log('Failed to send welcome message via WhatsApp: ' . $e->getMessage());
	                        }
	                    }
	                    if ($viaSms && $phone !== '') {
	                        try {
	                            Message::sendSMS($phone, $welcomeMessage);
	                        } catch (Throwable $e) {
	                            _log('Failed to send welcome message via SMS: ' . $e->getMessage());
	                        }
	                    }
	                    if ($viaEmail && $emailTarget !== '' && Validator::Email($emailTarget)) {
	                        try {
	                            Message::sendEmail($emailTarget, $subject, $welcomeMessage, $emailTarget);
	                        } catch (Throwable $e) {
	                            _log('Failed to send welcome message via Email: ' . $e->getMessage());
	                        }
	                    }
	                    if ($viaInbox) {
	                        try {
	                            Message::addToInbox($user, $subject, $welcomeMessage);
	                        } catch (Throwable $e) {
	                            _log('Failed to add welcome message to Inbox: ' . $e->getMessage());
	                        }
	                    }
	                }
                $msg .= Lang::T('Registration successful') . '<br>';
                if ($config['reg_nofify_admin'] == 'yes') {
                    sendTelegram($config['CompanyName'] . ' - ' . Lang::T('New User Registration') . "\n\nFull Name: " . $fullname . "\nUsername: " . $username . "\nEmail: " . $email . "\nPhone Number: " . $phone_number . "\nAddress: " . $address);
                }
                r2(getUrl('login'), 's', Lang::T('Register Success! You can login now'));
            } else {
                $ui->assign('username', $username);
                $ui->assign('fullname', $fullname);
                $ui->assign('address', $address);
                $ui->assign('email', $email);
                $ui->assign('phone_number', $phone_number);
                $ui->assign('notify', 'Failed to register');
                $ui->assign('notify_t', 'd');
                $ui->assign('_title', Lang::T('Register'));
                run_hook('view_otp_register'); #HOOK
                $ui->display('customer/register-rotp.tpl');
            }
        } else {
            $ui->assign('username', $username);
            $ui->assign('fullname', $fullname);
            $ui->assign('address', $address);
            $ui->assign('email', $email);
            $ui->assign('phone_number', $phone_number);
            $ui->assign('notify', $msg);
            $ui->assign('notify_t', 'd');
            $ui->assign('_title', Lang::T('Register'));
            // Check if OTP is enabled
            if (!empty($config['sms_url']) && $_c['sms_otp_registration'] == 'yes') {
                // Display register-otp.tpl if OTP is enabled
                $ui->assign('csrf_token', Csrf::generateAndStoreToken());
                $ui->display('customer/register-otp.tpl');
            } else {
                $UPLOAD_URL_PATH = str_replace($root_path, '', $UPLOAD_PATH);
                $company_logo_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png';
                $company_logo_url = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.png';
                $company_logo_login_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.login.png';
                $company_logo_login_url = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.login.png';
                $company_logo_favicon_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.favicon.png';
                $company_logo_favicon_url = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.favicon.png';
                if (!empty($config['login_page_logo']) && file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'])) {
                    $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'];
                } elseif (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'login-logo.png')) {
                    $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png';
                } elseif (file_exists($company_logo_login_path)) {
                    $login_logo = $company_logo_login_url;
                } elseif (file_exists($company_logo_path)) {
                    $login_logo = $company_logo_url;
                } else {
                    $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.default.png';
                }

                if (!empty($config['login_page_wallpaper']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'])) {
                    $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'];
                } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png')) {
                    $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png';
                } else {
                    $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.default.png';
                }

                if (!empty($config['login_page_favicon']) && file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'])) {
                    $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'];
                } elseif (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'favicon.png')) {
                    $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png';
                } elseif (file_exists($company_logo_favicon_path)) {
                    $favicon = $company_logo_favicon_url;
                } elseif (file_exists($company_logo_path)) {
                    $favicon = $company_logo_url;
                } else {
                    $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.default.png';
                }

                $ui->assign('login_logo', $login_logo);
                $ui->assign('wallpaper', $wallpaper);
                $ui->assign('favicon', $favicon);
                $ui->assign('csrf_token', Csrf::generateAndStoreToken());
                $ui->assign('_title', Lang::T('Login'));
                $ui->assign('customFields', User::getFormCustomField($ui, true));
                switch ($config['login_page_type']) {
                    case 'custom':
                        $ui->display('customer/reg-login-custom-' . $config['login_Page_template'] . '.tpl');
                        break;
                    default:
                        $ui->display('customer/register.tpl');
                        break;
                }
            }
        }
        break;

    default:
        $csrf_token = Csrf::generateAndStoreToken();
        if ($_c['sms_otp_registration'] == 'yes') {
            $phone_number = _post('phone_number');
            if (!empty($phone_number)) {
                if ($_c['registration_username'] === 'phone') {
                    if (!Validator::PhoneWithCountry($phone_number)) {
                        r2(getUrl('register'), 'e', Lang::T('Invalid phone number; start with 62 or 0'));
                    }
                    $phone_number = Lang::phoneFormat($phone_number);
                    $d = ORM::for_table('tbl_customers')->where('phonenumber', $phone_number)->find_one();
                    if ($d) {
                        r2(getUrl('register'), 'e', Lang::T('Account already exists'));
                    }
                } else {
                    $d = ORM::for_table('tbl_customers')->where('username', $phone_number)->find_one();
                    if ($d) {
                        r2(getUrl('register'), 'e', Lang::T('Account already exists'));
                    }
                    if (!Validator::PhoneWithCountry($phone_number)) {
                        r2(getUrl('register'), 'e', Lang::T('Invalid phone number; start with 62 or 0'));
                    }
                    $phone_number = Lang::phoneFormat($phone_number);
                    $d = ORM::for_table('tbl_customers')->where('phonenumber', $phone_number)->find_one();
                    if ($d) {
                        r2(getUrl('register'), 'e', Lang::T('Phone number already exists'));
                    }
                }
                if (!file_exists($otpPath)) {
                    mkdir($otpPath);
                    touch($otpPath . 'index.html');
                }
                $otpPath .= sha1($phone_number . $db_pass) . ".txt";
                if (file_exists($otpPath) && time() - filemtime($otpPath) < (int)$_c['otp_wait']) {
                    $ui->assign('phone_number', $phone_number);
                    $ui->assign('notify', 'Please wait ' . ((int)$_c['otp_wait'] - (time() - filemtime($otpPath))) . ' seconds before sending another SMS');
                    $ui->assign('notify_t', 'd');
                    $ui->assign('_title', Lang::T('Register'));
                    $ui->assign('csrf_token', $csrf_token);
                    $ui->display('customer/register-rotp.tpl');
                    return;
	                } else {
	                    $otp = rand(100000, 999999);
	                    file_put_contents($otpPath, $otp);
	                    $otpMessage = Message::renderOtpMessage($otp, Lang::T("Registration code"), 'register');
	                    $otpPlainMessage = Message::whatsappTemplateToPlainText($otpMessage);
	                    if ($otpPlainMessage === '') {
	                        $otpPlainMessage = 'Kode OTP: ' . $otp;
	                    } elseif (strpos($otpPlainMessage, (string) $otp) === false) {
	                        $otpPlainMessage .= "\n\nKode OTP: " . $otp;
	                    }
		                    if ($config['phone_otp_type'] == 'whatsapp') {
		                        // Template decides text vs interactive (via [[wa]] block). Gateway support decides whether
		                        // interactive is delivered or downgraded to plain text (fallback).
		                        $waSent = Message::sendWhatsapp($phone_number, $otpMessage);
		                        if ($waSent === false) {
		                            $ui->assign('notify', Lang::T('OTP not sent: phone number isn\'t registered on WhatsApp'));
		                            $ui->assign('notify_t', 'd');
		                            $ui->assign('_title', Lang::T('Register'));
                            run_hook('view_otp_register'); #HOOK
                            $ui->display('customer/register-rotp.tpl');
	                            return;
	                        }
	                    } else if ($config['phone_otp_type'] == 'both') {
	                        $waSent = Message::sendWhatsapp($phone_number, $otpMessage);
	                        if ($waSent === false) {
	                            $ui->assign('notify', Lang::T('OTP not sent: phone number isn\'t registered on WhatsApp'));
	                            $ui->assign('notify_t', 'd');
	                            $ui->assign('_title', Lang::T('Register'));
                            run_hook('view_otp_register'); #HOOK
                            $ui->display('customer/register-rotp.tpl');
	                            return;
	                        }
	                        Message::sendSMS($phone_number, $otpPlainMessage);
	                    } else {
	                        Message::sendSMS($phone_number, $otpPlainMessage);
	                    }
                    $ui->assign('phone_number', $phone_number);
                    $ui->assign('notify', 'Registration code has been sent to your phone');
                    $ui->assign('notify_t', 's');
                    $ui->assign('_title', Lang::T('Register'));
                    $ui->assign('customFields', User::getFormCustomField($ui, true));
                    $ui->assign('csrf_token', $csrf_token);
                    $ui->display('customer/register-otp.tpl');
                }
            } else {
                $ui->assign('_title', Lang::T('Register'));
                run_hook('view_otp_register'); #HOOK
                $ui->assign('csrf_token', $csrf_token);
                $ui->display('customer/register-rotp.tpl');
                return;
            }
        } else {
            $UPLOAD_URL_PATH = str_replace($root_path, '', $UPLOAD_PATH);
            $company_logo_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png';
            $company_logo_url = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.png';
            $company_logo_login_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.login.png';
            $company_logo_login_url = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.login.png';
            $company_logo_favicon_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.favicon.png';
            $company_logo_favicon_url = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.favicon.png';
            if (!empty($config['login_page_logo']) && file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'])) {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'];
            } elseif (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'login-logo.png')) {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png';
            } elseif (file_exists($company_logo_login_path)) {
                $login_logo = $company_logo_login_url;
            } elseif (file_exists($company_logo_path)) {
                $login_logo = $company_logo_url;
            } else {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.default.png';
            }

            if (!empty($config['login_page_wallpaper']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'])) {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'];
            } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png')) {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png';
            } else {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.default.png';
            }

            if (!empty($config['login_page_favicon']) && file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'])) {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'];
            } elseif (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'favicon.png')) {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png';
            } elseif (file_exists($company_logo_favicon_path)) {
                $favicon = $company_logo_favicon_url;
            } elseif (file_exists($company_logo_path)) {
                $favicon = $company_logo_url;
            } else {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.default.png';
            }

            $ui->assign('login_logo', $login_logo);
            $ui->assign('wallpaper', $wallpaper);
            $ui->assign('favicon', $favicon);
            $ui->assign('csrf_token', $csrf_token);
            $ui->assign('_title', Lang::T('Login'));
            $ui->assign('customFields', User::getFormCustomField($ui, true));
            $ui->assign('username', "");
            $ui->assign('fullname', "");
            $ui->assign('address', "");
            $ui->assign('email', "");
            $ui->assign('otp', false);
            $ui->assign('_title', Lang::T('Register'));
            run_hook('view_register'); #HOOK
            switch ($config['login_page_type']) {
                case 'custom':
                    $ui->display('customer/reg-login-custom-' . $config['login_Page_template'] . '.tpl');
                    break;
                default:
                    $ui->display('customer/register.tpl');
                    break;
            }

        }
        break;
}
