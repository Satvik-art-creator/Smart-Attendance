<?php
/**
 * Admin Reports & Analytics
 * GET: system-wide attendance stats
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('admin');
$db = getDB();

// Counts
$totalStudents  = (int) $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalTeachers  = (int) $db->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
$totalSubjects  = (int) $db->query('SELECT COUNT(*) FROM subjects')->fetchColumn();
$totalSessions  = (int) $db->query('SELECT COUNT(*) FROM attendance_sessions')->fetchColumn();

// Overall attendance
$totalRecords = (int) $db->query('SELECT COUNT(*) FROM attendance_records')->fetchColumn();
$totalPossible = 0;

// Calculate total possible = sum(students_in_section * sessions_for_that_section)
$stmt = $db->query('
    SELECT ases.semester, ases.section, COUNT(*) as session_count
    FROM attendance_sessions ases
    WHERE ases.status IN ("ended","expired")
    GROUP BY ases.semester, ases.section
');
$sectionSessions = $stmt->fetchAll();

foreach ($sectionSessions as $ss) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM students WHERE semester = ? AND section = ?');
    $stmt->execute([$ss['semester'], $ss['section']]);
    $studentCount = (int) $stmt->fetchColumn();
    $totalPossible += $studentCount * $ss['session_count'];
}

$overallPercent = $totalPossible > 0 ? round(($totalRecords / $totalPossible) * 100) : 0;

// Defaulters count (students below 75% in any subject)
$defaulterIds = [];
$stmt = $db->query('SELECT DISTINCT semester, section FROM students');
$sections = $stmt->fetchAll();

foreach ($sections as $sec) {
    $stmt = $db->prepare('SELECT id FROM students WHERE semester = ? AND section = ?');
    $stmt->execute([$sec['semester'], $sec['section']]);
    $students = $stmt->fetchAll();

    foreach ($students as $s) {
        $stats = getStudentSubjectStats($s['id'], $sec['semester'], $sec['section']);
        foreach ($stats as $stat) {
            if ($stat['status'] === 'low') {
                $defaulterIds[$s['id']] = true;
                break;
            }
        }
    }
}
$defaulterCount = count($defaulterIds);

// Subject-wise average
$stmt = $db->query('
    SELECT s.subject_code, s.subject_name,
           COUNT(DISTINCT ases.id) as total_sessions,
           COUNT(ar.id) as total_present
    FROM subjects s
    LEFT JOIN attendance_sessions ases ON ases.subject_id = s.id AND ases.status IN ("ended","expired")
    LEFT JOIN attendance_records ar ON ar.session_id = ases.id
    GROUP BY s.id
    ORDER BY s.subject_code
');
$subjectStats = $stmt->fetchAll();

// Recent sessions
$stmt = $db->query('
    SELECT ases.id, ases.start_time, ases.status,
           s.subject_code, ases.semester, ases.section,
           t.full_name as teacher_name,
           (SELECT COUNT(*) FROM attendance_records WHERE session_id = ases.id) as marked
    FROM attendance_sessions ases
    JOIN subjects s ON s.id = ases.subject_id
    JOIN teachers t ON t.id = ases.teacher_id
    ORDER BY ases.start_time DESC
    LIMIT 20
');
$recentSessions = $stmt->fetchAll();

jsonSuccess([
    'counts' => [
        'students'   => $totalStudents,
        'teachers'   => $totalTeachers,
        'subjects'   => $totalSubjects,
        'sessions'   => $totalSessions,
        'defaulters' => $defaulterCount,
    ],
    'overall_percent'  => $overallPercent,
    'subject_stats'    => $subjectStats,
    'recent_sessions'  => $recentSessions,
]);
