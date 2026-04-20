<?php
/**
 * Admin Subjects CRUD
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('admin');
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->query('SELECT * FROM subjects ORDER BY subject_code');
    jsonSuccess(['subjects' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = getInput();
    requireFields(['subject_code', 'subject_name'], $data);

    $stmt = $db->prepare('SELECT id FROM subjects WHERE subject_code = ?');
    $stmt->execute([sanitize($data['subject_code'])]);
    if ($stmt->fetch()) jsonError('Subject code already exists');

    $stmt = $db->prepare('INSERT INTO subjects (subject_code, subject_name) VALUES (?,?)');
    $stmt->execute([sanitize($data['subject_code']), sanitize($data['subject_name'])]);
    jsonSuccess(['message' => 'Subject created', 'id' => (int)$db->lastInsertId()]);
}

if ($method === 'PUT') {
    $data = getInput();
    requireFields(['id'], $data);
    $fields = []; $params = [];
    foreach (['subject_code','subject_name'] as $f) {
        if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($data[$f]); }
    }
    if (empty($fields)) jsonError('Nothing to update');
    $params[] = (int)$data['id'];
    $db->prepare('UPDATE subjects SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
    jsonSuccess(['message' => 'Subject updated']);
}

if ($method === 'DELETE') {
    $data = getInput();
    requireFields(['id'], $data);
    $db->prepare('DELETE FROM subjects WHERE id = ?')->execute([(int)$data['id']]);
    jsonSuccess(['message' => 'Subject deleted']);
}
