<?php
/**
 * Student Dashboard API
 * Returns: timetable, attendance stats, active sessions, calendar data, notices
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('student');

$semester = (int) $_SESSION['semester'];
$section  = $_SESSION['section'];
$studentId = $user['id'];

// ── Timetable ──
$timetable = getTimetable($semester, $section);

// ── Subject stats ──
$stats = getStudentSubjectStats($studentId, $semester, $section);

// ── Overall stats ──
$totalPresent = array_sum(array_column($stats, 'present'));
$totalClasses = array_sum(array_column($stats, 'total'));
$overallPercent = $totalClasses > 0 ? round(($totalPresent / $totalClasses) * 100) : 0;
$lowSubjects = count(array_filter($stats, fn($s) => $s['status'] === 'low'));

// Priority & best subjects
$markedStats = array_filter($stats, fn($s) => $s['total'] > 0);
$priority = null;
$best = null;

if (!empty($markedStats)) {
    $priority = array_reduce($markedStats, fn($carry, $s) =>
        ($carry === null || $s['percent'] < $carry['percent']) ? $s : $carry
    );
    $best = array_reduce($markedStats, fn($carry, $s) =>
        ($carry === null || $s['percent'] > $carry['percent']) ? $s : $carry
    );
}

// ── Active sessions ──
$activeSessions = getActiveSessions($semester, $section);

// Check which ones this student already marked
$db = getDB();
foreach ($activeSessions as &$sess) {
    $stmt = $db->prepare('SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?');
    $stmt->execute([$sess['id'], $studentId]);
    $sess['already_marked'] = (bool) $stmt->fetch();
}
unset($sess);

// ── Calendar data (dates with attendance) ──
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$year  = isset($_GET['year'])  ? (int) $_GET['year']  : (int) date('Y');

$stmt = $db->prepare('
    SELECT DISTINCT DATE(ases.start_time) as att_date
    FROM attendance_records ar
    JOIN attendance_sessions ases ON ases.id = ar.session_id
    WHERE ar.student_id = ?
      AND MONTH(ases.start_time) = ?
      AND YEAR(ases.start_time) = ?
');
$stmt->execute([$studentId, $month, $year]);
$markedDates = array_column($stmt->fetchAll(), 'att_date');

// ── Notices ──
$stmt = $db->prepare('
    SELECT title, message, created_by, created_at
    FROM notices
    WHERE role_type IN ("all", "student")
    ORDER BY created_at DESC
    LIMIT 10
');
$stmt->execute();
$notices = $stmt->fetchAll();

jsonSuccess([
    'student' => [
        'name'      => $user['name'],
        'roll_no'   => $user['identifier'],
        'semester'  => $semester,
        'section'   => $section,
    ],
    'overall' => [
        'percent'      => $overallPercent,
        'present'      => $totalPresent,
        'total'        => $totalClasses,
        'low_subjects' => $lowSubjects,
    ],
    'priority'        => $priority,
    'best'            => $best,
    'subjects'        => $stats,
    'timetable'       => $timetable,
    'active_sessions' => $activeSessions,
    'marked_dates'    => $markedDates,
    'notices'         => $notices,
]);
