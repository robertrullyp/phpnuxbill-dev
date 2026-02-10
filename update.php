<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * This script is for updating PHPNuxBill
 **/
session_start();
include "config.php";

if (!isset($GLOBALS['update_replace_registered'])) {
    register_shutdown_function('finalizeDeferredReplacements');
    $GLOBALS['update_replace_registered'] = true;
}

processDeferredReplacements();

if($db_password != null && ($db_pass == null || empty($db_pass))){
    // compability for old version
    $db_pass = $db_password;
}

// Always use the default, secure update URL. Do not allow override from user input.
$update_url = 'https://github.com/robertrullyp/phpnuxbill-dev/archive/refs/heads/main.zip';

if (!isset($_SESSION['aid']) || empty($_SESSION['aid'])) {
    r2("./?_route=login&You_are_not_admin", 'e', 'You are not admin');
}

set_time_limit(-1);

if (!is_writeable(pathFixer('system/cache/'))) {
    r2("./?_route=community", 'e', 'Folder system/cache/ is not writable');
}
if (!is_writeable(pathFixer('.'))) {
    r2("./?_route=community", 'e', 'Folder web is not writable');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;
if ($step <= 1) {
    unset($_SESSION['update_extract_dir']);
}
$continue = true;
if (!extension_loaded('zip')) {
    $msg = "No PHP ZIP extension is available";
    $msgType = "danger";
    $continue = false;
}


$currentStep = $step;
$displayStep = $step > 0 ? $step : 1;
$nextStep = $step <= 0 ? 1 : $step;
$progressTotalSteps = 5;
$completedSteps = 0;
$progressPercent = 0;
$progressAnimateTo = 0;


$file = pathFixer('system/cache/phpnuxbill.zip');
// Detect repo name from update_url (supports both upstream and fork)
$repo = 'phpnuxbill';
$urlPath = parse_url($update_url, PHP_URL_PATH);
if (!empty($urlPath)) {
    $parts = explode('/', trim($urlPath, '/'));
    if (count($parts) >= 2 && !empty($parts[1])) {
        $repo = $parts[1]; // e.g. phpnuxbill or phpnuxbill-dev
    }
}
$zipBase = basename($update_url, ".zip"); // e.g. main, master, or commit SHA
$folder = normalizeDir('system/cache/' . $repo . '-' . $zipBase);
if (!empty($_SESSION['update_extract_dir'])) {
    $sessionFolder = normalizeDir($_SESSION['update_extract_dir']);
    if (is_dir($sessionFolder)) {
        $folder = $sessionFolder;
    } else {
        unset($_SESSION['update_extract_dir']);
    }
}

if (empty($step)) {
    $displayStep = 1;
} else if ($step == 1) {
    if (file_exists($file)) unlink($file);

    // Download update
    $fp = fopen($file, 'w+');
    $ch = curl_init($update_url);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 600);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    if (file_exists($file)) {
        $nextStep = 2;
    } else {
        $msg = "Failed to download Update file";
        $msgType = "danger";
        $continue = false;
    }
} else if ($step == 2) {
    $zip = new ZipArchive();
    $zip->open($file);
    $zip->extractTo(pathFixer('system/cache/'));
    $zip->close();

    $folder = resolveExtractedFolder($repo, $zipBase, $folder);

    if (is_dir($folder)) {
        $nextStep = 3;
    } else {
        unset($_SESSION['update_extract_dir']);
        $msg = "Failed to extract update file";
        $msgType = "danger";
        $continue = false;
    }
    // remove downloaded zip
    if (file_exists($file)) unlink($file);
} else if ($step == 3) {
    // Step 3: Create Backup
    $backupDir = pathFixer('system/backup/');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    $backupFile = $backupDir . 'pre_update_backup_' . time() . '.zip';
    $_SESSION['backup_file'] = $backupFile;

    try {
        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception("Cannot open zip archive for writing: " . $backupFile);
        }

        zipFolder(pathFixer('.'), $zip, strlen(pathFixer('.')));

        $zip->close();
        $dbBackupFile = createDatabaseBackup($backupDir);
        if (!empty($dbBackupFile)) {
            $_SESSION['db_backup_file'] = $dbBackupFile;
        }
        $nextStep = 4;
    } catch (Exception $e) {
        $msg = "Failed to create backup: " . $e->getMessage();
        $msgType = "danger";
        $continue = false;
    }

} else if ($step == 4) {
    // Step 4: Install (previously step 3)
    $preservePaths = [
        'config.php',
        'config.local.php',
        '.env',
        '.env.local',
        'system/cache',
        'system/backup',
        'system/uploads',
        'system/logs',
        'system/tmp',
        'ui/cache',
        'ui/compiled',
        'ui/ui_custom',
        'ui/uploads',
    ];
    $replaceDirs = [
        'system/autoload',
        'system/vendor',
        'ui/ui',
    ];
    $preparedBackups = [];
    $folder = resolveExtractedFolder($repo, $zipBase, $folder);
    try {
        if (!is_dir($folder)) {
            throw new Exception('Source path does not exist: ' . $folder);
        }
        foreach ($replaceDirs as $dir) {
            $original = rtrim(pathFixer($dir), DIRECTORY_SEPARATOR);
            if (!is_dir($original)) {
                continue;
            }
            $backup = $original . '.update-backup';
            if (is_dir($backup)) {
                deleteFolder(rtrim($backup, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
            }
            if (!@rename($original, $backup)) {
                throw new Exception('Unable to prepare directory for update: ' . $dir);
            }
            $preparedBackups[$backup] = $original;
        }

        copyFolder($folder, pathFixer('./'), $preservePaths);

        if (is_dir(pathFixer('install/'))) {
            deleteFolder(pathFixer('install/'));
        }

        foreach ($replaceDirs as $dir) {
            $expected = rtrim(pathFixer($dir), DIRECTORY_SEPARATOR);
            if (!is_dir($expected)) {
                throw new Exception('Missing required directory after update: ' . $dir);
            }
        }

        foreach ($preparedBackups as $backup => $original) {
            if (is_dir($backup)) {
                deleteFolder(rtrim($backup, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
            }
        }

        if (file_exists($folder)) {
            deleteFolder($folder);
        }

        unset($_SESSION['update_extract_dir']);

        if (!file_exists($folder)) {
            $nextStep = 5;
        } else {
            throw new Exception('Failed to remove temporary update directory.');
        }
    } catch (Exception $e) {
        foreach ($preparedBackups as $backup => $original) {
            if (is_dir($backup) && !is_dir($original)) {
                @rename($backup, $original);
            } else if (is_dir($backup)) {
                deleteFolder(rtrim($backup, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
            }
        }

        if (file_exists($folder)) {
            deleteFolder($folder);
        }

        unset($_SESSION['update_extract_dir']);

        $msg = 'Failed to install update files: ' . $e->getMessage() . ' A backup has been created at: ' . htmlspecialchars($_SESSION['backup_file']);
        $msgType = 'danger';
        $continue = false;
    }
} else if ($step == 5) {
    // Step 5: Database Update (previously step 4)
    try {
        $db = new pdo(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );

        if (file_exists("system/updates.json")) {
            $updates = json_decode(file_get_contents("system/updates.json"), true);
            if (!is_array($updates)) {
                throw new Exception('Invalid update manifest: system/updates.json');
            }

            $doneFile = "system/cache/updates.done.json";
            $dones = updateReadDoneVersions($doneFile);

            foreach ($updates as $version => $queries) {
                $version = trim((string) $version);
                if ($version === '') {
                    continue;
                }
                if (in_array($version, $dones, true)) {
                    continue;
                }

                if (!is_array($queries)) {
                    $queries = [];
                }

                foreach ($queries as $q) {
                    $q = trim((string) $q);
                    if ($q === '') {
                        continue;
                    }
                    try {
                        $db->exec($q);
                    } catch (PDOException $e) {
                        if (!updateIsIgnorableMigrationError($e)) {
                            throw new Exception(
                                'Database migration failed at version ' . $version . ': ' . $e->getMessage(),
                                0,
                                $e
                            );
                        }
                    }
                }

                $dones[] = $version;
                updateWriteDoneVersions($doneFile, $dones);
            }
        }

        runCoreSchemaHardening($db);
        runUserHierarchyCleanupMigration($db);
    } catch (Exception $e) {
        $backupHint = '';
        if (!empty($_SESSION['backup_file'])) {
            $backupHint = ". A backup has been created at: " . htmlspecialchars($_SESSION['backup_file']);
        }
        $msg = "Failed to update database: " . $e->getMessage() . $backupHint;
        $msgType = "danger";
        $continue = false;
    }
    if ($continue) {
        $nextStep = 6;
    }
} else if ($step == 6) {
    // Step 6: Finish (previously step 5)
    // Clear compiled cache
    $path = 'ui/compiled/';
    if (is_dir($path)) {
        $files = scandir($path);
        foreach ($files as $file) {
            if (is_file($path . $file)) {
                unlink($path . $file);
            }
        }
    }

    // Keep backup files for safety (clean manually if needed)
    $backupFile = '';
    if (!empty($_SESSION['backup_file']) && file_exists($_SESSION['backup_file'])) {
        $backupFile = $_SESSION['backup_file'];
    }
    $dbBackupFile = '';
    if (!empty($_SESSION['db_backup_file']) && file_exists($_SESSION['db_backup_file'])) {
        $dbBackupFile = $_SESSION['db_backup_file'];
    }
    unset($_SESSION['backup_file'], $_SESSION['db_backup_file']);

    $versionLabel = null;
    if (is_file('version.json')) {
        $versionData = json_decode(file_get_contents('version.json'), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($versionData) && !empty($versionData['version'])) {
            $versionLabel = $versionData['version'];
        }
    }

    $message = 'PHPNuxBill has been updated successfully.';
    if (!empty($versionLabel)) {
        $message = 'PHPNuxBill has been updated to Version ' . $versionLabel;
    }
    if ($backupFile !== '' || $dbBackupFile !== '') {
        $details = [];
        if ($backupFile !== '') {
            $details[] = 'Files: ' . basename($backupFile);
        }
        if ($dbBackupFile !== '') {
            $details[] = 'DB: ' . basename($dbBackupFile);
        }
        $message .= ' (Backups kept in system/backup/ — ' . implode(', ', $details) . ')';
    }

    $redirectTargets = [
        'dashboard' => './?_route=dashboard',
        'community' => './?_route=community',
    ];
    $requestedRedirect = isset($_GET['redirect_to']) ? strtolower($_GET['redirect_to']) : '';
    $target = $redirectTargets['dashboard'];
    if (!empty($requestedRedirect) && isset($redirectTargets[$requestedRedirect])) {
        $target = $redirectTargets[$requestedRedirect];
    }

    r2($target, 's', $message);
}

if ($continue) {
    if ($nextStep > $currentStep) {
        $completedSteps = max(0, min($progressTotalSteps, $nextStep - 1));
    } else {
        $completedSteps = max(0, min($progressTotalSteps, $currentStep));
    }
} else {
    $completedSteps = max(0, min($progressTotalSteps, $currentStep - 1));
}

if ($progressTotalSteps > 0) {
    $progressPercent = (int) round(($completedSteps / $progressTotalSteps) * 100);
}

$progressAnimateTo = $progressPercent;
if ($continue && $nextStep !== $currentStep && $progressAnimateTo < 100) {
    $progressAnimateTo = min(99, $progressAnimateTo + 12);
}

function pathFixer($path)
{
    return str_replace("/", DIRECTORY_SEPARATOR, $path);
}

function normalizeDir($path)
{
    $normalized = pathFixer($path);
    return rtrim($normalized, '\/') . DIRECTORY_SEPARATOR;
}

function resolveExtractedFolder($repo, $zipBase, $preferred)
{
    $preferred = normalizeDir($preferred);
    if (is_dir($preferred)) {
        $_SESSION['update_extract_dir'] = $preferred;
        return $preferred;
    }

    if (!empty($_SESSION['update_extract_dir'])) {
        $sessionFolder = normalizeDir($_SESSION['update_extract_dir']);
        if (is_dir($sessionFolder)) {
            return $sessionFolder;
        }
        unset($_SESSION['update_extract_dir']);
    }

    $cacheBase = normalizeDir('system/cache/');
    $candidates = [
        normalizeDir($cacheBase . $repo . '-' . $zipBase),
        normalizeDir($cacheBase . $repo . '-main'),
        normalizeDir($cacheBase . $repo . '-master'),
    ];

    foreach ($candidates as $candidate) {
        if (is_dir($candidate)) {
            $_SESSION['update_extract_dir'] = $candidate;
            return $candidate;
        }
    }

    $latestFolder = '';
    $latestTime = 0;
    if (is_dir($cacheBase)) {
        $entries = scandir($cacheBase);
        if ($entries !== false) {
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (strpos($entry, $repo . '-') !== 0) {
                    continue;
                }
                $fullPath = normalizeDir($cacheBase . $entry);
                if (is_dir($fullPath)) {
                    $modified = filemtime($fullPath);
                    if ($modified > $latestTime) {
                        $latestTime = $modified;
                        $latestFolder = $fullPath;
                    }
                }
            }
        }
    }

    if (!empty($latestFolder)) {
        $_SESSION['update_extract_dir'] = $latestFolder;
        return $latestFolder;
    }

    return $preferred;
}

function scheduleDeferredReplacement($source, $target)
{
    $job = [
        'source' => pathFixer($source),
        'target' => pathFixer($target),
    ];

    if (!isset($_SESSION['update_pending_replace']) || !is_array($_SESSION['update_pending_replace'])) {
        $_SESSION['update_pending_replace'] = [];
    }

    $_SESSION['update_pending_replace'] = array_values(array_filter(
        $_SESSION['update_pending_replace'],
        function ($existing) use ($job) {
            return !isset($existing['target']) || $existing['target'] !== $job['target'];
        }
    ));

    $_SESSION['update_pending_replace'][] = $job;
}

function processDeferredReplacements()
{
    if (empty($_SESSION['update_pending_replace']) || !is_array($_SESSION['update_pending_replace'])) {
        return;
    }

    $remaining = [];

    foreach ($_SESSION['update_pending_replace'] as $job) {
        if (empty($job['source']) || empty($job['target'])) {
            continue;
        }

        $source = pathFixer($job['source']);
        $target = pathFixer($job['target']);

        if (!file_exists($source)) {
            continue;
        }

        $targetDir = dirname($target);
        if ($targetDir !== '' && !is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                $remaining[] = $job;
                continue;
            }
        }

        $backup = $target . '.update-backup';
        $replaced = false;

        if (file_exists($target)) {
            if (!@rename($target, $backup)) {
                $remaining[] = $job;
                if (file_exists($backup) && !file_exists($target)) {
                    @rename($backup, $target);
                }
                continue;
            }
        }

        if (@rename($source, $target)) {
            $replaced = true;
        } else {
            if (@copy($source, $target)) {
                $replaced = true;
            }
        }

        if ($replaced) {
            if (file_exists($backup)) {
                @unlink($backup);
            }
            if (file_exists($source)) {
                @unlink($source);
            }
        } else {
            if (file_exists($backup)) {
                @rename($backup, $target);
            }
            $remaining[] = $job;
        }
    }

    if (!empty($remaining)) {
        $_SESSION['update_pending_replace'] = $remaining;
    } else {
        unset($_SESSION['update_pending_replace']);
    }
}

function finalizeDeferredReplacements()
{
    processDeferredReplacements();
}

function r2($to, $ntype = 'e', $msg = '')
{

    if ($msg == '') {
        header("location: $to");
        die();
    }
    $_SESSION['ntype'] = $ntype;
    $_SESSION['notify'] = $msg;
    header("location: $to");
    die();
}

function updateReadDoneVersions($filePath)
{
    if (!file_exists($filePath)) {
        return [];
    }

    $raw = json_decode((string) file_get_contents($filePath), true);
    if (!is_array($raw)) {
        return [];
    }

    $doneMap = [];
    foreach ($raw as $version) {
        $version = trim((string) $version);
        if ($version !== '') {
            $doneMap[$version] = true;
        }
    }

    return array_keys($doneMap);
}

function updateWriteDoneVersions($filePath, $versions)
{
    $doneMap = [];
    foreach ((array) $versions as $version) {
        $version = trim((string) $version);
        if ($version !== '') {
            $doneMap[$version] = true;
        }
    }

    $payload = json_encode(
        array_keys($doneMap),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    if ($payload === false) {
        throw new Exception('Failed to encode update marker file.');
    }

    if (file_put_contents($filePath, $payload) === false) {
        throw new Exception('Failed to write update marker file: ' . $filePath);
    }
}

function updateIsIgnorableMigrationError($e)
{
    if (!($e instanceof PDOException)) {
        return false;
    }

    $driverCode = 0;
    if (!empty($e->errorInfo) && isset($e->errorInfo[1])) {
        $driverCode = (int) $e->errorInfo[1];
    }

    if (in_array($driverCode, [1050, 1051, 1054, 1060, 1061, 1062, 1068, 1091, 1146], true)) {
        return true;
    }

    $message = strtolower((string) $e->getMessage());
    $knownFragments = [
        'already exists',
        'duplicate column name',
        'duplicate entry',
        'duplicate key name',
        'multiple primary key defined',
        'can\'t drop',
        'unknown column',
        'unknown table',
        'table doesn\'t exist',
    ];
    foreach ($knownFragments as $fragment) {
        if (strpos($message, $fragment) !== false) {
            return true;
        }
    }

    return false;
}

function updateTableExists($db, $table)
{
    try {
        $stmt = $db->prepare("SELECT 1 FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = :name LIMIT 1");
        $stmt->execute([':name' => $table]);
        return (bool) $stmt->fetch(PDO::FETCH_NUM);
    } catch (Throwable $e) {
        $stmt = $db->prepare("SHOW TABLES LIKE :name");
        $stmt->execute([':name' => $table]);
        return (bool) $stmt->fetch(PDO::FETCH_NUM);
    }
}

function updateColumnExists($db, $table, $column)
{
    $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE :name");
    $stmt->execute([':name' => $column]);
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateIndexExists($db, $table, $indexName)
{
    $stmt = $db->prepare("SHOW INDEX FROM `{$table}` WHERE `Key_name` = :name");
    $stmt->execute([':name' => $indexName]);
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateHasAutoIncrement($db, $table, $column)
{
    $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE :name");
    $stmt->execute([':name' => $column]);
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        return false;
    }
    $extra = strtolower((string) ($col['Extra'] ?? ''));
    return strpos($extra, 'auto_increment') !== false;
}

function updateEnsureAppConfigValue($db, $setting, $value)
{
    if (!updateTableExists($db, 'tbl_appconfig')) {
        return;
    }
    if (!updateColumnExists($db, 'tbl_appconfig', 'setting') || !updateColumnExists($db, 'tbl_appconfig', 'value')) {
        return;
    }

    $find = $db->prepare("SELECT `setting` FROM `tbl_appconfig` WHERE `setting` = :setting LIMIT 1");
    $find->execute([':setting' => $setting]);
    if ($find->fetch(PDO::FETCH_ASSOC)) {
        return;
    }

    $insert = $db->prepare("INSERT INTO `tbl_appconfig` (`setting`, `value`) VALUES (:setting, :value)");
    $insert->execute([
        ':setting' => (string) $setting,
        ':value' => (string) $value,
    ]);
}

function runCoreSchemaHardening($db)
{
    if (updateTableExists($db, 'tbl_customers')) {
        if (!updateColumnExists($db, 'tbl_customers', 'account_manager_id')) {
            $db->exec("ALTER TABLE `tbl_customers` ADD `account_manager_id` INT NOT NULL DEFAULT '0' COMMENT '0 means all users can access' AFTER `created_by`;");
        }
        if (updateColumnExists($db, 'tbl_customers', 'service_type')) {
            $db->exec("ALTER TABLE `tbl_customers` CHANGE `service_type` `service_type` ENUM('Hotspot','PPPoE','VPN','Others') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Others' COMMENT 'For selecting user type';");
        }
    }

    if (updateTableExists($db, 'tbl_plans')) {
        if (updateColumnExists($db, 'tbl_plans', 'type')) {
            $db->exec("ALTER TABLE `tbl_plans` CHANGE `type` `type` ENUM('Hotspot','PPPOE','VPN','Balance') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;");
        }

        if (!updateColumnExists($db, 'tbl_plans', 'prepaid')) {
            if (updateColumnExists($db, 'tbl_plans', 'allow_purchase')) {
                $db->exec("ALTER TABLE `tbl_plans` CHANGE `allow_purchase` `prepaid` ENUM('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'yes' COMMENT 'is prepaid';");
            } else {
                $db->exec("ALTER TABLE `tbl_plans` ADD `prepaid` ENUM('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'yes' COMMENT 'is prepaid' AFTER `enabled`;");
            }
        }
    }

    if (updateTableExists($db, 'tbl_transactions') && updateColumnExists($db, 'tbl_transactions', 'type')) {
        $db->exec("ALTER TABLE `tbl_transactions` CHANGE `type` `type` ENUM('Hotspot','PPPOE','VPN','Balance') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;");
    }

    if (updateTableExists($db, 'tbl_voucher') && !updateColumnExists($db, 'tbl_voucher', 'batch_name')) {
        $db->exec("ALTER TABLE `tbl_voucher` ADD `batch_name` VARCHAR(40) AFTER `id_plan`;");
    }

    if (!updateTableExists($db, 'tbl_invoices')) {
        $db->exec("CREATE TABLE IF NOT EXISTS `tbl_invoices` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `number` VARCHAR(50) NOT NULL, `customer_id` INT NOT NULL, `fullname` VARCHAR(100) NOT NULL, `email` VARCHAR(100) NOT NULL, `address` TEXT, `status` ENUM('Unpaid', 'Paid', 'Cancelled') NOT NULL DEFAULT 'Unpaid', `due_date` DATETIME NOT NULL, `filename` VARCHAR(255), `amount` DECIMAL(10, 2) NOT NULL, `data` JSON NOT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    }

    if (updateTableExists($db, 'tbl_plan_customers')) {
        if (!updateIndexExists($db, 'tbl_plan_customers', 'PRIMARY')) {
            $db->exec("ALTER TABLE `tbl_plan_customers` ADD PRIMARY KEY (`id`);");
        }
        if (!updateIndexExists($db, 'tbl_plan_customers', 'plan_customer_unique')) {
            $db->exec("ALTER TABLE `tbl_plan_customers` ADD UNIQUE KEY `plan_customer_unique` (`plan_id`,`customer_id`);");
        }
        if (!updateIndexExists($db, 'tbl_plan_customers', 'idx_plan_id')) {
            $db->exec("ALTER TABLE `tbl_plan_customers` ADD KEY `idx_plan_id` (`plan_id`);");
        }
        if (!updateIndexExists($db, 'tbl_plan_customers', 'idx_customer_id')) {
            $db->exec("ALTER TABLE `tbl_plan_customers` ADD KEY `idx_customer_id` (`customer_id`);");
        }
        if (updateColumnExists($db, 'tbl_plan_customers', 'id') && !updateHasAutoIncrement($db, 'tbl_plan_customers', 'id')) {
            $db->exec("ALTER TABLE `tbl_plan_customers` MODIFY `id` int NOT NULL AUTO_INCREMENT;");
        }
    }

    updateEnsureAppConfigValue($db, 'genieacs_enable', 'no');
    updateEnsureAppConfigValue($db, 'genieacs_url', '');
}

function updateCleanupNormalizeIds($value)
{
    if (is_string($value)) {
        $value = trim($value);
        if ($value === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $value = $decoded;
        } else {
            $value = preg_split('/[\s,]+/', $value);
        }
    }
    if (!is_array($value)) {
        return [];
    }
    $ids = [];
    foreach ($value as $item) {
        $id = (int) $item;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

function updateCleanupParseData($raw, &$valid)
{
    if (is_array($raw)) {
        $valid = true;
        return $raw;
    }
    if ($raw === null) {
        $valid = true;
        return [];
    }
    if (!is_string($raw)) {
        $valid = false;
        return [];
    }
    $raw = trim($raw);
    if ($raw === '') {
        $valid = true;
        return [];
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        $valid = false;
        return [];
    }

    $valid = true;
    return $decoded;
}

function updateCleanupEncodeData($data)
{
    if (empty($data)) {
        return null;
    }
    $encoded = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return ($encoded === false) ? null : $encoded;
}

function updateCleanupFirstId($ids)
{
    if (empty($ids)) {
        return 0;
    }
    sort($ids, SORT_NUMERIC);
    return (int) $ids[0];
}

function updateCleanupFindAncestorByRole($startRoot, $wantedRoles, $usersById, $depthLimit = 8)
{
    $startRoot = (int) $startRoot;
    if ($startRoot < 1) {
        return 0;
    }
    $wantedMap = [];
    foreach ((array) $wantedRoles as $role) {
        $role = trim((string) $role);
        if ($role !== '') {
            $wantedMap[$role] = true;
        }
    }
    if (empty($wantedMap)) {
        return 0;
    }

    $visited = [];
    $current = $startRoot;
    for ($i = 0; $i < $depthLimit; $i++) {
        if ($current < 1 || isset($visited[$current]) || !isset($usersById[$current])) {
            return 0;
        }
        $visited[$current] = true;
        $row = $usersById[$current];
        $role = trim((string) ($row['user_type'] ?? ''));
        if (isset($wantedMap[$role])) {
            return (int) $current;
        }
        $next = (int) ($row['root'] ?? 0);
        if ($next < 1 || $next === $current) {
            return 0;
        }
        $current = $next;
    }

    return 0;
}

function updateCleanupBuildRoleIndexes($usersById)
{
    $super = [];
    $admin = [];
    $agent = [];
    $adminBySuper = [];
    $agentByAdmin = [];

    foreach ($usersById as $id => $row) {
        $id = (int) $id;
        $role = trim((string) ($row['user_type'] ?? ''));
        $root = (int) ($row['root'] ?? 0);

        if ($role === 'SuperAdmin') {
            $super[] = $id;
        } elseif ($role === 'Admin') {
            $admin[] = $id;
            if ($root > 0) {
                $adminBySuper[$root][] = $id;
            }
        } elseif ($role === 'Agent') {
            $agent[] = $id;
            if ($root > 0) {
                $agentByAdmin[$root][] = $id;
            }
        }
    }

    sort($super, SORT_NUMERIC);
    sort($admin, SORT_NUMERIC);
    sort($agent, SORT_NUMERIC);
    foreach ($adminBySuper as &$rows) {
        sort($rows, SORT_NUMERIC);
    }
    unset($rows);
    foreach ($agentByAdmin as &$rows) {
        sort($rows, SORT_NUMERIC);
    }
    unset($rows);

    return [
        'super' => $super,
        'admin' => $admin,
        'agent' => $agent,
        'admin_by_super' => $adminBySuper,
        'agent_by_admin' => $agentByAdmin,
    ];
}

function updateCleanupNormalizeRouterData($data, $role)
{
    $before = $data;
    $role = trim((string) $role);

    if ($role === 'SuperAdmin') {
        unset($data['router_assignment_mode'], $data['router_assignment_ids'], $data['router_access_mode'], $data['router_access_ids']);
        return [$data, $before !== $data];
    }

    $mode = strtolower(trim((string) ($data['router_assignment_mode'] ?? ($data['router_access_mode'] ?? 'all'))));
    if (!in_array($mode, ['all', 'list'], true)) {
        $mode = 'all';
    }
    $ids = updateCleanupNormalizeIds($data['router_assignment_ids'] ?? ($data['router_access_ids'] ?? []));
    $data['router_assignment_mode'] = $mode;
    $data['router_assignment_ids'] = $ids;
    unset($data['router_access_mode'], $data['router_access_ids']);

    return [$data, $before !== $data];
}

function updateCleanupMarkDone($db, $settingName)
{
    if (!updateTableExists($db, 'tbl_appconfig') || !updateColumnExists($db, 'tbl_appconfig', 'setting') || !updateColumnExists($db, 'tbl_appconfig', 'value')) {
        return;
    }

    $hasIdColumn = updateColumnExists($db, 'tbl_appconfig', 'id');
    if ($hasIdColumn) {
        $find = $db->prepare("SELECT `id` FROM `tbl_appconfig` WHERE `setting` = :setting ORDER BY `id` ASC LIMIT 1");
        $find->execute([':setting' => $settingName]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['id'])) {
            $update = $db->prepare("UPDATE `tbl_appconfig` SET `value` = :value WHERE `id` = :id");
            $update->execute([
                ':value' => 'done',
                ':id' => (int) $row['id'],
            ]);
            return;
        }
    } else {
        $find = $db->prepare("SELECT `setting` FROM `tbl_appconfig` WHERE `setting` = :setting LIMIT 1");
        $find->execute([':setting' => $settingName]);
        if ($find->fetch(PDO::FETCH_ASSOC)) {
            $update = $db->prepare("UPDATE `tbl_appconfig` SET `value` = :value WHERE `setting` = :setting");
            $update->execute([
                ':value' => 'done',
                ':setting' => $settingName,
            ]);
            return;
        }
    }

    try {
        $insert = $db->prepare("INSERT INTO `tbl_appconfig` (`setting`, `value`) VALUES (:setting, :value)");
        $insert->execute([
            ':setting' => $settingName,
            ':value' => 'done',
        ]);
    } catch (Exception $e) {
        // fallback for strict/legacy table definitions
        $update = $db->prepare("UPDATE `tbl_appconfig` SET `value` = :value WHERE `setting` = :setting");
        $update->execute([
            ':value' => 'done',
            ':setting' => $settingName,
        ]);
        return;
    }
}

function runUserHierarchyCleanupMigration($db)
{
    $settingName = 'user_hierarchy_cleanup_v1';
    if (!updateTableExists($db, 'tbl_users')) {
        updateCleanupMarkDone($db, $settingName);
        return;
    }
    if (!updateColumnExists($db, 'tbl_users', 'id') || !updateColumnExists($db, 'tbl_users', 'user_type') || !updateColumnExists($db, 'tbl_users', 'root')) {
        updateCleanupMarkDone($db, $settingName);
        return;
    }

    if (updateTableExists($db, 'tbl_appconfig') && updateColumnExists($db, 'tbl_appconfig', 'setting') && updateColumnExists($db, 'tbl_appconfig', 'value')) {
        $marker = $db->prepare("SELECT `value` FROM `tbl_appconfig` WHERE `setting` = :setting ORDER BY `id` ASC LIMIT 1");
        $marker->execute([':setting' => $settingName]);
        $markerRow = $marker->fetch(PDO::FETCH_ASSOC);
        if ($markerRow && trim((string) ($markerRow['value'] ?? '')) === 'done') {
            return;
        }
    }

    $hasDataColumn = updateColumnExists($db, 'tbl_users', 'data');
    if ($hasDataColumn) {
        $rows = $db->query("SELECT `id`, `user_type`, `root`, `data` FROM `tbl_users` ORDER BY `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $rows = $db->query("SELECT `id`, `user_type`, `root` FROM `tbl_users` ORDER BY `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    if (empty($rows)) {
        updateCleanupMarkDone($db, $settingName);
        return;
    }

    $usersById = [];
    $changes = [];
    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id < 1) {
            continue;
        }
        $usersById[$id] = $row;
        $changes[$id] = [
            'old_root' => (int) ($row['root'] ?? 0),
            'new_root' => (int) ($row['root'] ?? 0),
            'old_data' => $row['data'] ?? null,
            'new_data' => $row['data'] ?? null,
        ];
    }
    if (empty($usersById)) {
        updateCleanupMarkDone($db, $settingName);
        return;
    }

    $indexes = updateCleanupBuildRoleIndexes($usersById);

    foreach ($usersById as $id => $row) {
        $role = trim((string) ($row['user_type'] ?? ''));
        $uid = (int) $id;
        $currentRoot = (int) ($row['root'] ?? 0);
        $newRoot = $currentRoot;

        if ($role === 'SuperAdmin') {
            $newRoot = 0;
        } elseif ($role === 'Admin') {
            $isValidParent = ($currentRoot > 0 && $currentRoot !== $uid && in_array($currentRoot, $indexes['super'], true));
            if (!$isValidParent) {
                $candidate = updateCleanupFindAncestorByRole($currentRoot, ['SuperAdmin'], $usersById);
                if ($candidate < 1) {
                    $candidate = updateCleanupFirstId($indexes['super']);
                }
                $newRoot = $candidate;
            }
        }

        if ($newRoot !== $currentRoot) {
            $changes[$uid]['new_root'] = $newRoot;
            $usersById[$uid]['root'] = $newRoot;
        }
    }

    $indexes = updateCleanupBuildRoleIndexes($usersById);

    foreach ($usersById as $id => $row) {
        $role = trim((string) ($row['user_type'] ?? ''));
        if (!in_array($role, ['Agent', 'Report'], true)) {
            continue;
        }
        $uid = (int) $id;
        $currentRoot = (int) ($row['root'] ?? 0);
        $newRoot = $currentRoot;
        $isValidParent = ($currentRoot > 0 && $currentRoot !== $uid && in_array($currentRoot, $indexes['admin'], true));
        if (!$isValidParent) {
            $candidate = updateCleanupFindAncestorByRole($currentRoot, ['Admin'], $usersById);
            if ($candidate < 1 && $currentRoot > 0 && in_array($currentRoot, $indexes['super'], true)) {
                $candidate = updateCleanupFirstId($indexes['admin_by_super'][$currentRoot] ?? []);
            }
            if ($candidate < 1) {
                $candidate = updateCleanupFirstId($indexes['admin']);
            }
            $newRoot = $candidate;
        }
        if ($newRoot !== $currentRoot) {
            $changes[$uid]['new_root'] = $newRoot;
            $usersById[$uid]['root'] = $newRoot;
        }
    }

    $indexes = updateCleanupBuildRoleIndexes($usersById);

    foreach ($usersById as $id => $row) {
        $role = trim((string) ($row['user_type'] ?? ''));
        if ($role !== 'Sales') {
            continue;
        }
        $uid = (int) $id;
        $currentRoot = (int) ($row['root'] ?? 0);
        $newRoot = $currentRoot;
        $isValidParent = ($currentRoot > 0 && $currentRoot !== $uid && in_array($currentRoot, $indexes['agent'], true));
        if (!$isValidParent) {
            $candidate = updateCleanupFindAncestorByRole($currentRoot, ['Agent'], $usersById);
            if ($candidate < 1 && $currentRoot > 0 && in_array($currentRoot, $indexes['admin'], true)) {
                $candidate = updateCleanupFirstId($indexes['agent_by_admin'][$currentRoot] ?? []);
            }
            if ($candidate < 1 && $currentRoot > 0 && in_array($currentRoot, $indexes['super'], true)) {
                $adminUnderSuper = updateCleanupFirstId($indexes['admin_by_super'][$currentRoot] ?? []);
                if ($adminUnderSuper > 0) {
                    $candidate = updateCleanupFirstId($indexes['agent_by_admin'][$adminUnderSuper] ?? []);
                }
            }
            if ($candidate < 1) {
                $candidate = updateCleanupFirstId($indexes['agent']);
            }
            $newRoot = $candidate;
        }
        if ($newRoot !== $currentRoot) {
            $changes[$uid]['new_root'] = $newRoot;
            $usersById[$uid]['root'] = $newRoot;
        }
    }

    if ($hasDataColumn) {
        foreach ($usersById as $id => $row) {
            $valid = false;
            $raw = $changes[$id]['old_data'];
            $decoded = updateCleanupParseData($raw, $valid);
            if (!$valid) {
                continue;
            }
            list($normalized, $changed) = updateCleanupNormalizeRouterData($decoded, (string) ($row['user_type'] ?? ''));
            if (!$changed) {
                continue;
            }
            $changes[$id]['new_data'] = updateCleanupEncodeData($normalized);
        }
    }

    $planned = [];
    foreach ($changes as $id => $item) {
        $rootChanged = ((int) $item['new_root'] !== (int) $item['old_root']);
        $dataChanged = $hasDataColumn && ((string) ($item['new_data'] ?? '') !== (string) ($item['old_data'] ?? ''));
        if ($rootChanged || $dataChanged) {
            $planned[$id] = $item;
        }
    }

    if (empty($planned)) {
        updateCleanupMarkDone($db, $settingName);
        return;
    }

    $startedTx = false;
    if (!$db->inTransaction()) {
        $db->beginTransaction();
        $startedTx = true;
    }

    try {
        if ($hasDataColumn) {
            $stmt = $db->prepare("UPDATE `tbl_users` SET `root` = :root, `data` = :data WHERE `id` = :id");
        } else {
            $stmt = $db->prepare("UPDATE `tbl_users` SET `root` = :root WHERE `id` = :id");
        }

        foreach ($planned as $id => $item) {
            $stmt->bindValue(':root', (int) $item['new_root'], PDO::PARAM_INT);
            if ($hasDataColumn) {
                if ($item['new_data'] === null || $item['new_data'] === '') {
                    $stmt->bindValue(':data', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue(':data', (string) $item['new_data'], PDO::PARAM_STR);
                }
            }
            $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
            $stmt->execute();
        }

        updateCleanupMarkDone($db, $settingName);
        if ($startedTx && $db->inTransaction()) {
            $db->commit();
        }
    } catch (Exception $e) {
        if ($startedTx && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

function shouldSkipCopy($relativePath, $exclude)
{
    $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
    if ($relativePath === '') {
        return false;
    }
    foreach ($exclude as $item) {
        $normalized = trim(str_replace('\\', '/', $item), '/');
        if ($normalized === '') {
            continue;
        }
        if ($relativePath === $normalized || strpos($relativePath, $normalized . '/') === 0) {
            return true;
        }
    }
    return false;
}

function copyFolder($from, $to, $exclude = [], $base = null)
{
    if (!is_dir($from)) {
        throw new Exception('Source path does not exist: ' . $from);
    }

    if ($base === null) {
        $base = rtrim($from, DIRECTORY_SEPARATOR);
        if (substr($base, -1) !== DIRECTORY_SEPARATOR) {
            $base .= DIRECTORY_SEPARATOR;
        } else {
            $base .= '';
        }
    }

    $items = scandir($from);
    if ($items === false) {
        throw new Exception('Unable to read directory: ' . $from);
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $sourcePath = $from . $item;
        $targetPath = $to . $item;
        $relativePath = ltrim(str_replace('\\', '/', substr($sourcePath, strlen($base))), '/');

        if (shouldSkipCopy($relativePath, $exclude)) {
            continue;
        }

        if (is_dir($sourcePath)) {
            if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                throw new Exception('Failed to create directory: ' . $relativePath);
            }
            copyFolder($sourcePath . DIRECTORY_SEPARATOR, $targetPath . DIRECTORY_SEPARATOR, $exclude, $base);
        } elseif (is_file($sourcePath)) {
            $parent = dirname($targetPath);
            if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new Exception('Failed to create directory for file: ' . $relativePath);
            }
            if ($relativePath === 'update.php') {
                $staged = pathFixer('system/cache/update.php.pending');
                if (file_exists($staged) && !unlink($staged)) {
                    throw new Exception('Failed to stage update.php for replacement.');
                }
                if (!copy($sourcePath, $staged)) {
                    throw new Exception('Failed to stage update.php for replacement.');
                }
                scheduleDeferredReplacement($staged, pathFixer(__DIR__ . DIRECTORY_SEPARATOR . 'update.php'));
                continue;
            }
            if (file_exists($targetPath) && !unlink($targetPath)) {
                throw new Exception('Failed to overwrite file: ' . $relativePath);
            }
            if (!copy($sourcePath, $targetPath)) {
                throw new Exception('Failed to copy file: ' . $relativePath);
            }
        }
    }
}
function deleteFolder($path)
{
    if (!is_dir($path)) return;
    $files = scandir($path);
    foreach ($files as $file) {
        if (is_file($path . $file)) {
            unlink($path . $file);
        } else if (is_dir($path . $file) && !in_array($file, ['.', '..'])) {
            deleteFolder($path . $file . DIRECTORY_SEPARATOR);
            rmdir($path . $file);
        }
    }
    rmdir($path);
}

function zipFolder($source, &$zip, $stripPath)
{
    $source = pathFixer($source);
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $exclude = [
        pathFixer('system/cache'),
        pathFixer('system/backup')
    ];

    foreach ($files as $file) {
        $file = pathFixer($file);

        // Exclude backup and cache directories
        $excluded = false;
        foreach($exclude as $ex) {
            if (strpos($file, $ex) === 0) {
                $excluded = true;
                break;
            }
        }
        if ($excluded) continue;


        $filePath = $file;
        $localPath = substr($filePath, $stripPath);
        if(empty($localPath)) continue;
        $localPath = ltrim($localPath, DIRECTORY_SEPARATOR);


        if (is_dir($file)) {
            $zip->addEmptyDir($localPath);
        } else if (is_file($file)) {
            $zip->addFile($filePath, $localPath);
        }
    }
}

function createDatabaseBackup($backupDir)
{
    global $db_host, $db_name, $db_user, $db_pass;

    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        throw new Exception('Database configuration missing');
    }

    $timestamp = time();
    $useGzip = function_exists('gzopen');
    $ext = $useGzip ? '.sql.gz' : '.sql';
    $dbBackupFile = $backupDir . 'pre_update_db_' . $timestamp . $ext;

    $handle = $useGzip ? gzopen($dbBackupFile, 'wb9') : fopen($dbBackupFile, 'wb');
    if ($handle === false) {
        throw new Exception('Unable to create database backup file');
    }

    $writer = function ($line) use ($handle, $useGzip) {
        if ($useGzip) {
            gzwrite($handle, $line);
        } else {
            fwrite($handle, $line);
        }
    };

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
        $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
    }
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

    $writer("-- PHPNuxBill Database Backup\n");
    $writer("-- Generated at " . date('Y-m-d H:i:s') . "\n\n");
    $writer("SET NAMES utf8mb4;\n");
    $writer("SET FOREIGN_KEY_CHECKS=0;\n\n");

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $row) {
        $table = $row[0];
        if ($table === '') {
            continue;
        }
        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $createSql = $createStmt['Create Table'] ?? '';
        if ($createSql === '') {
            continue;
        }

        $writer("\n-- Table: `{$table}`\n");
        $writer("DROP TABLE IF EXISTS `{$table}`;\n");
        $writer($createSql . ";\n\n");

        $colStmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
        $columns = [];
        while ($col = $colStmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $col['Field'];
        }
        if (empty($columns)) {
            continue;
        }

        $columnList = '`' . implode('`,`', array_map(function ($col) {
            return str_replace('`', '``', $col);
        }, $columns)) . '`';

        $stmt = $pdo->query("SELECT * FROM `{$table}`");
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $batch = [];
        $batchSize = 200;

        while ($rowData = $stmt->fetch()) {
            $values = [];
            foreach ($columns as $col) {
                $val = $rowData[$col] ?? null;
                if ($val === null) {
                    $values[] = 'NULL';
                } elseif (is_bool($val)) {
                    $values[] = $val ? '1' : '0';
                } else {
                    $values[] = $pdo->quote((string) $val);
                }
            }
            $batch[] = '(' . implode(',', $values) . ')';
            if (count($batch) >= $batchSize) {
                $writer("INSERT INTO `{$table}` ({$columnList}) VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        if (!empty($batch)) {
            $writer("INSERT INTO `{$table}` ({$columnList}) VALUES\n" . implode(",\n", $batch) . ";\n");
        }
    }

    $writer("\nSET FOREIGN_KEY_CHECKS=1;\n");

    if ($useGzip) {
        gzclose($handle);
    } else {
        fclose($handle);
    }

    return $dbBackupFile;
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>PHPNuxBill Updater</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">

    <link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">

    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">

    <?php if ($continue && $nextStep !== $currentStep) { ?>
        <meta http-equiv="refresh" content="3; ./update.php?step=<?= $nextStep ?>">
    <?php } ?>
    <style>
        ::-moz-selection {
            /* Code for Firefox */
            color: red;
            background: yellow;
        }

        ::selection {
            color: red;
            background: yellow;
        }

        .updater-progress-panel {
            margin-bottom: 15px;
        }

        .updater-progress-title {
            font-weight: 600;
        }

        .updater-progress {
            margin: 10px 0 6px;
            height: 18px;
        }

        .updater-progress .progress-bar {
            min-width: 2px;
            transition: width 0.35s ease;
        }
    </style>

</head>

<body class="hold-transition skin-blue">
    <div class="container">
        <section class="content-header">
            <h1 class="text-center">
                Update PHPNuxBill
            </h1>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-4">
                    <div class="panel panel-default updater-progress-panel">
                        <div class="panel-body">
                            <div class="clearfix">
                                <span class="updater-progress-title">Update Progress</span>
                                <span class="pull-right" id="update-progress-text"><?= (int) $progressPercent ?>%</span>
                            </div>
                            <div class="progress updater-progress">
                                <div
                                    id="update-progress-bar"
                                    class="progress-bar progress-bar-info progress-bar-striped <?= $continue ? 'active' : '' ?>"
                                    role="progressbar"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-valuenow="<?= (int) $progressPercent ?>"
                                    style="width: <?= (int) max(0, $progressPercent) ?>%;">
                                </div>
                            </div>
                            <small class="text-muted">Step <?= (int) max(1, min($progressTotalSteps, $displayStep)) ?> of <?= (int) $progressTotalSteps ?></small>
                        </div>
                    </div>
                    <?php if (!empty($msgType) && !empty($msg)) { ?>
                        <div class="alert alert-<?= $msgType ?>" role="alert">
                            <?= $msg ?>
                        </div>
                    <?php } ?>
                    <?php if ($displayStep > 0) { ?>
                        <?php if ($displayStep == 1) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 1</div>
                                <div class="panel-body">
                                    Downloading update<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } else if ($displayStep == 2) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 2</div>
                                <div class="panel-body">
                                    Extracting update<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } else if ($displayStep == 3) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 3</div>
                                <div class="panel-body">
                                    Creating backup<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } else if ($displayStep == 4) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 4</div>
                                <div class="panel-body">
                                    Installing update files<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } else if ($displayStep == 5) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 5</div>
                                <div class="panel-body">
                                    Running database migrations<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </section>
        <footer class="footer text-center">
            <a  href="https://github.com/robertrullyp/phpnuxbill-dev" rel="nofollow noreferrer noopener"
                target="_blank">PHPNuxBill-Dev</a> Modified by <a href="https://github.com/robertrullyp" rel="nofollow noreferrer noopener"
                target="_blank">Mr. Free</a> from PHPNuxBill by <a href="https://github.com/hotspotbilling/phpnuxbill" rel="nofollow noreferrer noopener"
                target="_blank">iBNuX</a>, Theme by <a href="https://adminlte.io/" rel="nofollow noreferrer noopener"
                target="_blank">AdminLTE</a>
        </footer>
    </div>
    <script>
        (function() {
            var bar = document.getElementById('update-progress-bar');
            var text = document.getElementById('update-progress-text');
            if (!bar || !text) {
                return;
            }

            var current = <?= (int) $progressPercent ?>;
            var target = <?= (int) $progressAnimateTo ?>;

            function render(value) {
                var safe = Math.max(0, Math.min(100, value));
                bar.style.width = safe + '%';
                bar.setAttribute('aria-valuenow', String(safe));
                text.textContent = safe + '%';
                if (safe >= 100 || <?= $continue ? 'false' : 'true' ?>) {
                    bar.classList.remove('active');
                }
            }

            render(current);
            if (target <= current) {
                return;
            }

            var delta = target - current;
            var interval = Math.max(35, Math.floor(950 / delta));
            var timer = setInterval(function() {
                current += 1;
                render(current);
                if (current >= target) {
                    clearInterval(timer);
                }
            }, interval);
        })();
    </script>
</body>

</html>
