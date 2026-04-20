<?php
/**
 * Admin Teacher-Subject Assignments
 * GET: list all or ?teacher_id=X
 * POST: assign { teacher_id, subject_id, semester, section }
 * DELETE: unassign { id }
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('admin');
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = '
        SELECT ts.id, ts.teacher_id, ts.subject_id, ts.semester, ts.section,
               t.teacher_code, t.full_name as teacher_name,
               s.subject_code, s.subject_name
        FROM teacher_subjects ts
        JOIN teachers t ON t.id = ts.teacher_id
        JOIN subjects s ON s.id = ts.subject_id
    ';
    $params = [];
    if (!empty($_GET['teacher_id'])) {
        $sql .= ' WHERE ts.teacher_id = ?';
        $params[] = (int)$_GET['teacher_id'];
    }
    $sql .= ' ORDER BY t.teacher_code, s.subject_code';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonSuccess(['assignments' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = getInput();
    requireFields(['teacher_id','subject_id','semester','section'], $data);

    try {
        $stmt = $db->prepare('INSERT INTO teacher_subjects (teacher_id, subject_id, semester, section) VALUES (?,?,?,?)');
        $stmt->execute([(int)$data['teacher_id'], (int)$data['subject_id'], (int)$data['semester'], sanitize($data['section'])]);
        jsonSuccess(['message'=>'Assignment created','id'=>(int)$db->lastInsertId()]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) jsonError('This assignment already exists');
        throw $e;
    }
}

if ($method === 'DELETE') {
    $data = getInput();
    requireFields(['id'], $data);
    $db->prepare('DELETE FROM teacher_subjects WHERE id = ?')->execute([(int)$data['id']]);
    jsonSuccess(['message'=>'Assignment removed']);
}
