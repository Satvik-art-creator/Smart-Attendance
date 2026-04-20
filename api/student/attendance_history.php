<?php
/**
 * Student Attendance History
 * GET: optional ?subject_id=X
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('student');

$studentId = $user['id'];
$db = getDB();

$sql = '
    SELECT ar.present_time, ar.marked_by,
           s.subject_code, s.subject_name,
           ases.start_time as session_date,
           t.full_name as teacher_name
    FROM attendance_records ar
    JOIN attendance_sessions ases ON ases.id = ar.session_id
    JOIN subjects s ON s.id = ases.subject_id
    JOIN teachers t ON t.id = ases.teacher_id
    WHERE ar.student_id = ?
';
$params = [$studentId];

if (!empty($_GET['subject_id'])) {
    $sql .= ' AND ases.subject_id = ?';
    $params[] = (int) $_GET['subject_id'];
}

$sql .= ' ORDER BY ar.present_time DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);

jsonSuccess(['history' => $stmt->fetchAll()]);
