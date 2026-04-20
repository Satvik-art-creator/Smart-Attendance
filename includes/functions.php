<?php
/**
 * Shared Helper Functions
 * Smart Attendance Tracker
 */

require_once __DIR__ . '/../config/database.php';

// ── JSON response ──────────────────────────────────────────

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function jsonSuccess(array $extra = []): void {
    jsonResponse(array_merge(['success' => true], $extra));
}

function jsonError(string $message, int $code = 400): void {
    jsonResponse(['success' => false, 'message' => $message], $code);
}

// ── Input helpers ──────────────────────────────────────────

function getInput(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    return array_merge($_GET, $_POST);
}

function requireFields(array $fields, array $data): void {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            jsonError("Missing required field: $field");
        }
    }
}

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// ── OTP helpers ────────────────────────────────────────────

function generateOTP(): string {
    return str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
}

function isSessionActive(array $session): bool {
    if ($session['status'] !== 'active') return false;
    return strtotime($session['expiry_time']) > time();
}

// ── Attendance stats ───────────────────────────────────────

function getStudentSubjectStats(int $studentId, int $semester, string $section): array {
    $db = getDB();

    // Get all subjects for this semester/section
    $stmt = $db->prepare('
        SELECT s.id, s.subject_code, s.subject_name
        FROM subjects s
        JOIN teacher_subjects ts ON ts.subject_id = s.id
        WHERE ts.semester = ? AND ts.section IN (?, "All", "Both")
        GROUP BY s.id
    ');
    $stmt->execute([$semester, $section]);
    $subjects = $stmt->fetchAll();

    $stats = [];

    foreach ($subjects as $subj) {
        // total sessions for this subject/semester/section
        $stmt = $db->prepare('
            SELECT COUNT(*) as total
            FROM attendance_sessions
            WHERE subject_id = ? AND semester = ? AND section IN (?, "All", "Both")
              AND status IN ("ended","expired")
        ');
        $stmt->execute([$subj['id'], $semester, $section]);
        $total = (int) $stmt->fetchColumn();

        // present count for this student
        $stmt = $db->prepare('
            SELECT COUNT(*) as present
            FROM attendance_records ar
            JOIN attendance_sessions ases ON ases.id = ar.session_id
            WHERE ar.student_id = ?
              AND ases.subject_id = ?
              AND ases.semester = ?
              AND ases.section IN (?, "All", "Both")
        ');
        $stmt->execute([$studentId, $subj['id'], $semester, $section]);
        $present = (int) $stmt->fetchColumn();

        $percent = $total > 0 ? round(($present / $total) * 100) : 0;
        $needed  = classesNeededFor75($present, $total);

        $stats[] = [
            'subject_id'   => $subj['id'],
            'subject_code' => $subj['subject_code'],
            'subject_name' => $subj['subject_name'],
            'present'      => $present,
            'total'        => $total,
            'percent'      => $percent,
            'needed'       => $needed,
            'status'       => ($total === 0 || $percent >= MIN_ATTENDANCE_PERCENT) ? 'safe' : 'low',
        ];
    }

    return $stats;
}

function classesNeededFor75(int $present, int $total): int {
    if ($total === 0) return 0;
    $needed = 0;
    while (($present + $needed) / ($total + $needed) < 0.75) {
        $needed++;
        if ($needed > 500) break; // safety
    }
    return $needed;
}

// ── Active session check ───────────────────────────────────

function expireOldSessions(): void {
    $db = getDB();
    $db->exec("
        UPDATE attendance_sessions
        SET status = 'expired'
        WHERE status = 'active' AND expiry_time < NOW()
    ");
}

function getActiveSessions(int $semester, string $section): array {
    expireOldSessions();
    $db = getDB();

    $stmt = $db->prepare('
        SELECT ases.id, ases.subject_id, s.subject_code, s.subject_name,
               ases.start_time, ases.expiry_time, ases.status,
               t.full_name as teacher_name,
               TIMESTAMPDIFF(SECOND, NOW(), ases.expiry_time) as db_remaining_seconds
        FROM attendance_sessions ases
        JOIN subjects s ON s.id = ases.subject_id
        JOIN teachers t ON t.id = ases.teacher_id
        WHERE ases.semester = ? AND ases.section IN (?, "All", "Both")
          AND ases.status = "active"
          AND ases.expiry_time > NOW()
        ORDER BY ases.start_time DESC
    ');
    $stmt->execute([$semester, $section]);
    $sessions = $stmt->fetchAll();
    
    foreach ($sessions as &$sess) {
        $sess['remaining_seconds'] = max(0, (int) $sess['db_remaining_seconds']);
    }
    return $sessions;
}

// ── CSRF token ─────────────────────────────────────────────

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ── Timetable helpers ──────────────────────────────────────

function getTimetable(int $semester, string $section): array {
    $db = getDB();
    $stmt = $db->prepare('
        SELECT t.id, t.day_name, t.start_time, t.end_time,
               s.subject_code, s.subject_name, s.id as subject_id
        FROM timetable t
        JOIN subjects s ON s.id = t.subject_id
        WHERE t.semester = ? AND t.section IN (?, "All", "Both")
        ORDER BY FIELD(t.day_name, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"),
                 t.start_time
    ');
    $stmt->execute([$semester, $section]);
    return $stmt->fetchAll();
}
