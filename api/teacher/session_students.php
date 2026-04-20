<?php
/**
 * Get students who marked attendance in a session
 * GET: ?session_id=X
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('teacher');

if (empty($_GET['session_id'])) jsonError('session_id required');

$sessionId = (int) $_GET['session_id'];
$db = getDB();

// Verify session belongs to teacher
$stmt = $db->prepare('SELECT * FROM attendance_sessions WHERE id = ? AND teacher_id = ?');
$stmt->execute([$sessionId, $user['id']]);
$session = $stmt->fetch();

if (!$session) jsonError('Session not found');

// Get marked students
$stmt = $db->prepare('
    SELECT s.id, s.roll_no, s.full_name, ar.present_time, ar.marked_by
    FROM attendance_records ar
    JOIN students s ON s.id = ar.student_id
    WHERE ar.session_id = ?
    ORDER BY ar.present_time ASC
');
$stmt->execute([$sessionId]);
$marked = $stmt->fetchAll();

// Get all students in this section who haven't marked
if (in_array($session['section'], ['All', 'Both'])) {
    $stmt = $db->prepare('
        SELECT s.id, s.roll_no, s.full_name
        FROM students s
        WHERE s.semester = ?
          AND s.id NOT IN (
              SELECT student_id FROM attendance_records WHERE session_id = ?
          )
        ORDER BY s.roll_no
    ');
    $stmt->execute([$session['semester'], $sessionId]);
} else {
    $stmt = $db->prepare('
        SELECT s.id, s.roll_no, s.full_name
        FROM students s
        WHERE s.semester = ? AND s.section = ?
          AND s.id NOT IN (
              SELECT student_id FROM attendance_records WHERE session_id = ?
          )
        ORDER BY s.roll_no
    ');
    $stmt->execute([$session['semester'], $session['section'], $sessionId]);
}
$absent = $stmt->fetchAll();

// Check real-time status
expireOldSessions();
$stmt = $db->prepare('SELECT status, expiry_time FROM attendance_sessions WHERE id = ?');
$stmt->execute([$sessionId]);
$currentStatus = $stmt->fetch();

jsonSuccess([
    'session'  => $session,
    'status'   => $currentStatus['status'],
    'remaining_seconds' => max(0, strtotime($currentStatus['expiry_time']) - time()),
    'marked'   => $marked,
    'absent'   => $absent,
    'total_marked' => count($marked),
    'total_absent' => count($absent),
]);
