<?php
/**
 * Endpoint to refresh CSRF token via AJAX
 */
if (!_admin(false) && !_auth(false)) {
    http_response_code(403);
    if (!empty($isApi)) {
        showResult(false, Lang::T('Unauthorized'), [], ['login' => true]);
    }
    exit;
}

$payload = [
    'csrf_token' => Csrf::generateAndStoreToken(),
    'csrf_token_logout' => Csrf::generateAndStoreToken(),
];

// In API mode, respond using the standard JSON envelope to avoid double-output
// (this controller previously echoed JSON and the API wrapper then appended a second JSON envelope).
if (!empty($isApi)) {
    showResult(true, 'ok', $payload);
}

header('Content-Type: application/json');
echo json_encode($payload);
exit;
