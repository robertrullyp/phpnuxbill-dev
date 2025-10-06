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
    $step++;
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
        $step++;
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
        $step++;
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
        $step++;
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
            $step++;
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
    if (file_exists("system/updates.json")) {
        try {
            $db = new pdo(
                "mysql:host=$db_host;dbname=$db_name",
                $db_user,
                $db_pass,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );

            $updates = json_decode(file_get_contents("system/updates.json"), true);
            $dones = [];
            if (file_exists("system/cache/updates.done.json")) {
                $dones = json_decode(file_get_contents("system/cache/updates.done.json"), true);
            }
            foreach ($updates as $version => $queries) {
                if (!in_array($version, $dones)) {
                    foreach ($queries as $q) {
                        try {
                            $db->exec($q);
                        } catch (PDOException $e) {
                            // Log or handle more gracefully in the future
                            // For now, ignoring "already exists" is the original behavior
                        }
                    }
                    $dones[] = $version;
                }
            }
            file_put_contents("system/cache/updates.done.json", json_encode($dones));
        } catch (Exception $e) {
            $msg = "Failed to update database: " . $e->getMessage() . ". A backup has been created at: " . htmlspecialchars($_SESSION['backup_file']);
            $msgType = "danger";
            $continue = false;
        }
    }
    if ($continue) {
        $step++;
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

    // Delete the successful backup file
    if (!empty($_SESSION['backup_file']) && file_exists($_SESSION['backup_file'])) {
        unlink($_SESSION['backup_file']);
        unset($_SESSION['backup_file']);
    }

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

    <?php if ($continue) { ?>
        <meta http-equiv="refresh" content="3; ./update.php?step=<?= $step ?>">
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
                    <?php if (!empty($msgType) && !empty($msg)) { ?>
                        <div class="alert alert-<?= $msgType ?>" role="alert">
                            <?= $msg ?>
                        </div>
                    <?php } ?>
                    <?php if ($continue || $step == 5) { ?>
                        <?php if ($step == 1) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 1</div>
                                <div class="panel-body">
                                    Downloading update<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } else if ($step == 2) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 2</div>
                                <div class="panel-body">
                                    extracting<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } else if ($step == 3) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 3</div>
                                <div class="panel-body">
                                    Installing<br>
                                    Please wait....
                                </div>
                            </div>
                        <?php } else if ($step == 4) { ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading">Step 4</div>
                                <div class="panel-body">
                                    Updating database...
                                </div>
                            </div>
                        <?php } else if ($step == 5) { ?>
                            <div class="panel panel-success">
                                <div class="panel-heading">Update Finished</div>
                                <div class="panel-body">
                                    PHPNuxBill has been updated to Version <b><?= $version ?></b>
                                </div>
                            </div>
                            <meta http-equiv="refresh" content="5; ./?_route=dashboard">
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
</body>

</html>
