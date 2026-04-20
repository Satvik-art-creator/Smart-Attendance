<?php
/**
 * Authentication & Session Management
 * Smart Attendance Tracker
 */

session_start();

require_once __DIR__ . '/../config/database.php';

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

/**
 * Get the current user session data
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;

    return [
        'id'   => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'name' => $_SESSION['user_name'] ?? '',
        'identifier' => $_SESSION['identifier'] ?? '',
    ];
}

/**
 * Require login — returns JSON error + exits if not authenticated
 */
function requireLogin(string $role = ''): array {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    if ($role && $_SESSION['role'] !== $role) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    return getCurrentUser();
}

/**
 * Authenticate user against the database
 */
function authenticate(string $role, string $identifier, string $password): ?array {
    $db = getDB();

    switch ($role) {
        case 'student':
            $stmt = $db->prepare('SELECT id, roll_no AS identifier, full_name, password, semester, section FROM students WHERE roll_no = ?');
            break;

        case 'teacher':
            $stmt = $db->prepare('SELECT id, teacher_code AS identifier, full_name, password FROM teachers WHERE teacher_code = ?');
            break;

        case 'admin':
            $stmt = $db->prepare('SELECT id, username AS identifier, full_name, password FROM admins WHERE username = ?');
            break;

        default:
            return null;
    }

    $stmt->execute([$identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return null;
    }

    // Set session
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['role']       = $role;
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['identifier'] = $user['identifier'];

    if ($role === 'student') {
        $_SESSION['semester'] = $user['semester'];
        $_SESSION['section']  = $user['section'];
    }

    unset($user['password']);
    return $user;
}

/**
 * Destroy session and log out
 */
function logout(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }

    session_destroy();
}
