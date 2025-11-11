<?php
/**
 * Endpoint to refresh CSRF token via AJAX
 */
if (!_admin(false) && !_auth(false)) {
    http_response_code(403);
    exit;
}

header('Content-Type: application/json');

$response = [
    'csrf_token' => Csrf::generateAndStoreToken(),
    'csrf_token_logout' => Csrf::generateAndStoreToken(),
];

echo json_encode($response);
