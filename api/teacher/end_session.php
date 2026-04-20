<?php
/**
 * End Attendance Session
 * POST: { session_id }
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$data = getInput();
requireFields(['session_id'], $data);

$sessionId = (int) $data['session_id'];
$db = getDB();

// Verify session belongs to this teacher
$stmt = $db->prepare('SELECT * FROM attendance_sessions WHERE id = ? AND teacher_id = ?');
$stmt->execute([$sessionId, $user['id']]);
$session = $stmt->fetch();

if (!$session) jsonError('Session not found');
if ($session['status'] !== 'active') jsonError('Session is already ended');

$stmt = $db->prepare('UPDATE attendance_sessions SET status = "ended" WHERE id = ?');
$stmt->execute([$sessionId]);

// Get count of students marked
$stmt = $db->prepare('SELECT COUNT(*) FROM attendance_records WHERE session_id = ?');
$stmt->execute([$sessionId]);
$count = (int) $stmt->fetchColumn();

jsonSuccess([
    'message'       => 'Session ended',
    'students_marked' => $count,
]);
