#!/usr/bin/env php
<?php

declare(strict_types=1);

$repoRoot = realpath(__DIR__ . '/..');
if ($repoRoot === false) {
    fwrite(STDERR, "Unable to resolve repository root.\n");
    exit(1);
}

$upstreamUrl = 'https://hotspotbilling.github.io/Plugin-Repository/repository.json';
$repositoryFile = $repoRoot . DIRECTORY_SEPARATOR . 'plugin-repository.json';
$customFile = $repoRoot . DIRECTORY_SEPARATOR . 'plugin-repository.custom.json';

$upstreamPayload = fetchUrlPayload($upstreamUrl);
if ($upstreamPayload === null) {
    fwrite(STDERR, "Unable to download upstream repository: {$upstreamUrl}\n");
    exit(1);
}

$upstreamData = decodeRepositoryData($upstreamPayload);
if ($upstreamData === null) {
    fwrite(STDERR, "Invalid upstream repository payload.\n");
    exit(1);
}

$customData = loadCustomOverrides($upstreamData, $repositoryFile, $customFile);
$mergedData = mergeRepositoryData($upstreamData, $customData);
$mergedPayload = json_encode(
    $mergedData,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
if ($mergedPayload === false) {
    fwrite(STDERR, "Unable to encode merged repository payload.\n");
    exit(1);
}

$writeOk = file_put_contents($repositoryFile, $mergedPayload . "\n", LOCK_EX);
if ($writeOk === false) {
    fwrite(STDERR, "Unable to write {$repositoryFile}\n");
    exit(1);
}

echo "Plugin repository synced.\n";
echo "Upstream: {$upstreamUrl}\n";
echo "Output: {$repositoryFile}\n";
echo "Custom override: {$customFile}\n";
echo "Counts: plugins=" . count($mergedData['plugins'])
    . ", payment_gateway=" . count($mergedData['payment_gateway'])
    . ", devices=" . count($mergedData['devices']) . "\n";

function fetchUrlPayload(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                ],
                CURLOPT_USERAGENT => 'PHPNuxBill-PluginRepo-Sync',
            ]);
            $data = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (is_string($data) && $status >= 200 && $status < 300) {
                return ltrim($data, "\xEF\xBB\xBF");
            }
        }
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 60,
            'header' => "Accept: application/json\r\nUser-Agent: PHPNuxBill-PluginRepo-Sync\r\n",
        ],
    ]);
    $data = @file_get_contents($url, false, $context);
    if (!is_string($data) || $data === '') {
        return null;
    }

    return ltrim($data, "\xEF\xBB\xBF");
}

function readPayload(string $filePath): ?string
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

function decodeRepositoryData(string $payload): ?array
{
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

function loadCustomOverrides(array $upstreamData, string $repositoryFile, string $customFile): array
{
    $empty = [
        'plugins' => [],
        'payment_gateway' => [],
        'devices' => [],
    ];

    $customPayload = readPayload($customFile);
    if ($customPayload !== null) {
        $customData = decodeRepositoryData($customPayload);
        if ($customData !== null) {
            return $customData;
        }
    }

    // One-time migration for legacy setup where manual changes lived directly in plugin-repository.json.
    $legacyPayload = readPayload($repositoryFile);
    if ($legacyPayload === null) {
        return $empty;
    }
    $legacyData = decodeRepositoryData($legacyPayload);
    if ($legacyData === null) {
        return $empty;
    }

    $overrides = extractOverrides($upstreamData, $legacyData);
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

function extractOverrides(array $upstreamData, array $legacyData): array
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
            if ($id !== '') {
                $upstreamMap[$id] = $row;
            }
        }

        foreach ($legacyData[$section] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            if (!isset($upstreamMap[$id]) || !rowsAreEqual($row, $upstreamMap[$id])) {
                $overrides[$section][] = $row;
            }
        }
    }

    return $overrides;
}

function mergeRepositoryData(array $upstreamData, array $customData): array
{
    return [
        'plugins' => sortSectionByLastUpdateDesc(mergeSection($upstreamData['plugins'], $customData['plugins'])),
        'payment_gateway' => sortSectionByLastUpdateDesc(mergeSection($upstreamData['payment_gateway'], $customData['payment_gateway'])),
        'devices' => sortSectionByLastUpdateDesc(mergeSection($upstreamData['devices'], $customData['devices'])),
    ];
}

function mergeSection(array $upstreamRows, array $customRows): array
{
    $customById = [];
    foreach ($customRows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = trim((string) ($row['id'] ?? ''));
        if ($id !== '') {
            $customById[$id] = $row;
        }
    }

    $merged = [];
    foreach ($upstreamRows as $row) {
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

function rowsAreEqual(array $left, array $right): bool
{
    ksort($left);
    ksort($right);
    return json_encode($left) === json_encode($right);
}

function sortSectionByLastUpdateDesc(array $rows): array
{
    usort($rows, function ($left, $right) {
        $leftTs = parseDateValueToTimestamp((string) ($left['last_update'] ?? ''));
        $rightTs = parseDateValueToTimestamp((string) ($right['last_update'] ?? ''));
        if ($leftTs === $rightTs) {
            $leftName = strtolower(trim((string) ($left['name'] ?? '')));
            $rightName = strtolower(trim((string) ($right['name'] ?? '')));
            return strcmp($leftName, $rightName);
        }
        return ($leftTs > $rightTs) ? -1 : 1;
    });
    return array_values($rows);
}

function parseDateValueToTimestamp(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $parsed = strtotime($value);
    if ($parsed === false) {
        return 0;
    }
    return (int) $parsed;
}
