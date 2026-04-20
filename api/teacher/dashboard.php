<?php
/**
 * Teacher Dashboard & Reports API
 * GET: returns assigned subjects, session history, stats
 * GET ?subject_id=X&semester=Y&section=Z : detailed attendance report
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('teacher');

$db = getDB();
$teacherId = $user['id'];

// ── Assigned subjects ──
$stmt = $db->prepare('
    SELECT ts.id as assignment_id, ts.subject_id, ts.semester, ts.section,
           s.subject_code, s.subject_name
    FROM teacher_subjects ts
    JOIN subjects s ON s.id = ts.subject_id
    WHERE ts.teacher_id = ?
    ORDER BY ts.semester, ts.section, s.subject_code
');
$stmt->execute([$teacherId]);
$assignments = $stmt->fetchAll();

// ── Check for active session ──
expireOldSessions();
$stmt = $db->prepare('
    SELECT ases.*, s.subject_code, s.subject_name,
           TIMESTAMPDIFF(SECOND, NOW(), ases.expiry_time) as db_remaining_seconds
    FROM attendance_sessions ases
    JOIN subjects s ON s.id = ases.subject_id
    WHERE ases.teacher_id = ? AND ases.status = "active"
    LIMIT 1
');
$stmt->execute([$teacherId]);
$activeSession = $stmt->fetch() ?: null;

if ($activeSession) {
    $activeSession['remaining_seconds'] = max(0, (int)$activeSession['db_remaining_seconds']);
}

// ── Detailed report for a specific subject ──
if (!empty($_GET['subject_id'])) {
    $subjectId = (int) $_GET['subject_id'];
    $semester  = (int) ($_GET['semester'] ?? 0);
    $section   = sanitize($_GET['section'] ?? '');

    // All sessions for this subject
    $stmt = $db->prepare('
        SELECT id, start_time, status,
               (SELECT COUNT(*) FROM attendance_records WHERE session_id = attendance_sessions.id) as marked_count
        FROM attendance_sessions
        WHERE teacher_id = ? AND subject_id = ? AND semester = ? AND section = ?
        ORDER BY start_time DESC
    ');
    $stmt->execute([$teacherId, $subjectId, $semester, $section]);
    $sessions = $stmt->fetchAll();

    // Per-student stats
    $stmt = $db->prepare('
        SELECT s.id, s.roll_no, s.full_name,
               COUNT(ar.id) as present,
               (SELECT COUNT(*) FROM attendance_sessions
                WHERE subject_id = ? AND semester = ? AND section = ?
                  AND status IN ("ended","expired")) as total
        FROM students s
        LEFT JOIN attendance_records ar ON ar.student_id = s.id
            AND ar.session_id IN (
                SELECT id FROM attendance_sessions
                WHERE subject_id = ? AND semester = ? AND section = ?
            )
        WHERE s.semester = ? AND s.section = ?
        GROUP BY s.id
        ORDER BY s.roll_no
    ');
    $stmt->execute([$subjectId, $semester, $section, $subjectId, $semester, $section, $semester, $section]);
    $studentStats = $stmt->fetchAll();

    foreach ($studentStats as &$ss) {
        $ss['percent'] = $ss['total'] > 0 ? round(($ss['present'] / $ss['total']) * 100) : 0;
        $ss['status']  = ($ss['total'] == 0 || $ss['percent'] >= MIN_ATTENDANCE_PERCENT) ? 'safe' : 'low';
    }

    jsonSuccess([
        'report' => [
            'sessions'      => $sessions,
            'student_stats' => $studentStats,
        ]
    ]);
}

// ── Recent sessions history ──
$stmt = $db->prepare('
    SELECT ases.id, ases.start_time, ases.status,
           s.subject_code, ases.semester, ases.section,
           (SELECT COUNT(*) FROM attendance_records WHERE session_id = ases.id) as marked_count
    FROM attendance_sessions ases
    JOIN subjects s ON s.id = ases.subject_id
    WHERE ases.teacher_id = ?
    ORDER BY ases.start_time DESC
    LIMIT 20
');
$stmt->execute([$teacherId]);
$recentSessions = $stmt->fetchAll();

// ── Notices ──
$stmt = $db->prepare('
    SELECT title, message, created_by, created_at
    FROM notices
    WHERE role_type IN ("all", "teacher")
    ORDER BY created_at DESC LIMIT 10
');
$stmt->execute();
$notices = $stmt->fetchAll();

jsonSuccess([
    'teacher' => [
        'name'         => $user['name'],
        'teacher_code' => $user['identifier'],
    ],
    'assignments'     => $assignments,
    'active_session'  => $activeSession,
    'recent_sessions' => $recentSessions,
    'notices'         => $notices,
]);
