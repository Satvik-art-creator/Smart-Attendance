<?php
/**
 * Admin Notices
 * GET: list all | POST: create global notice | DELETE: { id }
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('admin');
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->query('SELECT * FROM notices ORDER BY created_at DESC');
    jsonSuccess(['notices' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = getInput();
    requireFields(['title','message'], $data);

    $roleType = in_array($data['role_type'] ?? 'all', ['all','student','teacher']) ? $data['role_type'] : 'all';

    $stmt = $db->prepare('INSERT INTO notices (role_type, title, message, created_by) VALUES (?,?,?,?)');
    $stmt->execute([$roleType, sanitize($data['title']), sanitize($data['message']), $user['name']]);
    jsonSuccess(['message'=>'Notice created','id'=>(int)$db->lastInsertId()]);
}

if ($method === 'DELETE') {
    $data = getInput();
    requireFields(['id'], $data);
    $db->prepare('DELETE FROM notices WHERE id = ?')->execute([(int)$data['id']]);
    jsonSuccess(['message'=>'Notice deleted']);
}
