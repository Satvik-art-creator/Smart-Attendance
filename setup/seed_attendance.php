<?php
/**
 * Script to seed dummy attendance history data for the past few days.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'smart_attendance';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Ensure students exist
$stmt = $pdo->query("SELECT id, section FROM students");
$students = $stmt->fetchAll();

if (count($students) === 0) {
    die("No students found. Please run reseed.php first.");
}

// Clear existing attendance
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("TRUNCATE TABLE attendance_records");
$pdo->exec("TRUNCATE TABLE attendance_sessions");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// Get teacher-subjects
$stmt = $pdo->query("SELECT * FROM teacher_subjects");
$tsList = $stmt->fetchAll();

// 10 days of history
$days = 14; 
$todayTs = time();

$sessionCount = 0;
$recordCount = 0;

for ($i = $days; $i >= 1; $i--) {
    $dateTs = $todayTs - ($i * 86400);
    $dayOfWeek = date('N', $dateTs);
    
    // Skip weekends
    if ($dayOfWeek == 6 || $dayOfWeek == 7) {
        continue;
    }
    
    $dateStr = date('Y-m-d', $dateTs);
    
    // Pick 3 random teacher-subjects per day
    shuffle($tsList);
    $dailyTs = array_slice($tsList, 0, 4);
    
    // Times
    $startTimes = ["10:00:00", "11:30:00", "13:00:00", "15:00:00"];
    
    foreach ($dailyTs as $index => $ts) {
        // Create session
        $startTime = $dateStr . " " . $startTimes[$index];
        $expiryTimeTs = strtotime($startTime) + 300; // 5 mins active
        $expiryTime = date('Y-m-d H:i:s', $expiryTimeTs);
        
        $stmt = $pdo->prepare("INSERT INTO attendance_sessions (teacher_id, subject_id, semester, section, otp_code, start_time, expiry_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'ended', ?)");
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt->execute([
            $ts['teacher_id'],
            $ts['subject_id'],
            $ts['semester'],
            $ts['section'],
            $otp,
            $startTime,
            $expiryTime,
            $startTime
        ]);
        $sessionId = $pdo->lastInsertId();
        $sessionCount++;
        
        // Add attendance records
        foreach ($students as $student) {
            // Check section match
            if ($ts['section'] === 'All' || $ts['section'] === $student['section']) {
                // 85% chance to be present
                if (rand(1, 100) <= 85) {
                    // Mark present
                    $presentTimeTs = strtotime($startTime) + rand(10, 200);
                    $presentTime = date('Y-m-d H:i:s', $presentTimeTs);
                    
                    $stmtRec = $pdo->prepare("INSERT INTO attendance_records (session_id, student_id, present_time, marked_by) VALUES (?, ?, ?, ?)");
                    $stmtRec->execute([
                        $sessionId,
                        $student['id'],
                        $presentTime,
                        'otp'
                    ]);
                    $recordCount++;
                }
            }
        }
    }
}

echo "Successfully seeded $sessionCount attendance sessions and $recordCount attendance records.\n";
