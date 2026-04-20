<?php
/**
 * Change Password
 * POST: { current_password, new_password }
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$data = getInput();
requireFields(['current_password', 'new_password'], $data);

$db = getDB();
$role = $_SESSION['role'];

// Get current hash
$tables = ['student' => 'students', 'teacher' => 'teachers', 'admin' => 'admins'];
$table = $tables[$role] ?? null;
if (!$table) jsonError('Invalid role');

$stmt = $db->prepare("SELECT password FROM $table WHERE id = ?");
$stmt->execute([$user['id']]);
$row = $stmt->fetch();

if (!$row || !password_verify($data['current_password'], $row['password'])) {
    jsonError('Current password is incorrect');
}

if (strlen($data['new_password']) < 6) {
    jsonError('New password must be at least 6 characters');
}

$newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE $table SET password = ? WHERE id = ?");
$stmt->execute([$newHash, $user['id']]);

jsonSuccess(['message' => 'Password changed successfully']);
