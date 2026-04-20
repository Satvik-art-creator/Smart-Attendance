<?php
/**
 * CSV Report Download
 * GET: ?subject_id=X&semester=Y&section=Z
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = requireLogin('teacher');

if (empty($_GET['subject_id']) || empty($_GET['semester']) || empty($_GET['section'])) {
    header('Content-Type: application/json');
    jsonError('subject_id, semester, and section required');
}

$subjectId = (int) $_GET['subject_id'];
$semester  = (int) $_GET['semester'];
$section   = sanitize($_GET['section']);

$db = getDB();

// Get subject name
$stmt = $db->prepare('SELECT subject_code, subject_name FROM subjects WHERE id = ?');
$stmt->execute([$subjectId]);
$subject = $stmt->fetch();

// Total sessions
$stmt = $db->prepare('
    SELECT COUNT(*) FROM attendance_sessions
    WHERE subject_id = ? AND semester = ? AND section = ?
      AND status IN ("ended","expired")
');
$stmt->execute([$subjectId, $semester, $section]);
$total = (int) $stmt->fetchColumn();

// Student stats
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
    ORDER BY s.roll_no
');
$stmt->execute([$subjectId, $semester, $section, $semester, $section]);
$students = $stmt->fetchAll();

// Output CSV
$filename = "{$subject['subject_code']}_Sem{$semester}_{$section}_attendance.csv";
header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$filename\"");

$out = fopen('php://output', 'w');
fputcsv($out, ['Roll No', 'Name', 'Present', 'Total', 'Percentage', 'Status']);

foreach ($students as $s) {
    $pct = $total > 0 ? round(($s['present'] / $total) * 100) : 0;
    $status = $pct >= MIN_ATTENDANCE_PERCENT ? 'Safe' : 'Low';
    fputcsv($out, [$s['roll_no'], $s['full_name'], $s['present'], $total, "$pct%", $status]);
}

fclose($out);
