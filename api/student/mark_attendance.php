<?php
/**
 * Mark Attendance via OTP
 * POST: { session_id, otp }
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$data = getInput();
requireFields(['session_id', 'otp'], $data);

$sessionId = (int) $data['session_id'];
$otp       = sanitize($data['otp']);
$studentId = $user['id'];
$semester  = (int) $_SESSION['semester'];
$section   = $_SESSION['section'];

$db = getDB();

// Expire old sessions first
expireOldSessions();

// 1. Check session exists & is active
$stmt = $db->prepare('SELECT * FROM attendance_sessions WHERE id = ?');
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session) {
    jsonError('Session not found');
}

if ($session['status'] !== 'active') {
    jsonError('This session has ended or expired');
}

// Expiry is solely handled by expireOldSessions() querying DB time.

// 2. Check OTP
if ($session['otp_code'] !== $otp) {
    jsonError('Incorrect OTP code');
}

// 3. Check student belongs to this section/semester
if ((int) $session['semester'] !== $semester || !in_array($session['section'], [$section, 'All', 'Both'])) {
    jsonError('This session is not for your section');
}

// 4. Check not already marked
$stmt = $db->prepare('SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?');
$stmt->execute([$sessionId, $studentId]);

if ($stmt->fetch()) {
    jsonError('Attendance already marked for this session');
}

// 5. Mark present
$stmt = $db->prepare('
    INSERT INTO attendance_records (session_id, student_id, present_time, marked_by)
    VALUES (?, ?, NOW(), "otp")
');
$stmt->execute([$sessionId, $studentId]);

jsonSuccess(['message' => 'Attendance marked successfully!']);
