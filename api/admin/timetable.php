<?php
/**
 * Admin Timetable CRUD
 * GET: ?semester=X&section=Y
 * POST: create slot { day_name, start_time, end_time, subject_id, semester, section }
 * PUT: update slot { id, ... }
 * DELETE: { id }
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('admin');
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = '
        SELECT t.*, s.subject_code, s.subject_name
        FROM timetable t
        JOIN subjects s ON s.id = t.subject_id
        WHERE 1=1
    ';
    $params = [];
    if (!empty($_GET['semester'])) { $sql .= ' AND t.semester = ?'; $params[] = (int)$_GET['semester']; }
    if (!empty($_GET['section']))  { $sql .= ' AND t.section = ?';  $params[] = sanitize($_GET['section']); }

    $sql .= ' ORDER BY FIELD(t.day_name,"Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"), t.start_time';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonSuccess(['timetable' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = getInput();
    requireFields(['day_name','start_time','end_time','subject_id','semester','section'], $data);

    $stmt = $db->prepare('
        INSERT INTO timetable (day_name, start_time, end_time, subject_id, semester, section)
        VALUES (?,?,?,?,?,?)
    ');
    $stmt->execute([
        sanitize($data['day_name']),
        sanitize($data['start_time']),
        sanitize($data['end_time']),
        (int)$data['subject_id'],
        (int)$data['semester'],
        sanitize($data['section']),
    ]);
    jsonSuccess(['message'=>'Slot created','id'=>(int)$db->lastInsertId()]);
}

if ($method === 'PUT') {
    $data = getInput();
    requireFields(['id'], $data);
    $fields = []; $params = [];
    foreach (['day_name','start_time','end_time','subject_id','semester','section'] as $f) {
        if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = sanitize($data[$f]); }
    }
    if (empty($fields)) jsonError('Nothing to update');
    $params[] = (int)$data['id'];
    $db->prepare('UPDATE timetable SET '.implode(', ',$fields).' WHERE id = ?')->execute($params);
    jsonSuccess(['message'=>'Slot updated']);
}

if ($method === 'DELETE') {
    $data = getInput();
    requireFields(['id'], $data);
    $db->prepare('DELETE FROM timetable WHERE id = ?')->execute([(int)$data['id']]);
    jsonSuccess(['message'=>'Slot deleted']);
}
