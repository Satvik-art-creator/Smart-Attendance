<?php
/**
 * Admin Teachers CRUD
 * GET: list | POST: create | PUT: update | DELETE: delete
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('admin');
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = 'SELECT id, teacher_code, full_name, email, created_at FROM teachers';
    $params = [];
    if (!empty($_GET['search'])) {
        $s = '%' . sanitize($_GET['search']) . '%';
        $sql .= ' WHERE teacher_code LIKE ? OR full_name LIKE ? OR email LIKE ?';
        $params = [$s, $s, $s];
    }
    $sql .= ' ORDER BY teacher_code';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonSuccess(['teachers' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = getInput();
    requireFields(['teacher_code', 'full_name', 'password'], $data);

    $stmt = $db->prepare('SELECT id FROM teachers WHERE teacher_code = ?');
    $stmt->execute([sanitize($data['teacher_code'])]);
    if ($stmt->fetch()) jsonError('Teacher code already exists');

    $stmt = $db->prepare('INSERT INTO teachers (teacher_code, full_name, email, password) VALUES (?,?,?,?)');
    $stmt->execute([
        sanitize($data['teacher_code']),
        sanitize($data['full_name']),
        sanitize($data['email'] ?? ''),
        password_hash($data['password'], PASSWORD_BCRYPT),
    ]);
    jsonSuccess(['message' => 'Teacher created', 'id' => (int) $db->lastInsertId()]);
}

if ($method === 'PUT') {
    $data = getInput();
    requireFields(['id'], $data);
    $id = (int) $data['id'];
    $fields = []; $params = [];

    foreach (['teacher_code', 'full_name', 'email'] as $f) {
        if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($data[$f]); }
    }
    if (!empty($data['password'])) {
        $fields[] = 'password = ?';
        $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
    }
    if (empty($fields)) jsonError('Nothing to update');

    $params[] = $id;
    $db->prepare('UPDATE teachers SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
    jsonSuccess(['message' => 'Teacher updated']);
}

if ($method === 'DELETE') {
    $data = getInput();
    requireFields(['id'], $data);
    $db->prepare('DELETE FROM teachers WHERE id = ?')->execute([(int)$data['id']]);
    jsonSuccess(['message' => 'Teacher deleted']);
}
