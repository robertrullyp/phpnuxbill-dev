<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', 'Plugin Manager');
$ui->assign('_system_menu', 'settings');

$pluginRepositoryUpstream = 'https://hotspotbilling.github.io/Plugin-Repository/repository.json';
$pluginRepositoryFile = File::pathFixer(__DIR__ . '/../../plugin-repository.json');
$pluginRepositoryCustomFile = File::pathFixer(__DIR__ . '/../../plugin-repository.custom.json');

$action = $routes['1'];
$ui->assign('_admin', $admin);


if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

$cache = $CACHE_PATH . File::pathFixer('/plugin_repository.json');
$repoError = null;
$jsonData = null;
$json = [
    'plugins' => [],
    'payment_gateway' => [],
    'devices' => [],
];
$loadedFromCache = false;

$syncError = '';
$mergedPayload = pluginRepositorySyncMergedFile(
    $pluginRepositoryUpstream,
    $pluginRepositoryFile,
    $pluginRepositoryCustomFile,
    $syncError
);
if ($mergedPayload !== null) {
    file_put_contents($cache, $mergedPayload);
    $jsonData = $mergedPayload;
} else {
    $localPayload = pluginRepositoryReadPayload($pluginRepositoryFile);
    if ($localPayload !== null) {
        $jsonData = $localPayload;
    } else if (file_exists($cache)) {
        $cachedFallback = file_get_contents($cache);
        if ($cachedFallback !== false) {
            $jsonData = ltrim($cachedFallback, "\xEF\xBB\xBF");
            $loadedFromCache = true;
        } else {
            $repoError = Lang::T('Unable to load plugin repository data. Please try again later.');
        }
    } else {
        $repoError = Lang::T('Unable to load plugin repository data. Please try again later.');
    }

    if ($syncError !== '') {
        $warning = Lang::T('Unable to sync upstream plugin repository. Showing local repository data.');
        if ($repoError !== null) {
            $repoError .= ' (' . $syncError . ')';
        } else {
            $repoError = $warning . ' (' . $syncError . ')';
        }
    }
}

if ($jsonData !== null) {
    $decoded = json_decode($jsonData, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $json['plugins'] = isset($decoded['plugins']) && is_array($decoded['plugins']) ? $decoded['plugins'] : [];
        $json['payment_gateway'] = isset($decoded['payment_gateway']) && is_array($decoded['payment_gateway']) ? $decoded['payment_gateway'] : [];
        $json['devices'] = isset($decoded['devices']) && is_array($decoded['devices']) ? $decoded['devices'] : [];

        $json['plugins'] = pluginRepositorySortItemsByLatest($json['plugins']);
        $json['payment_gateway'] = pluginRepositorySortItemsByLatest($json['payment_gateway']);
        $json['devices'] = pluginRepositorySortItemsByLatest($json['devices']);

        if ($loadedFromCache && empty($json['plugins']) && empty($json['payment_gateway'])) {
            if (file_exists($cache)) {
                unlink($cache);
            }
            r2(getUrl('pluginmanager'));
        }
    } else {
        $errorMessage = json_last_error_msg();
        $repoError = Lang::T('Unable to parse plugin repository response. Please refresh later.');
        if (!empty($errorMessage)) {
            $repoError .= ' (' . $errorMessage . ')';
        }
    }
} elseif ($repoError === null) {
    $repoError = Lang::T('Unable to load plugin repository data. Please try again later.');
}
switch ($action) {
    case 'refresh':
        if (file_exists($cache))
            unlink($cache);
        r2(getUrl('pluginmanager'), 's', 'Refresh success');
        break;
    case 'dlinstall':
        if ($_app_stage == 'Demo') {
            r2(getUrl('pluginmanager'), 'e', 'Demo Mode cannot install as it Security risk');
        }
        if (!is_writeable($CACHE_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder cache/ is not writable');
        }
        if (!is_writeable($PLUGIN_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder plugin/ is not writable');
        }
        if (!is_writeable($DEVICE_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder devices/ is not writable');
        }
        if (!is_writeable($UI_PATH . DIRECTORY_SEPARATOR . 'themes')) {
            r2(getUrl('pluginmanager'), 'e', 'Folder themes/ is not writable');
        }
        $cache = $CACHE_PATH . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR;
        if (!file_exists($cache)) {
            mkdir($cache);
        }
        if (file_exists($_FILES['zip_plugin']['tmp_name'])) {
            $zip = new ZipArchive();
            $zip->open($_FILES['zip_plugin']['tmp_name']);
            $zip->extractTo($cache);
            $zip->close();
            $plugin = basename($_FILES['zip_plugin']['name']);
            $plugin_base = pathinfo($plugin, PATHINFO_FILENAME);
            $folder = resolveExtractedFolder($cache, $plugin_base);
            $ai_chatbot_detected = false;
            if (file_exists($cache . 'plugin' . DIRECTORY_SEPARATOR . 'ai_chatbot.php')) {
                $ai_chatbot_detected = true;
            } elseif ($folder) {
                if (file_exists($folder . 'plugin' . DIRECTORY_SEPARATOR . 'ai_chatbot.php') || file_exists($folder . 'ai_chatbot.php')) {
                    $ai_chatbot_detected = true;
                }
            }
            unlink($_FILES['zip_plugin']['tmp_name']);
            $success = 0;
            //moving
            if (file_exists($cache . 'plugin')) {
                File::copyFolder($cache . 'plugin' . DIRECTORY_SEPARATOR, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($cache . 'paymentgateway')) {
                File::copyFolder($cache . 'paymentgateway' . DIRECTORY_SEPARATOR, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($cache . 'theme')) {
                File::copyFolder($cache . 'theme' . DIRECTORY_SEPARATOR, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($cache . 'device')) {
                File::copyFolder($cache . 'device' . DIRECTORY_SEPARATOR, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if ($success == 0) {
                // old plugin and theme using this
                $check = strtolower($plugin_base);
                if (strpos($check, 'plugin') !== false) {
                    if ($folder) {
                        File::copyFolder($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                    }
                } else if (strpos($check, 'payment') !== false) {
                    if ($folder) {
                        File::copyFolder($folder, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                    }
                } else if (strpos($check, 'theme') !== false) {
                    if ($folder) {
                        rename($folder, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $plugin);
                    }
                } else if (strpos($check, 'device') !== false) {
                    if ($folder) {
                        File::copyFolder($folder, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                    }
                }
            }
            if ($ai_chatbot_detected) {
                $plugin_main = $PLUGIN_PATH . DIRECTORY_SEPARATOR . 'ai_chatbot.php';
                if (file_exists($plugin_main)) {
                    include_once $plugin_main;
                }
                if (function_exists('ai_chatbot_plugin_install')) {
                    ai_chatbot_plugin_install();
                }
            }
            //Cleaning
            File::deleteFolder($cache);
            r2(getUrl('pluginmanager'), 's', 'Installation success');
        } else if (_post('gh_url', '') != '') {
            $ghUrl = _post('gh_url', '');
            if (!empty($config['github_token']) && !empty($config['github_username'])) {
                $ghUrl = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $ghUrl);
            }
            $plugin = basename($ghUrl);
            $file = $cache . $plugin . '.zip';
            $fp = fopen($file, 'w+');
            $ch = curl_init($ghUrl . '/archive/refs/heads/master.zip');
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            $zip = new ZipArchive();
            $zip->open($file);
            $zip->extractTo($cache);
            $zip->close();
            $folder = resolveExtractedFolder($cache, $plugin);
            if ($folder === null) {
                r2(getUrl('pluginmanager'), 'e', 'Extracted Folder is unknown');
            }
            $ai_chatbot_detected = false;
            if (file_exists($folder . 'plugin' . DIRECTORY_SEPARATOR . 'ai_chatbot.php') || file_exists($folder . 'ai_chatbot.php')) {
                $ai_chatbot_detected = true;
            }
            $success = 0;
            if (file_exists($folder . 'plugin')) {
                File::copyFolder($folder . 'plugin' . DIRECTORY_SEPARATOR, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($folder . 'paymentgateway')) {
                File::copyFolder($folder . 'paymentgateway' . DIRECTORY_SEPARATOR, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($folder . 'theme')) {
                File::copyFolder($folder . 'theme' . DIRECTORY_SEPARATOR, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($folder . 'device')) {
                File::copyFolder($folder . 'device' . DIRECTORY_SEPARATOR, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if ($success == 0) {
                // old plugin and theme using this
                $check = strtolower($ghUrl);
                if (strpos($check, 'plugin') !== false) {
                    File::copyFolder($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                } else if (strpos($check, 'payment') !== false) {
                    File::copyFolder($folder, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                } else if (strpos($check, 'theme') !== false) {
                    rename($folder, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $plugin);
                } else if (strpos($check, 'device') !== false) {
                    File::copyFolder($folder, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                }
            }
            if ($ai_chatbot_detected) {
                $plugin_main = $PLUGIN_PATH . DIRECTORY_SEPARATOR . 'ai_chatbot.php';
                if (file_exists($plugin_main)) {
                    include_once $plugin_main;
                }
                if (function_exists('ai_chatbot_plugin_install')) {
                    ai_chatbot_plugin_install();
                }
            }
            File::deleteFolder($cache);
            r2(getUrl('pluginmanager'), 's', 'Installation success');
        } else {
            r2(getUrl('pluginmanager'), 'e', 'Nothing Installed');
        }
        break;
    case 'delete':
        if ($_app_stage == 'Demo') {
            r2(getUrl('pluginmanager'), 'e', 'You cannot perform this action in Demo mode');
        }
        if (!is_writeable($CACHE_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder cache/ is not writable');
        }
        if (!is_writeable($PLUGIN_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder plugin/ is not writable');
        }
        set_time_limit(-1);
        $tipe = $routes['2'];
        $plugin = $routes['3'];
        $file = $CACHE_PATH . DIRECTORY_SEPARATOR . $plugin . '.zip';
        if (file_exists($file))
            unlink($file);
        if ($tipe == 'plugin') {
            if ($plugin === 'ai-chatbot-phpnuxbill-plugin') {
                $plugin_main = $PLUGIN_PATH . DIRECTORY_SEPARATOR . 'ai_chatbot.php';
                if (file_exists($plugin_main)) {
                    include_once $plugin_main;
                }
                if (function_exists('ai_chatbot_plugin_uninstall')) {
                    ai_chatbot_plugin_uninstall(true);
                }
            }
            foreach ($json['plugins'] as $plg) {
                if ($plg['id'] == $plugin) {
                    if (!empty($config['github_token']) && !empty($config['github_username'])) {
                        $plg['github'] = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $plg['github']);
                    }
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($plg['github'] . '/archive/refs/heads/master.zip');
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    $zip = new ZipArchive();
                    $zip->open($file);
                    $zip->extractTo($CACHE_PATH);
                    $zip->close();
                    $folder = resolveExtractedFolder($CACHE_PATH, $plugin);
                    if ($folder === null) {
                        r2(getUrl('pluginmanager'), 'e', 'Extracted Folder is unknown');
                    }
                    scanAndRemovePath($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                    File::deleteFolder($folder);
                    unlink($file);
                    r2(getUrl('pluginmanager'), 's', 'Plugin ' . $plugin . ' has been deleted');
                    break;
                }
            }
            break;
        }
        break;
    case 'install':
        if ($_app_stage == 'Demo') {
            r2(getUrl('pluginmanager'), 'e', 'You cannot perform this action in Demo mode');
        }
        if (!is_writeable($CACHE_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder cache/ is not writable');
        }
        if (!is_writeable($PLUGIN_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder plugin/ is not writable');
        }
        set_time_limit(-1);
        $tipe = $routes['2'];
        $plugin = $routes['3'];
        $file = $CACHE_PATH . DIRECTORY_SEPARATOR . $plugin . '.zip';
        if (file_exists($file))
            unlink($file);
        if ($tipe == 'plugin') {
            foreach ($json['plugins'] as $plg) {
                if ($plg['id'] == $plugin) {
                    if (!empty($config['github_token']) && !empty($config['github_username'])) {
                        $plg['github'] = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $plg['github']);
                    }
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($plg['github'] . '/archive/refs/heads/master.zip');
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    $zip = new ZipArchive();
                    $zip->open($file);
                    $zip->extractTo($CACHE_PATH);
                    $zip->close();
                    $folder = resolveExtractedFolder($CACHE_PATH, $plugin);
                    if ($folder === null) {
                        r2(getUrl('pluginmanager'), 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                    if ($plugin === 'ai-chatbot-phpnuxbill-plugin') {
                        $plugin_main = $PLUGIN_PATH . DIRECTORY_SEPARATOR . 'ai_chatbot.php';
                        if (file_exists($plugin_main)) {
                            include_once $plugin_main;
                        }
                        if (function_exists('ai_chatbot_plugin_install')) {
                            ai_chatbot_plugin_install();
                        }
                    }
                    File::deleteFolder($folder);
                    unlink($file);
                    r2(getUrl('pluginmanager'), 's', 'Plugin ' . $plugin . ' has been installed');
                    break;
                }
            }
            break;
        } else if ($tipe == 'payment') {
            foreach ($json['payment_gateway'] as $plg) {
                if ($plg['id'] == $plugin) {
                    if (!empty($config['github_token']) && !empty($config['github_username'])) {
                        $plg['github'] = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $plg['github']);
                    }
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($plg['github'] . '/archive/refs/heads/master.zip');
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    $zip = new ZipArchive();
                    $zip->open($file);
                    $zip->extractTo($CACHE_PATH);
                    $zip->close();
                    $folder = resolveExtractedFolder($CACHE_PATH, $plugin);
                    if ($folder === null) {
                        r2(getUrl('pluginmanager'), 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                    File::deleteFolder($folder);
                    unlink($file);
                    r2(getUrl('paymentgateway'), 's', 'Payment Gateway ' . $plugin . ' has been installed');
                    break;
                }
            }
            break;
        } else if ($tipe == 'device') {
            foreach ($json['devices'] as $d) {
                if ($d['id'] == $plugin) {
                    if (!empty($config['github_token']) && !empty($config['github_username'])) {
                        $d['github'] = str_replace('https://github.com', 'https://' . $config['github_username'] . ':' . $config['github_token'] . '@github.com', $d['github']);
                    }
                    $fp = fopen($file, 'w+');
                    $ch = curl_init($d['github'] . '/archive/refs/heads/master.zip');
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    $zip = new ZipArchive();
                    $zip->open($file);
                    $zip->extractTo($CACHE_PATH);
                    $zip->close();
                    $folder = resolveExtractedFolder($CACHE_PATH, $plugin);
                    if ($folder === null) {
                        r2(getUrl('pluginmanager'), 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, $DEVICE_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                    File::deleteFolder($folder);
                    unlink($file);
                    r2(getUrl('settings/devices'), 's', 'Device ' . $plugin . ' has been installed');
                    break;
                }
            }
            break;
        }
    default:
        if (class_exists('ZipArchive')) {
            $zipExt = true;
        } else {
            $zipExt = false;
        }
        $ui->assign('zipExt', $zipExt);
        $ui->assign('repoError', $repoError);
        $ui->assign('plugins', $json['plugins']);
        $ui->assign('pgs', $json['payment_gateway']);
        $ui->assign('dvcs', $json['devices']);
        $ui->display('admin/settings/plugin-manager.tpl');
}


/**
 * Locate the extracted repository folder regardless of case or branch suffix.
 */
function resolveExtractedFolder($basePath, $plugin)
{
    $normalizedBase = rtrim($basePath, '\/') . DIRECTORY_SEPARATOR;
    $candidates = [
        $normalizedBase . $plugin . '-main' . DIRECTORY_SEPARATOR,
        $normalizedBase . $plugin . '-master' . DIRECTORY_SEPARATOR,
    ];

    foreach ($candidates as $candidate) {
        if (is_dir($candidate)) {
            return rtrim($candidate, '\/') . DIRECTORY_SEPARATOR;
        }
    }

    if (!is_dir($normalizedBase)) {
        return null;
    }

    $entries = scandir($normalizedBase);
    $pluginLower = strtolower($plugin);

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $fullPath = $normalizedBase . $entry;
        if (!is_dir($fullPath)) {
            continue;
        }
        $entryLower = strtolower($entry);
        if ($entryLower === $pluginLower . '-main' || $entryLower === $pluginLower . '-master' || strpos($entryLower, $pluginLower) === 0) {
            return rtrim($fullPath, '\/') . DIRECTORY_SEPARATOR;
        }
    }

    return null;
}

function scanAndRemovePath($source, $target)
{
    $files = scandir($source);
    foreach ($files as $file) {
        if (is_file($source . $file)) {
            if (file_exists($target . $file)) {
                unlink($target . $file);
            }
        } else if (is_dir($source . $file) && !in_array($file, ['.', '..'])) {
            scanAndRemovePath($source . $file . DIRECTORY_SEPARATOR, $target . $file . DIRECTORY_SEPARATOR);
            if (file_exists($target . $file)) {
                rmdir($target . $file);
            }
        }
    }
    if (file_exists($target)) {
        rmdir($target);
    }
}

function pluginRepositoryReadPayload($filePath)
{
    if (!is_file($filePath)) {
        return null;
    }

    $raw = @file_get_contents($filePath);
    if (!is_string($raw) || $raw === '') {
        return null;
    }

    return ltrim($raw, "\xEF\xBB\xBF");
}

function pluginRepositoryDecodeData($payload)
{
    if (!is_string($payload) || $payload === '') {
        return null;
    }

    $decoded = json_decode($payload, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return null;
    }

    return [
        'plugins' => isset($decoded['plugins']) && is_array($decoded['plugins']) ? array_values($decoded['plugins']) : [],
        'payment_gateway' => isset($decoded['payment_gateway']) && is_array($decoded['payment_gateway']) ? array_values($decoded['payment_gateway']) : [],
        'devices' => isset($decoded['devices']) && is_array($decoded['devices']) ? array_values($decoded['devices']) : [],
    ];
}

function pluginRepositorySyncMergedFile($upstreamUrl, $repositoryFile, $customFile, &$error = '')
{
    $error = '';
    $upstreamRaw = Http::getData($upstreamUrl);
    if ($upstreamRaw === false || $upstreamRaw === null) {
        $error = 'Unable to reach upstream plugin repository.';
        return null;
    }

    $upstreamPayload = ltrim((string) $upstreamRaw, "\xEF\xBB\xBF");
    $upstreamData = pluginRepositoryDecodeData($upstreamPayload);
    if ($upstreamData === null) {
        $error = 'Invalid upstream plugin repository response.';
        return null;
    }

    $customData = pluginRepositoryLoadCustomOverrides($upstreamData, $repositoryFile, $customFile);
    $mergedData = pluginRepositoryMergeData($upstreamData, $customData);
    $mergedPayload = json_encode(
        $mergedData,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    if ($mergedPayload === false) {
        $error = 'Unable to encode merged plugin repository.';
        return null;
    }

    $mergedPayload .= "\n";
    @file_put_contents($repositoryFile, $mergedPayload, LOCK_EX);
    return $mergedPayload;
}

function pluginRepositoryLoadCustomOverrides($upstreamData, $repositoryFile, $customFile)
{
    $empty = [
        'plugins' => [],
        'payment_gateway' => [],
        'devices' => [],
    ];

    $customPayload = pluginRepositoryReadPayload($customFile);
    if ($customPayload !== null) {
        $decodedCustom = pluginRepositoryDecodeData($customPayload);
        if ($decodedCustom !== null) {
            return $decodedCustom;
        }
    }

    // Compatibility for old setup where manual edits were directly saved in plugin-repository.json.
    $legacyPayload = pluginRepositoryReadPayload($repositoryFile);
    if ($legacyPayload === null) {
        return $empty;
    }

    $legacyData = pluginRepositoryDecodeData($legacyPayload);
    if ($legacyData === null) {
        return $empty;
    }

    $overrides = pluginRepositoryExtractOverrides($upstreamData, $legacyData);
    if (empty($overrides['plugins']) && empty($overrides['payment_gateway']) && empty($overrides['devices'])) {
        return $empty;
    }

    $payload = json_encode(
        $overrides,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    if ($payload !== false) {
        @file_put_contents($customFile, $payload . "\n", LOCK_EX);
    }

    return $overrides;
}

function pluginRepositoryExtractOverrides($upstreamData, $legacyData)
{
    $sections = ['plugins', 'payment_gateway', 'devices'];
    $overrides = [
        'plugins' => [],
        'payment_gateway' => [],
        'devices' => [],
    ];

    foreach ($sections as $section) {
        $upstreamMap = [];
        foreach ($upstreamData[$section] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $upstreamMap[$id] = $row;
        }

        foreach ($legacyData[$section] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            if (!isset($upstreamMap[$id]) || !pluginRepositoryRowsAreEqual($row, $upstreamMap[$id])) {
                $overrides[$section][] = $row;
            }
        }
    }

    return $overrides;
}

function pluginRepositoryMergeData($upstreamData, $customData)
{
    return [
        'plugins' => pluginRepositorySortItemsByLatest(
            pluginRepositoryMergeSection($upstreamData['plugins'], $customData['plugins'])
        ),
        'payment_gateway' => pluginRepositorySortItemsByLatest(
            pluginRepositoryMergeSection($upstreamData['payment_gateway'], $customData['payment_gateway'])
        ),
        'devices' => pluginRepositorySortItemsByLatest(
            pluginRepositoryMergeSection($upstreamData['devices'], $customData['devices'])
        ),
    ];
}

function pluginRepositoryMergeSection($upstreamRows, $customRows)
{
    $customById = [];
    foreach ((array) $customRows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = trim((string) ($row['id'] ?? ''));
        if ($id === '') {
            continue;
        }
        $customById[$id] = $row;
    }

    $merged = [];
    foreach ((array) $upstreamRows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = trim((string) ($row['id'] ?? ''));
        if ($id !== '' && isset($customById[$id])) {
            $merged[] = $customById[$id];
            unset($customById[$id]);
        } else {
            $merged[] = $row;
        }
    }

    foreach ($customById as $row) {
        $merged[] = $row;
    }

    return array_values($merged);
}

function pluginRepositoryRowsAreEqual($left, $right)
{
    if (!is_array($left) || !is_array($right)) {
        return false;
    }

    $normalizedLeft = pluginRepositoryNormalizeRowForCompare($left);
    $normalizedRight = pluginRepositoryNormalizeRowForCompare($right);
    return json_encode($normalizedLeft) === json_encode($normalizedRight);
}

function pluginRepositoryNormalizeRowForCompare($row)
{
    if (!is_array($row)) {
        return [];
    }
    ksort($row);
    return $row;
}

function pluginRepositorySortItemsByLatest($items)
{
    $rows = array_values(array_filter((array) $items, function ($item) {
        return is_array($item);
    }));

    usort($rows, function ($left, $right) {
        $leftTs = pluginRepositoryParseDateValue($left['last_update'] ?? '');
        $rightTs = pluginRepositoryParseDateValue($right['last_update'] ?? '');
        if ($leftTs === $rightTs) {
            $leftName = strtolower(trim((string) ($left['name'] ?? '')));
            $rightName = strtolower(trim((string) ($right['name'] ?? '')));
            return strcmp($leftName, $rightName);
        }
        return ($leftTs < $rightTs) ? 1 : -1;
    });

    return $rows;
}

function pluginRepositoryParseDateValue($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return 0;
    }

    $time = strtotime($value);
    if ($time !== false) {
        return (int) $time;
    }

    if (preg_match('/(\d{4})\D+(\d{1,2})\D+(\d{1,2})/', $value, $m)) {
        $year = (int) $m[1];
        $month = (int) $m[2];
        $day = (int) $m[3];
        if ($year > 0 && $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
            return (int) mktime(0, 0, 0, $month, $day, $year);
        }
    }

    return 0;
}
