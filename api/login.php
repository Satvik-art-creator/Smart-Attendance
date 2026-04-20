<?php
/**
 * Login API
 * POST: { role, identifier, password }
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$data = getInput();
requireFields(['role', 'identifier', 'password'], $data);

$role       = sanitize($data['role']);
$identifier = sanitize($data['identifier']);
$password   = $data['password']; // don't sanitize — may contain special chars

if (!in_array($role, ['student', 'teacher', 'admin'])) {
    jsonError('Invalid role');
}

$user = authenticate($role, $identifier, $password);

if (!$user) {
    jsonError('Invalid credentials', 401);
}

jsonSuccess([
    'user' => $user,
    'role' => $role,
    'redirect' => "/$role/"
]);
