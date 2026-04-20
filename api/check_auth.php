<?php
/**
 * Check current auth status
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (isLoggedIn()) {
    jsonSuccess(['user' => getCurrentUser()]);
} else {
    // Return 200 with success:false instead of 401
    // A 401 causes browser extensions (ajaxRequestInterceptor) to force-reload the page
    jsonResponse(['success' => false, 'authenticated' => false, 'message' => 'Not authenticated']);
}
