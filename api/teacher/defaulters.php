<?php
/**
 * Defaulters List (students below 75%)
 * GET: ?subject_id=X&semester=Y&section=Z  (or without params for all assigned)
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('teacher');

$db = getDB();
$teacherId = $user['id'];

$subjectId = !empty($_GET['subject_id']) ? (int) $_GET['subject_id'] : null;
$semester  = !empty($_GET['semester'])   ? (int) $_GET['semester']   : null;
$section   = !empty($_GET['section'])    ? sanitize($_GET['section']) : null;

// Get assigned subjects
$sql = 'SELECT ts.subject_id, ts.semester, ts.section, s.subject_code, s.subject_name
        FROM teacher_subjects ts
        JOIN subjects s ON s.id = ts.subject_id
        WHERE ts.teacher_id = ?';
$params = [$teacherId];

if ($subjectId) {
    $sql .= ' AND ts.subject_id = ?';
    $params[] = $subjectId;
}
if ($semester) {
    $sql .= ' AND ts.semester = ?';
    $params[] = $semester;
}
if ($section) {
    $sql .= ' AND ts.section = ?';
    $params[] = $section;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll();

$defaulters = [];

foreach ($assignments as $assign) {
    // Total sessions
    $stmt = $db->prepare('
        SELECT COUNT(*) FROM attendance_sessions
        WHERE subject_id = ? AND semester = ? AND section = ?
          AND status IN ("ended","expired")
    ');
    $stmt->execute([$assign['subject_id'], $assign['semester'], $assign['section']]);
    $totalSessions = (int) $stmt->fetchColumn();

    if ($totalSessions === 0) continue;

    // Students with attendance below 75%
    $stmt = $db->prepare('
        SELECT s.roll_no, s.full_name, COUNT(ar.id) as present
        FROM students s
        LEFT JOIN attendance_records ar ON ar.student_id = s.id
            AND ar.session_id IN (
                SELECT id FROM attendance_sessions
                WHERE subject_id = ? AND semester = ? AND section = ?
                  AND status IN ("ended","expired")
            )
        WHERE s.semester = ? AND s.section = ?
        GROUP BY s.id
        HAVING (COUNT(ar.id) / ?) * 100 < ?
    ');
    $stmt->execute([
        $assign['subject_id'], $assign['semester'], $assign['section'],
        $assign['semester'], $assign['section'],
        $totalSessions, MIN_ATTENDANCE_PERCENT
    ]);

    $lowStudents = $stmt->fetchAll();
    foreach ($lowStudents as &$ls) {
        $ls['total']   = $totalSessions;
        $ls['percent'] = round(($ls['present'] / $totalSessions) * 100);
        $ls['subject'] = $assign['subject_code'];
    }

    $defaulters = array_merge($defaulters, $lowStudents);
}

jsonSuccess(['defaulters' => $defaulters]);
