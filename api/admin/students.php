<?php
/**
 * Admin Students CRUD
 * GET: list all students (?search=X optional)
 * POST: create student { roll_no, full_name, email, password, year, semester, section }
 * PUT: update student { id, ... }
 * DELETE: delete student { id }
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('admin');
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];

// ── LIST ──
if ($method === 'GET') {
    $sql = 'SELECT id, roll_no, full_name, email, year, semester, section, created_at FROM students';
    $params = [];

    if (!empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $sql .= ' WHERE roll_no LIKE ? OR full_name LIKE ? OR email LIKE ?';
        $params = [$search, $search, $search];
    }

    $sql .= ' ORDER BY roll_no';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonSuccess(['students' => $stmt->fetchAll()]);
}

// ── CREATE ──
if ($method === 'POST') {
    $data = getInput();
    requireFields(['roll_no', 'full_name', 'password', 'semester', 'section'], $data);

    // Check duplicate roll_no
    $stmt = $db->prepare('SELECT id FROM students WHERE roll_no = ?');
    $stmt->execute([sanitize($data['roll_no'])]);
    if ($stmt->fetch()) jsonError('Roll number already exists');

    $hash = password_hash($data['password'], PASSWORD_BCRYPT);

    $stmt = $db->prepare('
        INSERT INTO students (roll_no, full_name, email, password, year, semester, section)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        sanitize($data['roll_no']),
        sanitize($data['full_name']),
        sanitize($data['email'] ?? ''),
        $hash,
        (int)($data['year'] ?? 1),
        (int) $data['semester'],
        sanitize($data['section']),
    ]);

    jsonSuccess(['message' => 'Student created', 'id' => (int) $db->lastInsertId()]);
}

// ── UPDATE ──
if ($method === 'PUT') {
    $data = getInput();
    requireFields(['id'], $data);
    $id = (int) $data['id'];

    $fields = [];
    $params = [];

    foreach (['roll_no', 'full_name', 'email', 'year', 'semester', 'section'] as $f) {
        if (isset($data[$f])) {
            $fields[] = "$f = ?";
            $params[] = sanitize($data[$f]);
        }
    }

    if (!empty($data['password'])) {
        $fields[] = 'password = ?';
        $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
    }

    if (empty($fields)) jsonError('Nothing to update');

    $params[] = $id;
    $stmt = $db->prepare('UPDATE students SET ' . implode(', ', $fields) . ' WHERE id = ?');
    $stmt->execute($params);

    jsonSuccess(['message' => 'Student updated']);
}

// ── DELETE ──
if ($method === 'DELETE') {
    $data = getInput();
    requireFields(['id'], $data);

    $stmt = $db->prepare('DELETE FROM students WHERE id = ?');
    $stmt->execute([(int) $data['id']]);

    jsonSuccess(['message' => 'Student deleted']);
}
