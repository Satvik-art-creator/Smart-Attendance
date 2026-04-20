<?php
/**
 * Logout API
 */

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
logout();
echo json_encode(['success' => true]);
