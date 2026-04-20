<?php
/**
 * Manual Mark Attendance (Teacher marks student)
 * POST: { session_id, student_id, action: "present"|"absent" }
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$data = getInput();
requireFields(['session_id', 'student_id', 'action'], $data);

$sessionId = (int) $data['session_id'];
$studentId = (int) $data['student_id'];
$action    = sanitize($data['action']);

$db = getDB();

// Verify session belongs to teacher
$stmt = $db->prepare('SELECT * FROM attendance_sessions WHERE id = ? AND teacher_id = ?');
$stmt->execute([$sessionId, $user['id']]);
if (!$stmt->fetch()) jsonError('Session not found');

if ($action === 'present') {
    // Insert or ignore (if already present)
    $stmt = $db->prepare('
        INSERT IGNORE INTO attendance_records (session_id, student_id, present_time, marked_by)
        VALUES (?, ?, NOW(), "teacher")
    ');
    $stmt->execute([$sessionId, $studentId]);
    jsonSuccess(['message' => 'Student marked present']);

} elseif ($action === 'absent') {
    // Remove attendance record
    $stmt = $db->prepare('DELETE FROM attendance_records WHERE session_id = ? AND student_id = ?');
    $stmt->execute([$sessionId, $studentId]);
    jsonSuccess(['message' => 'Student marked absent']);

} else {
    jsonError('Invalid action. Use "present" or "absent".');
}
