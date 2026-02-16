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
if (!isset($GLOBALS['update_lock_registered'])) {
    register_shutdown_function('updateReleaseProcessLock');
    $GLOBALS['update_lock_registered'] = true;
}

processDeferredReplacements();

if($db_password != null && ($db_pass == null || empty($db_pass))){
    // compability for old version
    $db_pass = $db_password;
}

// Always use the default, secure update URL. Do not allow override from user input.
$update_url = 'https://github.com/robertrullyp/phpnuxbill-dev/archive/refs/heads/main.zip';
$updaterFaviconUrl = updateResolveUpdaterFaviconUrl();

if (!isset($_SESSION['aid']) || empty($_SESSION['aid'])) {
    r2("./?_route=login&You_are_not_admin", 'e', 'You are not admin');
}

set_time_limit(-1);
ignore_user_abort(true);

$isCloudflareProxy = isset($_SERVER['HTTP_CF_RAY'])
    || isset($_SERVER['HTTP_CF_VISITOR'])
    || isset($_SERVER['HTTP_CF_CONNECTING_IP'])
    || isset($_SERVER['HTTP_CDN_LOOP']);
$isApiRequest = isset($_GET['api']) && (string) $_GET['api'] === '1';
$isProgressApiRequest = $isApiRequest && isset($_GET['progress']) && (string) $_GET['progress'] === '1';
$enableStreamHeartbeat = $isCloudflareProxy && PHP_SAPI !== 'cli';
$GLOBALS['update_enable_stream_heartbeat'] = $enableStreamHeartbeat;

if ($isApiRequest) {
    $enableStreamHeartbeat = false;
    $GLOBALS['update_enable_stream_heartbeat'] = false;
}

if (!is_writeable(pathFixer('system/cache/'))) {
    r2("./?_route=community", 'e', 'Folder system/cache/ is not writable');
}
if (!is_writeable(pathFixer('.'))) {
    r2("./?_route=community", 'e', 'Folder web is not writable');
}

$requestedStep = isset($_GET['step']) ? (int) $_GET['step'] : 0;
if ($requestedStep < 0 || $requestedStep > 6) {
    $requestedStep = 0;
}
$initialStartStep = ($requestedStep >= 1 && $requestedStep <= 5) ? $requestedStep : 1;
$requestedRedirectTo = isset($_GET['redirect_to']) ? strtolower(trim((string) $_GET['redirect_to'])) : '';
if (!preg_match('/^[a-z_]+$/', $requestedRedirectTo)) {
    $requestedRedirectTo = '';
}
$step = $isApiRequest ? $requestedStep : 0;

if ((($step <= 1) && !$isProgressApiRequest) || empty($_SESSION['update_flow_token']) || !is_string($_SESSION['update_flow_token'])) {
    $_SESSION['update_flow_token'] = updateGenerateRandomToken(32);
}
$updateFlowToken = (string) $_SESSION['update_flow_token'];
if ($isProgressApiRequest) {
    $requestedFlowToken = isset($_GET['flow']) ? trim((string) $_GET['flow']) : '';
    if ($requestedFlowToken !== '' && preg_match('/^[a-f0-9]{16,128}$/i', $requestedFlowToken)) {
        $updateFlowToken = strtolower($requestedFlowToken);
    }
}
$flowTokenSafe = preg_replace('/[^a-f0-9]/i', '', $updateFlowToken);
if ($flowTokenSafe === '') {
    $flowTokenSafe = updateGenerateRandomToken(12);
}
$flowStateFile = pathFixer('system/cache/update-state-' . $flowTokenSafe . '.json');
if ($step <= 1 && !$isProgressApiRequest) {
    unset($_SESSION['update_extract_dir']);
    if (file_exists($flowStateFile)) {
        @unlink($flowStateFile);
    }
}
updateCleanupStaleFlowStates(pathFixer('system/cache/'));

if ($isProgressApiRequest) {
    $progressState = updateReadFlowState($flowStateFile);
    $progressStep = isset($progressState['current_step']) ? (int) $progressState['current_step'] : 0;
    $progressStatus = isset($progressState['status']) ? (string) $progressState['status'] : 'idle';
    $stepProgressPercent = isset($progressState['step_progress_percent']) ? (int) $progressState['step_progress_percent'] : 0;
    if ($stepProgressPercent < 0) {
        $stepProgressPercent = 0;
    } elseif ($stepProgressPercent > 100) {
        $stepProgressPercent = 100;
    }
    $stepProgressLabel = isset($progressState['step_progress_label']) ? (string) $progressState['step_progress_label'] : '';
    $progressUpdatedAt = isset($progressState['updated_at']) ? (int) $progressState['updated_at'] : 0;

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode([
        'ok' => true,
        'flow' => (string) $updateFlowToken,
        'step' => $progressStep,
        'status' => $progressStatus,
        'step_progress_percent' => $stepProgressPercent,
        'step_progress_label' => $stepProgressLabel,
        'updated_at' => $progressUpdatedAt,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$apiFinished = false;
$apiRedirectTarget = '';
$apiSuccessMessage = '';

$continue = true;
$waitForRunningUpdate = false;
if (!extension_loaded('zip')) {
    $msg = "No PHP ZIP extension is available";
    $msgType = "danger";
    $continue = false;
}

if ($step > 0 && $continue) {
    $requestFlowToken = isset($_GET['flow']) ? (string) $_GET['flow'] : '';
    if ($requestFlowToken === '' || !hash_equals($updateFlowToken, $requestFlowToken)) {
        // Graceful fallback for stale/old links: continue with current session flow token.
        $requestFlowToken = $updateFlowToken;
    }
}

if ($step > 0 && $continue) {
    $flowState = updateReadFlowState($flowStateFile);
    $completedStep = isset($flowState['completed_step']) ? (int) $flowState['completed_step'] : 0;
    if ($completedStep >= $step && $completedStep < 6) {
        $step = $completedStep + 1;
    }
}

if ($continue && $step > 0) {
    $lockError = '';
    $lockBusy = false;
    if (!updateAcquireProcessLock(pathFixer('system/cache/update.lock'), $lockError, $lockBusy)) {
        if ($lockBusy && $step > 0 && $step < 6) {
            $msg = 'Updater sedang diproses pada request lain. Halaman ini akan mencoba lagi otomatis.';
            $msgType = 'warning';
            $waitForRunningUpdate = true;
        } else {
            $msg = $lockError;
            $msgType = 'danger';
        }
        $continue = false;
    }
}

if ($continue && $step > 0 && $step < 6) {
    updateHeartbeat(true);
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

if ($step > 0 && $continue && $step < 6) {
    updateMarkFlowStep($flowStateFile, $step, 'running');
}

if (empty($step)) {
    $displayStep = 1;
} else if ($step == 1) {
    try {
        if (!function_exists('curl_init')) {
            throw new Exception('PHP cURL extension is required for updater download step.');
        }
        $downloadCandidates = updateBuildDownloadCandidates($update_url);
        $downloadSucceeded = false;
        $lastDownloadError = '';
        updateMarkFlowStepProgress($flowStateFile, 1, 0, 'Memulai download package...');

        foreach ($downloadCandidates as $candidateUrl) {
            updateHeartbeat(false);
            $downloadDetails = [];
            $downloadError = '';
            if (!updateDownloadArchiveToFile($candidateUrl, $file, $downloadDetails, $downloadError, $flowStateFile, 1)) {
                $lastDownloadError = $downloadError;
                continue;
            }

            $downloadZip = new ZipArchive();
            $zipOpenStatus = $downloadZip->open($file);
            if ($zipOpenStatus !== true) {
                $lastDownloadError = 'Downloaded response is not a valid zip archive (' . updateZipOpenErrorMessage($zipOpenStatus) . '). '
                    . updateBuildDownloadDebugSummary($file, $downloadDetails, $candidateUrl);
                @unlink($file);
                continue;
            }

            $unsafeZipEntry = '';
            if (!updateZipEntriesAreSafe($downloadZip, $unsafeZipEntry)) {
                $downloadZip->close();
                $lastDownloadError = 'Downloaded archive contains unsafe path: ' . $unsafeZipEntry;
                @unlink($file);
                continue;
            }
            if ((int) $downloadZip->numFiles < 1) {
                $downloadZip->close();
                $lastDownloadError = 'Downloaded archive is empty.';
                @unlink($file);
                continue;
            }
            $downloadZip->close();
            updateMarkFlowStepProgress($flowStateFile, 1, 100, 'Download selesai.');
            $downloadSucceeded = true;
            break;
        }

        if (!$downloadSucceeded) {
            if ($lastDownloadError === '') {
                $lastDownloadError = 'Unable to download a valid update package.';
            }
            throw new Exception($lastDownloadError);
        }

        $nextStep = 2;
        updateMarkFlowStep($flowStateFile, 1, 'completed');
    } catch (Exception $e) {
        if (file_exists($file)) {
            @unlink($file);
        }
        $msg = 'Failed to download update file: ' . $e->getMessage();
        $msgType = "danger";
        $continue = false;
    }
} else if ($step == 2) {
    try {
        if (!file_exists($file)) {
            throw new Exception('Downloaded archive not found. Please restart update from step 1.');
        }

        $zip = new ZipArchive();
        updateHeartbeat(false);
        $zipStatus = $zip->open($file);
        if ($zipStatus !== true) {
            throw new Exception('Failed to open update archive (' . updateZipOpenErrorMessage($zipStatus) . ').');
        }
        $unsafeZipEntry = '';
        if (!updateZipEntriesAreSafe($zip, $unsafeZipEntry)) {
            $zip->close();
            throw new Exception('Unsafe archive entry detected: ' . $unsafeZipEntry);
        }
        if (!$zip->extractTo(pathFixer('system/cache/'))) {
            $zip->close();
            throw new Exception('Zip extraction returned failure status.');
        }
        updateHeartbeat(false);
        $zip->close();

        $folder = resolveExtractedFolder($repo, $zipBase, $folder);

        if (is_dir($folder)) {
            $nextStep = 3;
            updateMarkFlowStep($flowStateFile, 2, 'completed');
        } else {
            unset($_SESSION['update_extract_dir']);
            throw new Exception('Extracted update folder was not found.');
        }
    } catch (Exception $e) {
        $msg = "Failed to extract update file: " . $e->getMessage();
        $msgType = "danger";
        $continue = false;
    }
    // remove downloaded zip
    if (file_exists($file)) {
        @unlink($file);
    }
} else if ($step == 3) {
    // Step 3: Create Backup
    $backupDir = pathFixer('system/backup/');
    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
        $msg = "Failed to create backup directory.";
        $msgType = "danger";
        $continue = false;
    }
    if ($continue && !is_writable($backupDir)) {
        $msg = "Backup directory is not writable.";
        $msgType = "danger";
        $continue = false;
    }

    if ($continue) {
        $backupFile = $backupDir . 'pre_update_backup_' . time() . '.zip';
        $_SESSION['backup_file'] = $backupFile;

        try {
            $zip = new ZipArchive();
            if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception("Cannot open zip archive for writing: " . $backupFile);
            }

            updateHeartbeat(false);
            zipFolder(pathFixer('.'), $zip, strlen(pathFixer('.')));

            $zip->close();
            updateHeartbeat(false);
            $dbBackupFile = createDatabaseBackup($backupDir);
            if (!empty($dbBackupFile)) {
                $_SESSION['db_backup_file'] = $dbBackupFile;
            }
            $nextStep = 4;
            updateMarkFlowStep($flowStateFile, 3, 'completed');
        } catch (Exception $e) {
            $msg = "Failed to create backup: " . $e->getMessage();
            $msgType = "danger";
            $continue = false;
        }
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
            updateHeartbeat(false);
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

        updateHeartbeat(false);
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
            updateMarkFlowStep($flowStateFile, 4, 'completed');
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

        $backupHint = '';
        if (!empty($_SESSION['backup_file'])) {
            $backupHint = ' A backup has been created at: ' . htmlspecialchars($_SESSION['backup_file']);
        }
        $msg = 'Failed to install update files: ' . $e->getMessage() . $backupHint;
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
                updateHeartbeat(false);
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
                    updateHeartbeat(false);
                    $q = trim((string) $q);
                    if ($q === '') {
                        continue;
                    }
                    try {
                        $db->exec($q);
                    } catch (PDOException $e) {
                        if (!updateIsIgnorableMigrationError($e)
                            && !updateCanSkipKnownLengthMigration($q, $e)
                            && !updateCanSkipKnownTypeEnumMigration($q, $e)
                        ) {
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

        updateHeartbeat(false);
        runCoreSchemaHardening($db);
        updateHeartbeat(false);
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
        updateMarkFlowStep($flowStateFile, 5, 'completed');
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
    $requestedRedirect = $requestedRedirectTo;
    $target = $redirectTargets['dashboard'];
    if (!empty($requestedRedirect) && isset($redirectTargets[$requestedRedirect])) {
        $target = $redirectTargets[$requestedRedirect];
    }

    updateMarkFlowStep($flowStateFile, 6, 'completed');
    if (file_exists($flowStateFile)) {
        @unlink($flowStateFile);
    }
    unset($_SESSION['update_flow_token']);
    if ($isApiRequest) {
        $apiFinished = true;
        $apiRedirectTarget = $target;
        $apiSuccessMessage = $message;
    } else {
        r2($target, 's', $message);
    }
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

if ($isApiRequest) {
    $apiMessage = '';
    $apiMessageType = '';
    $flowStateSnapshot = updateReadFlowState($flowStateFile);
    $apiStepProgressPercent = isset($flowStateSnapshot['step_progress_percent']) ? (int) $flowStateSnapshot['step_progress_percent'] : 0;
    if ($apiStepProgressPercent < 0) {
        $apiStepProgressPercent = 0;
    } elseif ($apiStepProgressPercent > 100) {
        $apiStepProgressPercent = 100;
    }
    $apiStepProgressLabel = isset($flowStateSnapshot['step_progress_label']) ? (string) $flowStateSnapshot['step_progress_label'] : '';
    if (!empty($msg)) {
        $apiMessage = (string) $msg;
    }
    if (!empty($msgType)) {
        $apiMessageType = (string) $msgType;
    }
    if ($apiFinished) {
        $apiMessage = $apiSuccessMessage;
        $apiMessageType = 'success';
    }

    $response = [
        'ok' => (bool) $continue,
        'busy' => (bool) $waitForRunningUpdate,
        'step' => (int) $currentStep,
        'next_step' => (int) $nextStep,
        'done' => (bool) $apiFinished,
        'flow' => (string) $updateFlowToken,
        'message' => $apiMessage,
        'message_type' => $apiMessageType,
        'progress_percent' => (int) $progressPercent,
        'completed_steps' => (int) $completedSteps,
        'step_progress_percent' => $apiStepProgressPercent,
        'step_progress_label' => $apiStepProgressLabel,
        'redirect_to' => (string) $apiRedirectTarget,
    ];
    if ($apiFinished) {
        $response['progress_percent'] = 100;
        $response['completed_steps'] = $progressTotalSteps;
        $response['next_step'] = 6;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
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

function updateReadFlowState($filePath)
{
    if (!is_file($filePath)) {
        return [];
    }
    $raw = @file_get_contents($filePath);
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    return $decoded;
}

function updateWriteFlowState($filePath, array $state)
{
    $dir = dirname($filePath);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }
    $payload = json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return false;
    }
    return file_put_contents($filePath, $payload, LOCK_EX) !== false;
}

function updateMarkFlowStep($filePath, $step, $status = 'running')
{
    $step = (int) $step;
    if ($step < 1) {
        return;
    }
    $status = trim((string) $status);
    if ($status === '') {
        $status = 'running';
    }
    $state = updateReadFlowState($filePath);
    $state['current_step'] = $step;
    $state['status'] = $status;
    $state['updated_at'] = time();
    if ($status === 'completed') {
        $currentCompleted = isset($state['completed_step']) ? (int) $state['completed_step'] : 0;
        $state['completed_step'] = max($currentCompleted, $step);
        $state['step_progress_percent'] = 100;
        $state['step_progress_label'] = 'Step ' . $step . ' selesai';
    } elseif (!isset($state['completed_step'])) {
        $state['completed_step'] = 0;
    }
    if ($status === 'running') {
        $state['step_progress_percent'] = 0;
        $state['step_progress_label'] = '';
    }
    updateWriteFlowState($filePath, $state);
    updateTouchProcessLock();
}

function updateMarkFlowStepProgress($filePath, $step, $percent, $label = '')
{
    $step = (int) $step;
    if ($step < 1) {
        return;
    }

    $percent = (int) round($percent);
    if ($percent < 0) {
        $percent = 0;
    } elseif ($percent > 100) {
        $percent = 100;
    }

    $state = updateReadFlowState($filePath);
    $state['current_step'] = $step;
    if (empty($state['status'])) {
        $state['status'] = 'running';
    }
    $state['step_progress_percent'] = $percent;
    if ($label !== '') {
        $state['step_progress_label'] = (string) $label;
    }
    $state['updated_at'] = time();
    updateWriteFlowState($filePath, $state);
    updateTouchProcessLock();
}

function updateHeartbeat($force = false)
{
    updateTouchProcessLock();
    if (empty($GLOBALS['update_enable_stream_heartbeat']) || PHP_SAPI === 'cli') {
        return;
    }

    static $initialized = false;
    static $lastBeat = 0.0;

    $now = microtime(true);
    if (!$force && ($now - $lastBeat) < 5) {
        return;
    }

    if (!$initialized) {
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        @ini_set('implicit_flush', '1');
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        @ob_implicit_flush(true);
        $initialized = true;
    }

    echo "\n<!-- updater-heartbeat " . date('H:i:s') . " -->\n";
    echo str_repeat(' ', 1024) . "\n";
    @ob_flush();
    @flush();
    $lastBeat = $now;
}

function updateGenerateRandomToken($length = 32)
{
    $length = (int) $length;
    if ($length < 8) {
        $length = 8;
    }
    if ($length % 2 !== 0) {
        $length += 1;
    }
    $bytes = (int) ($length / 2);
    try {
        return bin2hex(random_bytes($bytes));
    } catch (Exception $e) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $raw = openssl_random_pseudo_bytes($bytes, $strong);
            if ($raw !== false) {
                return bin2hex($raw);
            }
        }
    }
    return sha1(uniqid((string) mt_rand(), true));
}

function updateReadLockMeta($handle)
{
    if (!is_resource($handle)) {
        return [];
    }
    rewind($handle);
    $raw = stream_get_contents($handle);
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function updateExtractLockTimestamp(array $lockMeta)
{
    if (!empty($lockMeta['updated_at'])) {
        return (int) $lockMeta['updated_at'];
    }
    if (!empty($lockMeta['time'])) {
        $parsed = strtotime((string) $lockMeta['time']);
        if ($parsed !== false) {
            return (int) $parsed;
        }
    }
    return 0;
}

function updateProcessExists($pid)
{
    $pid = (int) $pid;
    if ($pid < 1) {
        return false;
    }
    if (function_exists('posix_kill') && @posix_kill($pid, 0)) {
        return true;
    }
    return is_dir('/proc/' . $pid);
}

function updateTerminateProcess($pid)
{
    $pid = (int) $pid;
    if ($pid < 1 || !updateProcessExists($pid)) {
        return true;
    }

    if (!function_exists('posix_kill')) {
        return false;
    }

    @posix_kill($pid, defined('SIGTERM') ? SIGTERM : 15);
    $deadline = microtime(true) + 2.0;
    while (microtime(true) < $deadline) {
        usleep(100000);
        if (!updateProcessExists($pid)) {
            return true;
        }
    }

    @posix_kill($pid, defined('SIGKILL') ? SIGKILL : 9);
    $deadline = microtime(true) + 2.0;
    while (microtime(true) < $deadline) {
        usleep(100000);
        if (!updateProcessExists($pid)) {
            return true;
        }
    }

    return !updateProcessExists($pid);
}

function updateTouchProcessLock()
{
    if (empty($GLOBALS['update_lock_handle']) || !is_resource($GLOBALS['update_lock_handle'])) {
        return;
    }
    $startedAt = !empty($GLOBALS['update_lock_started_at']) ? (int) $GLOBALS['update_lock_started_at'] : time();
    $payload = json_encode([
        'pid' => getmypid(),
        'time' => date('c'),
        'updated_at' => time(),
        'started_at' => $startedAt,
        'session' => session_id(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        return;
    }

    $handle = $GLOBALS['update_lock_handle'];
    @ftruncate($handle, 0);
    @rewind($handle);
    @fwrite($handle, $payload);
    @fflush($handle);
}

function updateLockMaxAgeSeconds()
{
    $configured = isset($GLOBALS['config']['update_lock_max_age']) ? (int) $GLOBALS['config']['update_lock_max_age'] : 0;
    if ($configured >= 120 && $configured <= 86400) {
        return $configured;
    }
    return 900;
}

function updateCleanupStaleFlowStates($cacheDir)
{
    $cacheDir = normalizeDir($cacheDir);
    if (!is_dir($cacheDir)) {
        return;
    }

    $stateTtl = 86400;
    $files = glob($cacheDir . 'update-state-*.json');
    if (is_array($files)) {
        foreach ($files as $stateFile) {
            $mtime = @filemtime($stateFile);
            if ($mtime !== false && (time() - $mtime) > $stateTtl) {
                @unlink($stateFile);
            }
        }
    }

    $zipFile = $cacheDir . 'phpnuxbill.zip';
    if (is_file($zipFile)) {
        $zipSize = @filesize($zipFile);
        $zipMtime = @filemtime($zipFile);
        if ($zipSize === 0 && $zipMtime !== false && (time() - $zipMtime) > 300) {
            @unlink($zipFile);
        }
    }
}

function updateAcquireProcessLock($lockFile, &$error = '', &$busy = false)
{
    $error = '';
    $busy = false;
    if (!empty($GLOBALS['update_lock_handle']) && is_resource($GLOBALS['update_lock_handle'])) {
        return true;
    }

    $lockDir = dirname($lockFile);
    if (!is_dir($lockDir) && !mkdir($lockDir, 0755, true) && !is_dir($lockDir)) {
        $error = 'Unable to create lock directory for updater.';
        return false;
    }

    $maxAge = updateLockMaxAgeSeconds();
    $attempts = 0;

    while ($attempts < 3) {
        $attempts++;

        $handle = fopen($lockFile, 'c+');
        if ($handle === false) {
            $error = 'Unable to open updater lock file.';
            return false;
        }

        if (flock($handle, LOCK_EX | LOCK_NB)) {
            $GLOBALS['update_lock_handle'] = $handle;
            $GLOBALS['update_lock_started_at'] = time();
            updateTouchProcessLock();
            return true;
        }

        $lockMeta = updateReadLockMeta($handle);
        fclose($handle);

        $lockTimestamp = updateExtractLockTimestamp($lockMeta);
        if ($lockTimestamp <= 0 && is_file($lockFile)) {
            $mtime = @filemtime($lockFile);
            if ($mtime !== false) {
                $lockTimestamp = (int) $mtime;
            }
        }

        $isStale = $lockTimestamp > 0 && (time() - $lockTimestamp) > $maxAge;
        if ($isStale) {
            $pid = isset($lockMeta['pid']) ? (int) $lockMeta['pid'] : 0;
            $canRecover = true;
            if ($pid > 0 && updateProcessExists($pid)) {
                $canRecover = updateTerminateProcess($pid);
            }

            if ($canRecover) {
                @unlink($lockFile);
                usleep(250000);
                continue;
            }

            $error = 'Updater lock is stale but cannot be recovered automatically. Please retry in a moment.';
            return false;
        }

        $busy = true;
        $error = 'Another update process is currently running. Please wait until it finishes.';
        return false;
    }

    $error = 'Unable to recover updater lock.';
    return false;
}

function updateReleaseProcessLock()
{
    if (empty($GLOBALS['update_lock_handle']) || !is_resource($GLOBALS['update_lock_handle'])) {
        return;
    }
    $handle = $GLOBALS['update_lock_handle'];
    @ftruncate($handle, 0);
    @fflush($handle);
    @flock($handle, LOCK_UN);
    @fclose($handle);
    $GLOBALS['update_lock_handle'] = null;
    unset($GLOBALS['update_lock_started_at']);
}

function updateBuildDownloadCandidates($primaryUrl)
{
    $primaryUrl = trim((string) $primaryUrl);
    $candidates = [];
    if ($primaryUrl !== '') {
        $candidates[$primaryUrl] = true;
    }

    $parsed = parse_url($primaryUrl);
    if (!is_array($parsed)) {
        return array_keys($candidates);
    }

    $host = strtolower((string) ($parsed['host'] ?? ''));
    $path = trim((string) ($parsed['path'] ?? ''), '/');
    if ($host !== 'github.com' || $path === '') {
        return array_keys($candidates);
    }

    if (preg_match('#^([^/]+)/([^/]+)/archive/refs/heads/(.+)\.zip$#', $path, $m)) {
        $owner = $m[1];
        $repo = $m[2];
        $branchPath = updateEncodeUrlPathSegments($m[3]);
        $candidates['https://codeload.github.com/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/zip/refs/heads/' . $branchPath] = true;
    } elseif (preg_match('#^([^/]+)/([^/]+)/archive/refs/tags/(.+)\.zip$#', $path, $m)) {
        $owner = $m[1];
        $repo = $m[2];
        $tagPath = updateEncodeUrlPathSegments($m[3]);
        $candidates['https://codeload.github.com/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/zip/refs/tags/' . $tagPath] = true;
    }

    return array_keys($candidates);
}

function updateEncodeUrlPathSegments($path)
{
    $segments = explode('/', trim((string) $path, '/'));
    $encoded = [];
    foreach ($segments as $segment) {
        if ($segment === '') {
            continue;
        }
        $encoded[] = rawurlencode($segment);
    }
    return implode('/', $encoded);
}

function updateDownloadArchiveToFile($url, $targetFile, &$details, &$error, $flowStateFile = '', $progressStep = 0)
{
    $details = [
        'url' => (string) $url,
        'http_code' => 0,
        'content_type' => '',
        'effective_url' => (string) $url,
        'bytes_written' => 0,
        'downloaded_size' => 0,
        'download_total' => 0,
        'download_now' => 0,
    ];
    $error = '';
    $progressStep = (int) $progressStep;
    $lastProgressPercent = -1;
    $lastProgressUpdate = 0.0;
    if ($progressStep > 0 && $flowStateFile !== '') {
        updateMarkFlowStepProgress($flowStateFile, $progressStep, 0, 'Memulai download...');
    }

    if (file_exists($targetFile) && !@unlink($targetFile)) {
        $error = 'Unable to remove stale update archive before downloading.';
        return false;
    }

    $fp = fopen($targetFile, 'wb');
    if ($fp === false) {
        $error = 'Unable to create update archive file in system/cache/.';
        return false;
    }

    $bytesWritten = 0;
    $ch = curl_init($url);
    if ($ch === false) {
        fclose($fp);
        @unlink($targetFile);
        $error = 'Unable to initialize cURL for update download.';
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => 0,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 600,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 8,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FAILONERROR => false,
        CURLOPT_WRITEFUNCTION => function ($curlHandle, $chunk) use ($fp, &$bytesWritten) {
            $length = strlen($chunk);
            if ($length === 0) {
                return 0;
            }
            $written = fwrite($fp, $chunk);
            if ($written === false) {
                return 0;
            }
            $bytesWritten += (int) $written;
            return $written;
        },
        CURLOPT_NOPROGRESS => false,
        CURLOPT_PROGRESSFUNCTION => function () use (&$details, $flowStateFile, $progressStep, &$lastProgressPercent, &$lastProgressUpdate) {
            $args = func_get_args();
            $downloadTotal = 0.0;
            $downloadNow = 0.0;

            if (count($args) >= 5) {
                $downloadTotal = (float) $args[1];
                $downloadNow = (float) $args[2];
            } elseif (count($args) >= 4) {
                $downloadTotal = (float) $args[0];
                $downloadNow = (float) $args[1];
            }

            $details['download_total'] = (int) max(0, round($downloadTotal));
            $details['download_now'] = (int) max(0, round($downloadNow));

            if ($progressStep > 0 && $flowStateFile !== '' && $downloadTotal > 0) {
                $percent = (int) floor(($downloadNow / $downloadTotal) * 100);
                if ($percent < 0) {
                    $percent = 0;
                } elseif ($percent > 99) {
                    $percent = 99;
                }

                $now = microtime(true);
                $shouldWrite = ($percent !== $lastProgressPercent) && ($percent === 0 || $percent >= 99 || ($now - $lastProgressUpdate) >= 0.75);
                if ($shouldWrite) {
                    $lastProgressPercent = $percent;
                    $lastProgressUpdate = $now;
                    updateMarkFlowStepProgress($flowStateFile, $progressStep, $percent, 'Download package ' . $percent . '%');
                }
            }
            updateHeartbeat(false);
            return 0;
        },
        CURLOPT_HTTPHEADER => [
            'Accept: application/zip, application/octet-stream;q=0.9, */*;q=0.8',
        ],
        CURLOPT_USERAGENT => 'PHPNuxBill-Updater',
    ]);

    $downloadOk = curl_exec($ch);
    $curlErrNo = curl_errno($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    fclose($fp);

    $downloadedSize = file_exists($targetFile) ? filesize($targetFile) : false;
    if ($downloadedSize === false) {
        $downloadedSize = 0;
    }

    $details['http_code'] = $httpCode;
    $details['content_type'] = $contentType;
    $details['effective_url'] = $effectiveUrl !== '' ? $effectiveUrl : (string) $url;
    $details['bytes_written'] = (int) $bytesWritten;
    $details['downloaded_size'] = (int) $downloadedSize;

    if ($downloadOk === false || $curlErrNo !== 0) {
        @unlink($targetFile);
        $error = 'Download failed from ' . $url . ': ' . ($curlError !== '' ? $curlError : 'Unknown cURL error.');
        return false;
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        @unlink($targetFile);
        $error = 'Download failed from ' . $url . ' with HTTP status ' . $httpCode . '.';
        return false;
    }
    if (!file_exists($targetFile) || $downloadedSize <= 0 || $bytesWritten <= 0) {
        @unlink($targetFile);
        $error = 'Downloaded archive appears incomplete from ' . $url . '.';
        return false;
    }
    if (!updateFileLooksLikeZip($targetFile)) {
        $error = 'Server returned non-zip content from ' . $url . '. '
            . updateBuildDownloadDebugSummary($targetFile, $details, $url);
        @unlink($targetFile);
        return false;
    }

    if ($progressStep > 0 && $flowStateFile !== '') {
        updateMarkFlowStepProgress($flowStateFile, $progressStep, 100, 'Download package selesai');
    }

    return true;
}

function updateFileLooksLikeZip($filePath)
{
    if (!is_file($filePath)) {
        return false;
    }

    $handle = @fopen($filePath, 'rb');
    if ($handle === false) {
        return false;
    }
    $signature = fread($handle, 4);
    fclose($handle);

    if (!is_string($signature) || strlen($signature) < 4) {
        return false;
    }

    return in_array($signature, ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"], true);
}

function updateBuildDownloadDebugSummary($filePath, $details = [], $sourceUrl = '')
{
    $parts = [];
    $httpCode = isset($details['http_code']) ? (int) $details['http_code'] : 0;
    $contentType = trim((string) ($details['content_type'] ?? ''));
    $effectiveUrl = trim((string) ($details['effective_url'] ?? ''));
    $bytesWritten = isset($details['bytes_written']) ? (int) $details['bytes_written'] : 0;
    $downloadedSize = isset($details['downloaded_size']) ? (int) $details['downloaded_size'] : 0;

    if ($httpCode > 0) {
        $parts[] = 'HTTP ' . $httpCode;
    }
    if ($contentType !== '') {
        $parts[] = 'Content-Type: ' . $contentType;
    }
    if ($effectiveUrl !== '' && $effectiveUrl !== $sourceUrl) {
        $parts[] = 'Final URL: ' . $effectiveUrl;
    }
    if ($bytesWritten > 0 || $downloadedSize > 0) {
        $parts[] = 'Size: ' . max($bytesWritten, $downloadedSize) . ' bytes';
    }

    $preview = updateReadFilePreview($filePath, 180);
    if ($preview !== '') {
        $preview = preg_replace('/\s+/', ' ', $preview);
        $preview = trim((string) $preview);
        if ($preview !== '') {
            $parts[] = 'Body preview: ' . $preview;
        }
    }

    return implode(' | ', $parts);
}

function updateReadFilePreview($filePath, $maxBytes = 180)
{
    $maxBytes = (int) $maxBytes;
    if ($maxBytes < 32) {
        $maxBytes = 32;
    }
    if (!is_file($filePath)) {
        return '';
    }

    $handle = @fopen($filePath, 'rb');
    if ($handle === false) {
        return '';
    }
    $raw = fread($handle, $maxBytes);
    fclose($handle);

    if (!is_string($raw) || $raw === '') {
        return '';
    }

    $clean = preg_replace('/[^\x20-\x7E\r\n\t]/', '?', $raw);
    return trim((string) $clean);
}

function updateResolveUpdaterFaviconUrl()
{
    $uploadPath = pathFixer(__DIR__ . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'uploads');
    $candidates = [];

    $configuredFavicon = updateReadAppConfigValue('login_page_favicon');
    if ($configuredFavicon !== '' && updateIsSafeUploadAssetName($configuredFavicon)) {
        $candidates[] = $configuredFavicon;
    }
    $candidates[] = 'favicon.png';
    $candidates[] = 'logo.favicon.png';
    $candidates[] = 'logo.png';
    $candidates[] = 'logo.default.png';

    foreach ($candidates as $assetName) {
        $assetName = trim((string) $assetName);
        if ($assetName === '') {
            continue;
        }
        $filePath = $uploadPath . DIRECTORY_SEPARATOR . $assetName;
        if (is_file($filePath)) {
            return updateBuildAssetUrl('system/uploads/' . $assetName);
        }
    }

    return updateBuildAssetUrl('ui/ui/images/logo.png');
}

function updateReadAppConfigValue($settingName)
{
    static $cache = [];
    $settingName = trim((string) $settingName);
    if ($settingName === '') {
        return '';
    }
    if (array_key_exists($settingName, $cache)) {
        return $cache[$settingName];
    }

    global $db_host, $db_name, $db_user, $db_pass;
    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        $cache[$settingName] = '';
        return '';
    }

    try {
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2,
            ]
        );
        $stmt = $pdo->prepare("SELECT `value` FROM `tbl_appconfig` WHERE `setting` = :setting ORDER BY `id` ASC LIMIT 1");
        $stmt->execute([':setting' => $settingName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $value = trim((string) ($row['value'] ?? ''));
        $cache[$settingName] = $value;
        return $value;
    } catch (Throwable $e) {
        $cache[$settingName] = '';
        return '';
    }
}

function updateIsSafeUploadAssetName($assetName)
{
    $assetName = trim((string) $assetName);
    if ($assetName === '') {
        return false;
    }
    if (strpos($assetName, '..') !== false || strpos($assetName, '/') !== false || strpos($assetName, '\\') !== false) {
        return false;
    }
    return preg_match('/^[a-zA-Z0-9._-]+$/', $assetName) === 1;
}

function updateBuildAssetUrl($relativePath)
{
    $relativePath = ltrim(str_replace('\\', '/', (string) $relativePath), '/');
    if ($relativePath === '') {
        return './';
    }

    $suffix = '';
    $assetFile = pathFixer(__DIR__ . DIRECTORY_SEPARATOR . $relativePath);
    if (is_file($assetFile)) {
        $mtime = @filemtime($assetFile);
        if ($mtime !== false) {
            $suffix = '?v=' . (int) $mtime;
        }
    }

    if (defined('APP_URL') && APP_URL !== '') {
        return rtrim((string) APP_URL, '/') . '/' . $relativePath . $suffix;
    }
    return './' . $relativePath . $suffix;
}

function updateZipOpenErrorMessage($statusCode)
{
    $map = [
        0 => 'No error',
        1 => 'Multi-disk zip archives are not supported',
        2 => 'Zip archive was renamed and no longer exists',
        3 => 'Failed to close zip archive',
        4 => 'Seek error',
        5 => 'Read error',
        6 => 'Write error',
        7 => 'CRC error',
        8 => 'Zip archive has been closed',
        9 => 'Zip archive missing',
        10 => 'Entry already exists',
        11 => 'Unable to open file',
        12 => 'Temporary file open failure',
        13 => 'Zlib error',
        14 => 'Memory allocation failure',
        15 => 'Zip entry changed unexpectedly',
        16 => 'Compression method not supported',
        17 => 'Premature EOF',
        18 => 'Invalid argument',
        19 => 'Not a zip archive',
        20 => 'Internal zip error',
        21 => 'Zip archive inconsistent',
        22 => 'Failed to remove file',
        23 => 'Entry has been deleted',
    ];
    $statusCode = (int) $statusCode;
    if (isset($map[$statusCode])) {
        return $map[$statusCode];
    }
    return 'zip_status_' . $statusCode;
}

function updateZipEntriesAreSafe($zip, &$unsafeEntry = '')
{
    $unsafeEntry = '';
    if (!($zip instanceof ZipArchive)) {
        $unsafeEntry = 'invalid-zip-object';
        return false;
    }
    $total = (int) $zip->numFiles;
    for ($i = 0; $i < $total; $i++) {
        $name = (string) $zip->getNameIndex($i);
        $normalized = str_replace('\\', '/', $name);
        if ($normalized === '' || strpos($normalized, "\0") !== false) {
            $unsafeEntry = $name;
            return false;
        }
        if ($normalized[0] === '/' || preg_match('/^[A-Za-z]:\//', $normalized)) {
            $unsafeEntry = $name;
            return false;
        }
        $parts = explode('/', $normalized);
        foreach ($parts as $part) {
            if ($part === '..') {
                $unsafeEntry = $name;
                return false;
            }
        }
    }
    return true;
}

function updateCopyFileAtomic($sourcePath, $targetPath, $label = '')
{
    $label = trim((string) $label);
    if ($label === '') {
        $label = $targetPath;
    }
    $targetDir = dirname($targetPath);
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        throw new Exception('Failed to create directory for file: ' . $label);
    }

    $tempFile = $targetPath . '.update-tmp-' . updateGenerateRandomToken(8);
    if (file_exists($tempFile) && !@unlink($tempFile)) {
        throw new Exception('Failed to prepare temporary file for: ' . $label);
    }
    if (!copy($sourcePath, $tempFile)) {
        throw new Exception('Failed to stage file copy: ' . $label);
    }

    if (file_exists($targetPath) && !@unlink($targetPath)) {
        @unlink($tempFile);
        throw new Exception('Failed to overwrite file: ' . $label);
    }

    if (!@rename($tempFile, $targetPath)) {
        if (!@copy($tempFile, $targetPath)) {
            @unlink($tempFile);
            throw new Exception('Failed to copy file: ' . $label);
        }
        @unlink($tempFile);
    }

    $sourcePerms = @fileperms($sourcePath);
    if ($sourcePerms !== false) {
        @chmod($targetPath, $sourcePerms & 0777);
    }
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

    if (file_put_contents($filePath, $payload, LOCK_EX) === false) {
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

function updateCanSkipKnownLengthMigration($query, $e)
{
    if (!($e instanceof PDOException)) {
        return false;
    }

    $driverCode = 0;
    if (!empty($e->errorInfo) && isset($e->errorInfo[1])) {
        $driverCode = (int) $e->errorInfo[1];
    }

    $message = strtolower((string) $e->getMessage());
    $query = strtolower(trim((string) $query));
    if ($query === '') {
        return false;
    }

    $isTooLongError = ($driverCode === 1406) || (strpos($message, 'data too long for column') !== false);
    if (!$isTooLongError) {
        return false;
    }

    $isPaymentGatewayAlter = (strpos($query, 'alter table `tbl_payment_gateway` change') === 0);
    if (!$isPaymentGatewayAlter) {
        return false;
    }

    $isKnownColumn = (strpos($query, '`pg_url_payment`') !== false) || (strpos($query, '`gateway_trx_id`') !== false);
    if (!$isKnownColumn) {
        return false;
    }

    $is512Target = strpos($query, 'varchar(512)') !== false;
    if (!$is512Target) {
        return false;
    }

    // Existing installations may already store values >512 chars.
    // Keeping the wider existing schema is safer than aborting entire migration.
    return true;
}

function updateCanSkipKnownTypeEnumMigration($query, $e)
{
    if (!($e instanceof PDOException)) {
        return false;
    }

    $driverCode = 0;
    if (!empty($e->errorInfo) && isset($e->errorInfo[1])) {
        $driverCode = (int) $e->errorInfo[1];
    }

    $message = strtolower((string) $e->getMessage());
    $query = strtolower(trim((string) $query));
    if ($query === '') {
        return false;
    }

    $isTypeTruncation = ($driverCode === 1265) || (strpos($message, "data truncated for column 'type'") !== false);
    if (!$isTypeTruncation) {
        return false;
    }

    $isTypeEnumAlter = (strpos($query, 'alter table `tbl_plans` change `type` `type` enum(') === 0)
        || (strpos($query, 'alter table `tbl_transactions` change `type` `type` enum(') === 0);
    if (!$isTypeEnumAlter) {
        return false;
    }

    $isKnownLegacyTarget = (strpos($query, "enum('hotspot','pppoe','balance')") !== false)
        || (strpos($query, "enum('hotspot','pppoe','balance','radius')") !== false);
    if (!$isKnownLegacyTarget) {
        return false;
    }

    // Legacy enum migrations can fail on databases that already contain newer values (e.g. VPN).
    // Skipping keeps the wider schema intact instead of forcing a downgrade.
    return true;
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
        if (!updateColumnExists($db, 'tbl_plans', 'pppoe_service')) {
            $db->exec("ALTER TABLE `tbl_plans` ADD `pppoe_service` VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'PPPoE server service-name' AFTER `pool`;");
        }
        if (!updateColumnExists($db, 'tbl_plans', 'customer_can_extend')) {
            $db->exec("ALTER TABLE `tbl_plans` ADD `customer_can_extend` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '0 disable customer self extend' AFTER `invoice_notification`;");
        }
    }

    if (updateTableExists($db, 'tbl_transactions') && updateColumnExists($db, 'tbl_transactions', 'type')) {
        $db->exec("ALTER TABLE `tbl_transactions` CHANGE `type` `type` ENUM('Hotspot','PPPOE','VPN','Balance') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;");
    }

    if (updateTableExists($db, 'tbl_user_recharges')) {
        if (!updateColumnExists($db, 'tbl_user_recharges', 'usage_tx_bytes')) {
            $db->exec("ALTER TABLE `tbl_user_recharges` ADD `usage_tx_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `admin_id`;");
        }
        if (!updateColumnExists($db, 'tbl_user_recharges', 'usage_rx_bytes')) {
            $db->exec("ALTER TABLE `tbl_user_recharges` ADD `usage_rx_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `usage_tx_bytes`;");
        }
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

    if (!updateTableExists($db, 'tbl_recharge_usage_cycles')) {
        $db->exec("CREATE TABLE IF NOT EXISTS `tbl_recharge_usage_cycles` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `recharge_id` INT UNSIGNED NOT NULL DEFAULT 0, `transaction_id` INT UNSIGNED NOT NULL DEFAULT 0, `customer_id` INT UNSIGNED NOT NULL DEFAULT 0, `plan_id` INT UNSIGNED NOT NULL DEFAULT 0, `router_name` VARCHAR(64) NOT NULL DEFAULT '', `type` VARCHAR(16) NOT NULL DEFAULT '', `binding_name` VARCHAR(128) NOT NULL DEFAULT '', `binding_user` VARCHAR(64) NOT NULL DEFAULT '', `started_at` DATETIME NOT NULL, `expires_at` DATETIME NOT NULL, `ended_at` DATETIME DEFAULT NULL, `status` ENUM('open','closed') NOT NULL DEFAULT 'open', `usage_tx_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0, `usage_rx_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0, `last_counter_tx` BIGINT UNSIGNED NOT NULL DEFAULT 0, `last_counter_rx` BIGINT UNSIGNED NOT NULL DEFAULT 0, `last_sample_at` DATETIME DEFAULT NULL, `note` VARCHAR(255) NOT NULL DEFAULT '', `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_usage_cycle_recharge_status` (`recharge_id`,`status`), KEY `idx_usage_cycle_scope` (`customer_id`,`router_name`,`type`,`status`), KEY `idx_usage_cycle_transaction` (`transaction_id`), KEY `idx_usage_cycle_plan` (`plan_id`), KEY `idx_usage_cycle_started` (`started_at`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    } else {
        if (!updateIndexExists($db, 'tbl_recharge_usage_cycles', 'idx_usage_cycle_recharge_status')) {
            $db->exec("ALTER TABLE `tbl_recharge_usage_cycles` ADD KEY `idx_usage_cycle_recharge_status` (`recharge_id`,`status`);");
        }
        if (!updateIndexExists($db, 'tbl_recharge_usage_cycles', 'idx_usage_cycle_scope')) {
            $db->exec("ALTER TABLE `tbl_recharge_usage_cycles` ADD KEY `idx_usage_cycle_scope` (`customer_id`,`router_name`,`type`,`status`);");
        }
        if (!updateIndexExists($db, 'tbl_recharge_usage_cycles', 'idx_usage_cycle_transaction')) {
            $db->exec("ALTER TABLE `tbl_recharge_usage_cycles` ADD KEY `idx_usage_cycle_transaction` (`transaction_id`);");
        }
        if (!updateIndexExists($db, 'tbl_recharge_usage_cycles', 'idx_usage_cycle_plan')) {
            $db->exec("ALTER TABLE `tbl_recharge_usage_cycles` ADD KEY `idx_usage_cycle_plan` (`plan_id`);");
        }
        if (!updateIndexExists($db, 'tbl_recharge_usage_cycles', 'idx_usage_cycle_started')) {
            $db->exec("ALTER TABLE `tbl_recharge_usage_cycles` ADD KEY `idx_usage_cycle_started` (`started_at`);");
        }
    }

    if (!updateTableExists($db, 'tbl_recharge_usage_samples')) {
        $db->exec("CREATE TABLE IF NOT EXISTS `tbl_recharge_usage_samples` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `cycle_id` BIGINT UNSIGNED NOT NULL DEFAULT 0, `recharge_id` INT UNSIGNED NOT NULL DEFAULT 0, `sample_at` DATETIME NOT NULL, `counter_tx` BIGINT UNSIGNED NOT NULL DEFAULT 0, `counter_rx` BIGINT UNSIGNED NOT NULL DEFAULT 0, `delta_tx` BIGINT UNSIGNED NOT NULL DEFAULT 0, `delta_rx` BIGINT UNSIGNED NOT NULL DEFAULT 0, `usage_tx_total` BIGINT UNSIGNED NOT NULL DEFAULT 0, `usage_rx_total` BIGINT UNSIGNED NOT NULL DEFAULT 0, `source` VARCHAR(24) NOT NULL DEFAULT 'cron', `note` VARCHAR(255) NOT NULL DEFAULT '', `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_usage_sample_cycle` (`cycle_id`), KEY `idx_usage_sample_recharge` (`recharge_id`), KEY `idx_usage_sample_time` (`sample_at`), KEY `idx_usage_sample_source` (`source`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    } else {
        if (!updateIndexExists($db, 'tbl_recharge_usage_samples', 'idx_usage_sample_cycle')) {
            $db->exec("ALTER TABLE `tbl_recharge_usage_samples` ADD KEY `idx_usage_sample_cycle` (`cycle_id`);");
        }
        if (!updateIndexExists($db, 'tbl_recharge_usage_samples', 'idx_usage_sample_recharge')) {
            $db->exec("ALTER TABLE `tbl_recharge_usage_samples` ADD KEY `idx_usage_sample_recharge` (`recharge_id`);");
        }
        if (!updateIndexExists($db, 'tbl_recharge_usage_samples', 'idx_usage_sample_time')) {
            $db->exec("ALTER TABLE `tbl_recharge_usage_samples` ADD KEY `idx_usage_sample_time` (`sample_at`);");
        }
        if (!updateIndexExists($db, 'tbl_recharge_usage_samples', 'idx_usage_sample_source')) {
            $db->exec("ALTER TABLE `tbl_recharge_usage_samples` ADD KEY `idx_usage_sample_source` (`source`);");
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
        updateHeartbeat(false);
        if ($item === '.' || $item === '..') {
            continue;
        }

        $sourcePath = $from . $item;
        $targetPath = $to . $item;
        $relativePath = ltrim(str_replace('\\', '/', substr($sourcePath, strlen($base))), '/');

        if (shouldSkipCopy($relativePath, $exclude)) {
            continue;
        }

        if (is_link($sourcePath)) {
            continue;
        }

        if (is_dir($sourcePath)) {
            if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                throw new Exception('Failed to create directory: ' . $relativePath);
            }
            copyFolder($sourcePath . DIRECTORY_SEPARATOR, $targetPath . DIRECTORY_SEPARATOR, $exclude, $base);
        } elseif (is_file($sourcePath)) {
            if ($relativePath === 'update.php') {
                $staged = pathFixer('system/cache/update.php.pending');
                updateCopyFileAtomic($sourcePath, $staged, 'update.php');
                scheduleDeferredReplacement($staged, pathFixer(__DIR__ . DIRECTORY_SEPARATOR . 'update.php'));
                continue;
            }
            updateCopyFileAtomic($sourcePath, $targetPath, $relativePath);
        }
    }
}
function deleteFolder($path)
{
    if (is_link($path)) {
        @unlink($path);
        return;
    }
    if (!is_dir($path)) return;
    $files = scandir($path);
    if ($files === false) {
        return;
    }
    foreach ($files as $file) {
        $itemPath = $path . $file;
        if ($file === '.' || $file === '..') {
            continue;
        }
        if (is_link($itemPath) || is_file($itemPath)) {
            @unlink($itemPath);
        } else if (is_dir($itemPath)) {
            deleteFolder($itemPath . DIRECTORY_SEPARATOR);
            @rmdir($itemPath);
        }
    }
    @rmdir($path);
}

function zipFolder($source, &$zip, $stripPath)
{
    $sourceRoot = realpath(pathFixer($source));
    if ($sourceRoot === false || !is_dir($sourceRoot)) {
        throw new Exception('Invalid source path for backup: ' . $source);
    }
    $sourceRoot = rtrim($sourceRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceRoot, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $exclude = [
        'system/cache',
        'system/backup',
        '.git'
    ];

    foreach ($files as $fileInfo) {
        updateHeartbeat(false);
        $filePath = $fileInfo->getPathname();
        $localPath = ltrim(str_replace('\\', '/', substr($filePath, strlen($sourceRoot))), '/');
        if ($localPath === '') {
            continue;
        }
        if (shouldSkipCopy($localPath, $exclude)) {
            continue;
        }
        if ($fileInfo->isLink()) {
            continue;
        }

        if ($fileInfo->isDir()) {
            if (!$zip->addEmptyDir($localPath)) {
                throw new Exception('Failed to add directory to backup archive: ' . $localPath);
            }
        } else if ($fileInfo->isFile()) {
            if (!$zip->addFile($filePath, $localPath)) {
                throw new Exception('Failed to add file to backup archive: ' . $localPath);
            }
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

    try {
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
            updateHeartbeat(false);
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
                updateHeartbeat(false);
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
                updateHeartbeat(false);
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
    } catch (Throwable $e) {
        if ($useGzip) {
            @gzclose($handle);
        } else {
            @fclose($handle);
        }
        if (file_exists($dbBackupFile)) {
            @unlink($dbBackupFile);
        }
        throw $e;
    }

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
    <link rel="shortcut icon" href="<?= htmlspecialchars($updaterFaviconUrl, ENT_QUOTES, 'UTF-8') ?>" type="image/x-icon" />

    <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">

    <link rel="stylesheet" href="ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">

    <link rel="stylesheet" href="ui/ui/styles/modern-AdminLTE.min.css">

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

        body.hold-transition.skin-blue {
            background: #f4f6f9;
        }

        .container {
            max-width: 980px;
        }

        .content-header {
            padding: 8px 15px 4px;
        }

        .content-header h1 {
            margin: 6px 0 8px;
            font-size: 24px;
        }

        .content {
            padding-top: 0;
        }

        .panel {
            margin-bottom: 10px;
        }

        .panel-heading {
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
        }

        .panel-body {
            padding: 12px;
        }

        .updater-progress-panel {
            margin-bottom: 8px;
        }

        .updater-progress-title {
            font-weight: 600;
        }

        .updater-progress {
            margin: 8px 0 4px;
            height: 14px;
        }

        .updater-progress .progress-bar {
            min-width: 2px;
            transition: width 0.35s ease;
        }

        .updater-mode-hint {
            margin-top: 4px;
        }

        .updater-wait-row {
            margin-top: 8px;
            min-height: 20px;
        }

        .updater-wait-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2f6f9f;
            font-size: 12px;
            font-weight: 600;
        }

        .updater-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(60, 141, 188, 0.25);
            border-top-color: #3c8dbc;
            border-radius: 50%;
            animation: updater-spin 0.8s linear infinite;
        }

        .updater-step-list {
            margin-bottom: 0;
        }

        .updater-step-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            transition: background-color 0.2s ease, border-color 0.2s ease;
            padding: 8px 10px;
        }

        .updater-step-item .updater-step-label {
            font-weight: 600;
            font-size: 12px;
        }

        .updater-step-item .updater-step-detail {
            display: block;
            color: #777;
            font-size: 11px;
        }

        .updater-step-item.state-pending {
            border-left: 4px solid #d2d6de;
            background: #f9fafc;
        }

        .updater-step-item.state-running {
            border-left: 4px solid #3c8dbc;
            background: #eef6fb;
        }

        .updater-step-item.state-done {
            border-left: 4px solid #00a65a;
            background: #edf9f2;
        }

        .updater-step-item.state-skipped {
            border-left: 4px solid #f39c12;
            background: #fff7ea;
        }

        .updater-step-item.state-error {
            border-left: 4px solid #dd4b39;
            background: #fff0ee;
        }

        .updater-step-state {
            min-width: 150px;
            text-align: right;
            white-space: nowrap;
            font-weight: 600;
            color: #555;
            font-size: 11px;
        }

        .updater-log {
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            background: #fbfbfb;
            max-height: 180px;
            overflow-y: auto;
            padding: 6px 8px;
        }

        .updater-log-entry {
            margin-bottom: 5px;
            font-size: 11px;
            line-height: 1.35;
            word-break: break-word;
        }

        .updater-log-entry:last-child {
            margin-bottom: 0;
        }

        .updater-log-entry .updater-log-time {
            color: #777;
            margin-right: 6px;
        }

        .updater-log-entry.info {
            color: #31708f;
        }

        .updater-log-entry.success {
            color: #3c763d;
        }

        .updater-log-entry.warning {
            color: #8a6d3b;
        }

        .updater-log-entry.danger {
            color: #a94442;
        }

        .updater-action-row {
            margin-top: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .footer {
            margin-top: 8px;
            padding-bottom: 8px;
            font-size: 11px;
        }

        @keyframes updater-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .content-header h1 {
                font-size: 20px;
            }

            .updater-step-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .updater-step-state {
                min-width: 0;
                text-align: left;
            }
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
                <div class="col-md-8 col-md-offset-2">
                    <div class="panel panel-default updater-progress-panel">
                        <div class="panel-body">
                            <div class="clearfix">
                                <span class="updater-progress-title">Update Progress</span>
                                <span class="pull-right" id="update-progress-text">0%</span>
                            </div>
                            <div class="progress updater-progress">
                                <div
                                    id="update-progress-bar"
                                    class="progress-bar progress-bar-info progress-bar-striped active"
                                    role="progressbar"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-valuenow="0"
                                    style="width: 0%;">
                                </div>
                            </div>
                            <div class="updater-mode-hint">
                                <span class="label label-<?= $initialStartStep === 5 ? 'warning' : 'primary' ?>" id="updater-mode-label">
                                    <?= $initialStartStep === 5 ? 'Database Only' : 'Full Update' ?>
                                </span>
                                <small class="text-muted">Start dari Step <?= (int) $initialStartStep ?>.</small>
                            </div>
                            <div class="updater-wait-row">
                                <span id="updater-wait-indicator" class="updater-wait-indicator" style="display:none;">
                                    <span class="updater-spinner" aria-hidden="true"></span>
                                    <span id="updater-wait-text">Sedang memproses...</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($msgType) && !empty($msg)) { ?>
                        <div class="alert alert-<?= $msgType ?>" role="alert">
                            <?= $msg ?>
                        </div>
                    <?php } ?>
                    <div id="updater-runtime-alert" class="alert alert-info" style="display: none;"></div>

                    <div class="panel panel-primary">
                        <div class="panel-heading">Langkah Update</div>
                        <div class="panel-body">
                            <ul class="list-group updater-step-list" id="updater-step-list">
                                <li class="list-group-item updater-step-item state-pending" data-step="1">
                                    <div>
                                        <span class="updater-step-label">Step 1 - Download package update</span>
                                        <span class="updater-step-detail">Unduh file update dari repositori.</span>
                                    </div>
                                    <span class="updater-step-state">Pending</span>
                                </li>
                                <li class="list-group-item updater-step-item state-pending" data-step="2">
                                    <div>
                                        <span class="updater-step-label">Step 2 - Extract package</span>
                                        <span class="updater-step-detail">Ekstrak zip update ke cache sementara.</span>
                                    </div>
                                    <span class="updater-step-state">Pending</span>
                                </li>
                                <li class="list-group-item updater-step-item state-pending" data-step="3">
                                    <div>
                                        <span class="updater-step-label">Step 3 - Backup sistem</span>
                                        <span class="updater-step-detail">Backup file dan database sebelum instalasi.</span>
                                    </div>
                                    <span class="updater-step-state">Pending</span>
                                </li>
                                <li class="list-group-item updater-step-item state-pending" data-step="4">
                                    <div>
                                        <span class="updater-step-label">Step 4 - Install file update</span>
                                        <span class="updater-step-detail">Replace file aplikasi sambil menjaga file penting.</span>
                                    </div>
                                    <span class="updater-step-state">Pending</span>
                                </li>
                                <li class="list-group-item updater-step-item state-pending" data-step="5">
                                    <div>
                                        <span class="updater-step-label">Step 5 - Update database</span>
                                        <span class="updater-step-detail">Jalankan migrasi dan hardening schema.</span>
                                    </div>
                                    <span class="updater-step-state">Pending</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel panel-default">
                        <div class="panel-heading clearfix">
                            <span>Status Proses</span>
                            <span class="label label-default pull-right" id="updater-run-status">Menunggu</span>
                        </div>
                        <div class="panel-body">
                            <div id="updater-log" class="updater-log"></div>
                            <div class="updater-action-row">
                                <button type="button" class="btn btn-warning btn-sm" id="updater-retry-btn" style="display:none;">
                                    Coba Lagi Step Terakhir
                                </button>
                                <a href="./?_route=dashboard" class="btn btn-success btn-sm" id="updater-finish-btn" style="display:none;">
                                    Lanjut ke Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
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
            var config = {
                flow: <?= json_encode($updateFlowToken, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                startStep: <?= (int) $initialStartStep ?>,
                canRun: <?= $continue ? 'true' : 'false' ?>,
                redirectTo: <?= json_encode($requestedRedirectTo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
            };

            var totalSteps = 5;
            var currentStartStep = Math.max(1, Math.min(totalSteps, parseInt(config.startStep, 10) || 1));
            var isRunning = false;
            var failedStep = null;
            var redirectTimer = null;
            var progressPollTimer = null;

            var progressBar = document.getElementById('update-progress-bar');
            var progressText = document.getElementById('update-progress-text');
            var runStatus = document.getElementById('updater-run-status');
            var runtimeAlert = document.getElementById('updater-runtime-alert');
            var retryBtn = document.getElementById('updater-retry-btn');
            var finishBtn = document.getElementById('updater-finish-btn');
            var logContainer = document.getElementById('updater-log');
            var waitIndicator = document.getElementById('updater-wait-indicator');
            var waitText = document.getElementById('updater-wait-text');

            if (!progressBar || !progressText || !runStatus || !runtimeAlert || !retryBtn || !finishBtn || !logContainer || !waitIndicator || !waitText) {
                return;
            }

            function renderProgress(percent) {
                var safe = Math.max(0, Math.min(100, parseInt(percent, 10) || 0));
                progressBar.style.width = safe + '%';
                progressBar.setAttribute('aria-valuenow', String(safe));
                progressText.textContent = safe + '%';
                if (safe >= 100) {
                    progressBar.classList.remove('active');
                } else if (!progressBar.classList.contains('active')) {
                    progressBar.classList.add('active');
                }
            }

            function setRunStatusLabel(text, labelType) {
                runStatus.textContent = text;
                runStatus.className = 'label pull-right';
                runStatus.classList.add('label-' + (labelType || 'default'));
            }

            function showRuntimeAlert(type, message) {
                if (!message) {
                    runtimeAlert.style.display = 'none';
                    runtimeAlert.textContent = '';
                    runtimeAlert.className = 'alert';
                    return;
                }
                runtimeAlert.className = 'alert alert-' + type;
                runtimeAlert.textContent = message;
                runtimeAlert.style.display = 'block';
            }

            function setWaitIndicator(active, message) {
                if (!active) {
                    waitIndicator.style.display = 'none';
                    waitText.textContent = 'Sedang memproses...';
                    return;
                }
                waitText.textContent = message || 'Sedang memproses...';
                waitIndicator.style.display = 'inline-flex';
            }

            function appendLog(message, tone) {
                var entry = document.createElement('div');
                entry.className = 'updater-log-entry ' + (tone || 'info');

                var time = document.createElement('span');
                time.className = 'updater-log-time';
                var now = new Date();
                var hh = String(now.getHours()).padStart(2, '0');
                var mm = String(now.getMinutes()).padStart(2, '0');
                var ss = String(now.getSeconds()).padStart(2, '0');
                time.textContent = '[' + hh + ':' + mm + ':' + ss + ']';

                var text = document.createElement('span');
                text.textContent = message;

                entry.appendChild(time);
                entry.appendChild(text);
                logContainer.appendChild(entry);
                logContainer.scrollTop = logContainer.scrollHeight;
            }

            function getStepNode(step) {
                return document.querySelector('.updater-step-item[data-step="' + step + '"]');
            }

            function setStepState(step, state, detail) {
                var node = getStepNode(step);
                if (!node) {
                    return;
                }

                node.classList.remove('state-pending', 'state-running', 'state-done', 'state-skipped', 'state-error');
                node.classList.add('state-' + state);

                var stateEl = node.querySelector('.updater-step-state');
                if (!stateEl) {
                    return;
                }

                var stateText = 'Pending';
                if (state === 'running') {
                    stateText = 'Sedang diproses';
                } else if (state === 'done') {
                    stateText = 'Selesai';
                } else if (state === 'skipped') {
                    stateText = 'Dilewati';
                } else if (state === 'error') {
                    stateText = 'Gagal';
                }
                if (detail) {
                    stateText += ' - ' + detail;
                }
                stateEl.textContent = stateText;
            }

            function initializeStepStates() {
                for (var i = 1; i <= totalSteps; i++) {
                    setStepState(i, 'pending');
                }
            }

            function applyStartMode() {
                initializeStepStates();
                var completedBeforeStart = Math.max(0, currentStartStep - 1);
                for (var i = 1; i < currentStartStep; i++) {
                    setStepState(i, 'skipped', 'Mode start step ' + currentStartStep);
                }
                renderProgress(Math.round((completedBeforeStart / totalSteps) * 100));
            }

            function applyStepProgress(step, stepPercent, stepLabel) {
                var safeStep = parseInt(step, 10);
                if (!safeStep || safeStep < 1 || safeStep > totalSteps) {
                    return;
                }

                var safeStepPercent = parseInt(stepPercent, 10);
                if (isNaN(safeStepPercent)) {
                    safeStepPercent = 0;
                }
                if (safeStepPercent < 0) {
                    safeStepPercent = 0;
                } else if (safeStepPercent > 100) {
                    safeStepPercent = 100;
                }

                var globalPercent = Math.round((((safeStep - 1) + (safeStepPercent / 100)) / totalSteps) * 100);
                renderProgress(globalPercent);

                if (stepLabel) {
                    setStepState(safeStep, 'running', stepLabel);
                    setWaitIndicator(true, stepLabel);
                }
            }

            function updateProgressFromResponse(payload, requestedStep) {
                if (!payload || typeof payload.progress_percent === 'undefined') {
                    return;
                }
                renderProgress(payload.progress_percent);
                if (typeof payload.step_progress_percent !== 'undefined') {
                    applyStepProgress(requestedStep, payload.step_progress_percent, payload.step_progress_label || '');
                }
            }

            function buildApiUrl(step) {
                var url = './update.php?api=1&step=' + encodeURIComponent(String(step)) + '&flow=' + encodeURIComponent(config.flow);
                if (config.redirectTo) {
                    url += '&redirect_to=' + encodeURIComponent(config.redirectTo);
                }
                return url;
            }

            function buildProgressApiUrl() {
                return './update.php?api=1&progress=1&flow=' + encodeURIComponent(config.flow);
            }

            function stopProgressPolling() {
                if (progressPollTimer) {
                    clearInterval(progressPollTimer);
                    progressPollTimer = null;
                }
            }

            function startProgressPolling(step) {
                stopProgressPolling();
                if (!isRunning || step < 1 || step > totalSteps) {
                    return;
                }

                progressPollTimer = setInterval(function() {
                    if (!isRunning) {
                        stopProgressPolling();
                        return;
                    }

                    fetch(buildProgressApiUrl(), {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(function(response) {
                        if (!response.ok) {
                            return null;
                        }
                        return response.json();
                    }).then(function(payload) {
                        if (!payload || !payload.ok) {
                            return;
                        }

                        if (payload.flow) {
                            config.flow = payload.flow;
                        }

                        var polledStep = parseInt(payload.step, 10);
                        if (!polledStep || polledStep < 1 || polledStep > totalSteps) {
                            polledStep = step;
                        }
                        applyStepProgress(polledStep, payload.step_progress_percent, payload.step_progress_label || '');
                    }).catch(function() {
                        // Do nothing on poll error, main step request still owns flow.
                    });
                }, 900);
            }

            function setFailureState(step, message, type, payload) {
                isRunning = false;
                stopProgressPolling();
                failedStep = step;
                if (step >= 1 && step <= totalSteps) {
                    setStepState(step, 'error', 'Perlu retry');
                }
                if (payload) {
                    updateProgressFromResponse(payload, step);
                }
                setRunStatusLabel('Gagal pada step ' + step, 'danger');
                showRuntimeAlert(type === 'warning' ? 'warning' : 'danger', message);
                appendLog(message, type === 'warning' ? 'warning' : 'danger');
                setWaitIndicator(false, '');
                retryBtn.style.display = 'inline-block';
            }

            function markFinished(payload) {
                isRunning = false;
                stopProgressPolling();
                failedStep = null;
                renderProgress(100);
                setRunStatusLabel('Selesai', 'success');
                progressBar.classList.remove('active');
                retryBtn.style.display = 'none';
                finishBtn.style.display = 'inline-block';
                setWaitIndicator(false, '');

                var finishMessage = (payload && payload.message) ? payload.message : 'Update selesai.';
                showRuntimeAlert('success', finishMessage);
                appendLog('Semua step updater selesai.', 'success');

                if (payload && payload.redirect_to) {
                    finishBtn.setAttribute('href', payload.redirect_to);
                    appendLog('Redirect otomatis dalam 5 detik.', 'info');
                    if (redirectTimer) {
                        clearTimeout(redirectTimer);
                    }
                    redirectTimer = setTimeout(function() {
                        window.location.href = payload.redirect_to;
                    }, 5000);
                }
            }

            function executeStep(step) {
                if (!isRunning) {
                    return;
                }

                var uiStep = Math.min(totalSteps, Math.max(1, step));
                if (step <= totalSteps) {
                    setStepState(uiStep, 'running', 'Mohon tunggu');
                }
                setRunStatusLabel('Menjalankan step ' + step, 'primary');
                appendLog('Menjalankan step ' + step + '.', 'info');
                setWaitIndicator(true, 'Menjalankan step ' + step + '...');
                startProgressPolling(step);

                fetch(buildApiUrl(step), {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                }).then(function(payload) {
                    stopProgressPolling();
                    if (payload && payload.flow) {
                        config.flow = payload.flow;
                    }

                    if (payload && payload.busy) {
                        setRunStatusLabel('Menunggu lock updater', 'warning');
                        if (step <= totalSteps) {
                            setStepState(uiStep, 'running', 'Menunggu request updater lain');
                        }
                        setWaitIndicator(true, payload.message || 'Menunggu proses updater lain...');
                        appendLog(payload.message || 'Updater sedang dipakai request lain. Retry 2 detik lagi.', 'warning');
                        setTimeout(function() {
                            executeStep(step);
                        }, 2000);
                        return;
                    }

                    if (!payload || !payload.ok) {
                        var failMessage = (payload && payload.message) ? payload.message : ('Step ' + step + ' gagal.');
                        setFailureState(step, failMessage, payload ? payload.message_type : 'danger', payload || null);
                        return;
                    }

                    updateProgressFromResponse(payload, step);
                    if (step <= totalSteps) {
                        setStepState(uiStep, 'done', 'Selesai');
                    }
                    setWaitIndicator(false, '');
                    if (payload.message) {
                        appendLog(payload.message, payload.message_type || 'success');
                    }

                    if (payload.done) {
                        markFinished(payload);
                        return;
                    }

                    var nextStep = parseInt(payload.next_step, 10);
                    if (!nextStep || nextStep <= step) {
                        nextStep = step + 1;
                    }
                    executeStep(nextStep);
                }).catch(function(error) {
                    stopProgressPolling();
                    setFailureState(step, 'Gagal memproses request updater: ' + error.message, 'danger', null);
                });
            }

            function startUpdater(step) {
                if (isRunning) {
                    return;
                }
                if (redirectTimer) {
                    clearTimeout(redirectTimer);
                    redirectTimer = null;
                }
                retryBtn.style.display = 'none';
                finishBtn.style.display = 'none';
                isRunning = true;
                failedStep = null;
                stopProgressPolling();

                applyStartMode();
                showRuntimeAlert('info', 'Updater berjalan pada halaman ini. Jangan menutup browser sampai selesai.');
                setRunStatusLabel('Memulai update', 'info');
                setWaitIndicator(true, 'Menyiapkan updater...');
                appendLog('Updater dimulai dari step ' + step + '.', 'info');
                executeStep(step);
            }

            retryBtn.addEventListener('click', function() {
                var stepToRetry = failedStep || currentStartStep;
                startUpdater(stepToRetry);
            });

            applyStartMode();
            if (!config.canRun) {
                setRunStatusLabel('Tidak dapat memulai', 'danger');
                appendLog('Validasi awal updater gagal. Lihat pesan error di atas.', 'danger');
                setWaitIndicator(false, '');
                return;
            }

            startUpdater(currentStartStep);
        })();
    </script>
</body>

</html>
