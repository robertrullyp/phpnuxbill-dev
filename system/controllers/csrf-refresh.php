<?php
/**
 * Endpoint to refresh CSRF token via AJAX
 */
_admin();
header('Content-Type: application/json');
$token = Csrf::generateAndStoreToken();
echo json_encode(['csrf_token' => $token]);
