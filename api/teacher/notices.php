<?php
/**
 * Teacher Notices
 * GET: list notices
 * POST: { title, message, subject_id? } create notice for subject students
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
$user = requireLogin('teacher');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = getInput();
    requireFields(['title', 'message'], $data);

    $stmt = $db->prepare('
        INSERT INTO notices (role_type, subject_id, title, message, created_by)
        VALUES ("student", ?, ?, ?, ?)
    ');
    $stmt->execute([
        !empty($data['subject_id']) ? (int) $data['subject_id'] : null,
        sanitize($data['title']),
        sanitize($data['message']),
        $user['name']
    ]);

    jsonSuccess(['message' => 'Notice created']);
}

// GET — list notices by this teacher
$stmt = $db->prepare('
    SELECT id, title, message, created_at
    FROM notices
    WHERE created_by = ?
    ORDER BY created_at DESC
');
$stmt->execute([$user['name']]);

jsonSuccess(['notices' => $stmt->fetchAll()]);
