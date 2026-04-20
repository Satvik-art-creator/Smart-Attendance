<?php
/**
 * Start Attendance Session
 * POST: { subject_id, semester, section }
 * Returns OTP code + session ID
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$data = getInput();
requireFields(['subject_id', 'semester', 'section'], $data);

$subjectId = (int) $data['subject_id'];
$semester  = (int) $data['semester'];
$section   = sanitize($data['section']);
$teacherId = $user['id'];

$db = getDB();

// Verify teacher is assigned to this subject/section
$stmt = $db->prepare('
    SELECT id FROM teacher_subjects
    WHERE teacher_id = ? AND subject_id = ? AND semester = ? AND section = ?
');
$stmt->execute([$teacherId, $subjectId, $semester, $section]);

if (!$stmt->fetch()) {
    jsonError('You are not assigned to this subject/section');
}

// Check no active session already exists for this teacher
expireOldSessions();
$stmt = $db->prepare('
    SELECT id FROM attendance_sessions
    WHERE teacher_id = ? AND status = "active"
');
$stmt->execute([$teacherId]);

if ($stmt->fetch()) {
    jsonError('You already have an active session. End it first.');
}

// Generate OTP and create session
$otp = generateOTP();

$stmt = $db->prepare('
    INSERT INTO attendance_sessions
    (teacher_id, subject_id, semester, section, otp_code, start_time, expiry_time, status)
    VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND), "active")
');
$stmt->execute([$teacherId, $subjectId, $semester, $section, $otp, OTP_EXPIRY_SECONDS]);

$sessionId = (int) $db->lastInsertId();

$stmt = $db->prepare('SELECT start_time, expiry_time, TIMESTAMPDIFF(SECOND, NOW(), expiry_time) as remaining_seconds FROM attendance_sessions WHERE id = ?');
$stmt->execute([$sessionId]);
$sessionTimes = $stmt->fetch();
$startTime = $sessionTimes['start_time'];
$expiryTime = $sessionTimes['expiry_time'];
$remainingSeconds = max(0, (int) $sessionTimes['remaining_seconds']);

// Get subject info
$stmt = $db->prepare('SELECT subject_code, subject_name FROM subjects WHERE id = ?');
$stmt->execute([$subjectId]);
$subject = $stmt->fetch();

jsonSuccess([
    'session_id'   => $sessionId,
    'otp'          => $otp,
    'start_time'   => $startTime,
    'expiry_time'  => $expiryTime,
    'subject'      => $subject,
    'semester'     => $semester,
    'section'      => $section,
    'expiry_seconds' => $remainingSeconds,
]);
