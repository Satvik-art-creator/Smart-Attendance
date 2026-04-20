<?php
/**
 * RESEED SCRIPT — Replace all data with real IIITN CSH branch data
 * Run via: http://localhost/ap/setup/reseed.php
 * 
 * WARNING: This will DELETE all existing data!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Reseed — IIITN CSH Data</title>";
echo "<style>
  * { box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f6f8fb; margin: 0; padding: 40px; color: #1d2530; }
  .card { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
  h1 { color: #368f8b; margin-top: 0; }
  .step { padding: 10px 0; border-bottom: 1px solid #eee; }
  .ok { color: #268266; font-weight: 700; }
  .fail { color: #d5483c; font-weight: 700; }
  .info { color: #526070; margin: 6px 0; font-size: .9rem; }
  pre { background: #f0f4f8; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: .85rem; }
  a { color: #368f8b; }
  table { border-collapse: collapse; width: 100%; margin: 10px 0; font-size: .85rem; }
  th, td { border: 1px solid #dde4ed; padding: 6px 10px; text-align: left; }
  th { background: #f0f4f8; }
</style></head><body><div class='card'>";

echo "<h1>🔄 Reseeding — IIITN CSH Branch Data</h1>";

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'smart_attendance';

// Connect
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "<span class='fail'>✗ Connection failed: " . $e->getMessage() . "</span>";
    echo "</div></body></html>";
    exit;
}

$defaultPass = password_hash('password123', PASSWORD_BCRYPT);

// ═══════════════════════════════════════════
// Step 1: Clear all existing data
// ═══════════════════════════════════════════
echo "<div class='step'><strong>Step 1:</strong> Clearing existing data... ";
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE attendance_records");
    $pdo->exec("TRUNCATE TABLE attendance_sessions");
    $pdo->exec("TRUNCATE TABLE notices");
    $pdo->exec("TRUNCATE TABLE timetable");
    $pdo->exec("TRUNCATE TABLE teacher_subjects");
    $pdo->exec("TRUNCATE TABLE students");
    $pdo->exec("TRUNCATE TABLE teachers");
    $pdo->exec("TRUNCATE TABLE admins");
    $pdo->exec("TRUNCATE TABLE subjects");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<span class='ok'>✓ All tables cleared</span>";
} catch (PDOException $e) {
    echo "<span class='fail'>✗ " . $e->getMessage() . "</span>";
}
echo "</div>";

// ═══════════════════════════════════════════
// Step 2: Insert Admin
// ═══════════════════════════════════════════
echo "<div class='step'><strong>Step 2:</strong> Creating admin account... ";
$pdo->prepare("INSERT INTO admins (username, full_name, email, password) VALUES (?,?,?,?)")
    ->execute(['admin', 'System Admin', 'admin@iiitn.ac.in', $defaultPass]);
echo "<span class='ok'>✓ Admin created</span></div>";

// ═══════════════════════════════════════════
// Step 3: Insert Subjects (7 theory + 3 labs = 10)
// ═══════════════════════════════════════════
echo "<div class='step'><strong>Step 3:</strong> Inserting subjects... ";
$subjects = [
    ['AE',     'Applied Electronics'],
    ['DS',     'Data Structures'],
    ['MTTDE',  'Matrices, Transform Techniques, and Differential Equations'],
    ['APG',    'Applied Physics for Gaming'],
    ['AP',     'Application Programming'],
    ['GDDT',   'Game Development and Design Thinking'],
    ['EVS',    'Environmental Studies'],
    ['AE-LAB', 'Applied Electronics Lab'],
    ['DS-LAB', 'Data Structures Lab'],
    ['AP-LAB', 'Application Programming Lab'],
];

$stmtSubj = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name) VALUES (?,?)");
foreach ($subjects as $s) {
    $stmtSubj->execute($s);
}
echo "<span class='ok'>✓ " . count($subjects) . " subjects inserted</span></div>";

// ═══════════════════════════════════════════
// Step 4: Insert Teachers (7)
// ═══════════════════════════════════════════
echo "<div class='step'><strong>Step 4:</strong> Inserting teachers... ";
$teachers = [
    ['TCH001', 'Dr. Shankar Bhattacharjee', 'shankar.b@iiitn.ac.in'],
    ['TCH002', 'Mr. Daud Ali',              'daud.ali@iiitn.ac.in'],
    ['TCH003', 'Mr. Nikhil',                'nikhil@iiitn.ac.in'],
    ['TCH004', 'Ms. Akansha Goel',          'akansha.goel@iiitn.ac.in'],
    ['TCH005', 'Dr. Aatish Daryapurkar',    'aatish.d@iiitn.ac.in'],
    ['TCH006', 'Dr. Shailesh Janbandhu',    'shailesh.j@iiitn.ac.in'],
    ['TCH007', 'Dr. Santosh Kumar Sahu',    'santosh.sahu@iiitn.ac.in'],
];

$stmtTeacher = $pdo->prepare("INSERT INTO teachers (teacher_code, full_name, email, password) VALUES (?,?,?,?)");
foreach ($teachers as $t) {
    $stmtTeacher->execute([$t[0], $t[1], $t[2], $defaultPass]);
}
echo "<span class='ok'>✓ " . count($teachers) . " teachers inserted</span></div>";

// ═══════════════════════════════════════════
// Step 5: Teacher ↔ Subject mapping
// ═══════════════════════════════════════════
echo "<div class='step'><strong>Step 5:</strong> Mapping teachers to subjects... ";

// Subject IDs (auto-increment from 1):
// 1=AE, 2=DS, 3=MTTDE, 4=APG, 5=AP, 6=GDDT, 7=EVS, 8=AE-LAB, 9=DS-LAB, 10=AP-LAB
// Teacher IDs:
// 1=Dr.Shankar(AE), 2=Mr.Daud(MTTDE), 3=Mr.Nikhil(GDDT), 4=Ms.Akansha(AP),
// 5=Dr.Aatish(APG), 6=Dr.Shailesh(EVS), 7=Dr.Santosh(DS)

$assignments = [
    // [teacher_id, subject_id, semester, section]
    // Dr. Shankar Bhattacharjee → AE + AE Lab
    [1, 1, 2, 'All'], // AE (Both Sections)
    [1, 8, 2, 'A1'], [1, 8, 2, 'A2'], // AE Lab

    // Mr. Daud Ali → MTTDE
    [2, 3, 2, 'All'],

    // Mr. Nikhil → GDDT
    [3, 6, 2, 'All'],

    // Ms. Akansha Goel → AP + AP Lab
    [4, 5, 2, 'All'], // AP (Both Sections)
    [4, 10, 2, 'A1'], [4, 10, 2, 'A2'], // AP Lab

    // Dr. Aatish Daryapurkar → APG
    [5, 4, 2, 'All'],

    // Dr. Shailesh Janbandhu → EVS
    [6, 7, 2, 'All'],

    // Dr. Santosh Kumar Sahu → DS + DS Lab
    [7, 2, 2, 'All'], // DS (Both Sections)
    [7, 9, 2, 'A1'], [7, 9, 2, 'A2'], // DS Lab
];

$stmtAssign = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id, semester, section) VALUES (?,?,?,?)");
foreach ($assignments as $a) {
    $stmtAssign->execute($a);
}
echo "<span class='ok'>✓ " . count($assignments) . " assignments created</span></div>";

// ═══════════════════════════════════════════
// Step 6: Insert 69 Students
// ═══════════════════════════════════════════
echo "<div class='step'><strong>Step 6:</strong> Inserting 69 students... ";

$indianNames = [
    'Aarav Sharma', 'Aditi Verma', 'Aditya Patel', 'Akash Gupta', 'Amrita Singh',
    'Ananya Joshi', 'Arjun Reddy', 'Ayesha Khan', 'Bhavya Mehta', 'Chaitanya Rao',
    'Devi Prasad', 'Divya Nair', 'Eshan Kulkarni', 'Gaurav Tiwari', 'Garima Saxena',
    'Harsh Agrawal', 'Isha Deshmukh', 'Jayesh Patil', 'Kavya Iyer', 'Krish Malhotra',
    'Lakshmi Pillai', 'Manav Chauhan', 'Meera Thakur', 'Mohit Yadav', 'Nandini Bose',
    'Nikhil Rathi', 'Nisha Pandey', 'Omkar Jain', 'Pallavi Dubey', 'Pranav Mishra',
    'Priya Sinha', 'Rahul Dwivedi', 'Riya Kapoor', 'Rohan Shukla', 'Sakshi Goyal',
    'Samarth Trivedi', 'Sanvi Bhatt', 'Saurabh Dixit', 'Shruti Bansal', 'Simran Kaur',
    'Sneha Rastogi', 'Sparsh Garg', 'Suhana Bajaj', 'Tanmay Chopra', 'Tanvi Arora',
    'Ujjwal Ranjan', 'Vanshika Seth', 'Varun Choudhary', 'Vidhi Agarwal', 'Vikram Tomar',
    'Yash Srivastava', 'Zara Ahmad', 'Aashish Bhatia', 'Bhumi Thakkar', 'Chirag Solanki',
    'Deepika Rawat', 'Eklavya Negi', 'Falguni Vyas', 'Govind Menon', 'Himani Kashyap',
    'Ishaan Deshpande', 'Jhanvi Chawla', 'Karan Oberoi', 'Lavanya Hegde', 'Madhav Srinivasan',
    'Neha Bhargava', 'Ojas Wagh', 'Pooja Khandelwal', 'Rajat Dangi',
];

$stmtStudent = $pdo->prepare(
    "INSERT INTO students (roll_no, full_name, email, password, year, semester, section) VALUES (?,?,?,?,?,?,?)"
);

for ($i = 1; $i <= 69; $i++) {
    $roll = 'BT25CSH' . str_pad($i, 3, '0', STR_PAD_LEFT);
    $name = $indianNames[$i - 1];
    $emailPrefix = strtolower(str_replace(' ', '.', $name));
    $email = $emailPrefix . '@iiitn.ac.in';
    $section = ($i <= 35) ? 'A1' : 'A2';

    $stmtStudent->execute([$roll, $name, $email, $defaultPass, 1, 2, $section]);
}
echo "<span class='ok'>✓ 69 students inserted (A1: 1–35, A2: 36–69)</span></div>";

// ═══════════════════════════════════════════
// Step 7: Insert Timetable
// ═══════════════════════════════════════════
echo "<div class='step'><strong>Step 7:</strong> Inserting timetable... ";

// Subject IDs: 1=AE, 2=DS, 3=MTTDE, 4=APG, 5=AP, 6=GDDT, 7=EVS, 8=AE-LAB, 9=DS-LAB, 10=AP-LAB

// Theory classes — shared for both A1 & A2
$theorySlots = [
    // Monday
    ['Monday',    '09:00', '10:00', 1],  // AE
    ['Monday',    '10:00', '11:00', 2],  // DS
    ['Monday',    '11:00', '12:00', 3],  // MTTDE
    ['Monday',    '12:00', '13:00', 4],  // APG

    // Tuesday
    ['Tuesday',   '09:00', '10:00', 1],  // AE
    ['Tuesday',   '10:00', '11:00', 1],  // AE
    ['Tuesday',   '11:00', '12:00', 4],  // APG
    ['Tuesday',   '12:00', '13:00', 2],  // DS

    // Wednesday
    ['Wednesday', '09:00', '10:00', 6],  // GDDT
    ['Wednesday', '10:00', '11:00', 5],  // AP
    ['Wednesday', '11:00', '12:00', 3],  // MTTDE
    ['Wednesday', '12:00', '13:00', 4],  // APG

    // Thursday
    ['Thursday',  '09:00', '10:00', 6],  // GDDT
    ['Thursday',  '10:00', '11:00', 5],  // AP
    ['Thursday',  '11:00', '12:00', 3],  // MTTDE
    ['Thursday',  '12:00', '13:00', 3],  // MTTDE Tute-1
    ['Thursday',  '14:00', '15:00', 7],  // EVS (CR-302)

    // Friday
    ['Friday',    '09:00', '10:00', 3],  // MTTDE Tute-2
    ['Friday',    '10:00', '11:00', 5],  // AP
    ['Friday',    '11:00', '12:00', 7],  // EVS
    ['Friday',    '12:00', '13:00', 2],  // DS
];

// Lab classes — section-specific
$labSlots = [
    // Monday
    ['Monday',    '15:00', '17:00', 10, 'A2'],  // AP Lab (A2) Lab 2
    ['Monday',    '15:00', '17:00',  9, 'A1'],  // DS Lab (A1) Lab 6

    // Tuesday
    ['Tuesday',   '15:00', '17:00', 10, 'A1'],  // AP Lab (A1) Lab 8

    // Wednesday
    ['Wednesday', '14:00', '16:00',  8, 'A1'],  // AE Lab (A1) Electronics Lab
    ['Wednesday', '16:00', '18:00',  8, 'A2'],  // AE Lab (A2) Electronics Lab

    // Friday
    ['Friday',    '15:00', '17:00',  9, 'A2'],  // DS Lab (A2) Lab 2
];

$stmtTT = $pdo->prepare(
    "INSERT INTO timetable (day_name, start_time, end_time, subject_id, semester, section) VALUES (?,?,?,?,?,?)"
);

$ttCount = 0;

// Theory — insert for 'All'
foreach ($theorySlots as $slot) {
    $stmtTT->execute([$slot[0], $slot[1], $slot[2], $slot[3], 2, 'All']);
    $ttCount++;
}

// Labs — insert for specified section only
foreach ($labSlots as $slot) {
    $stmtTT->execute([$slot[0], $slot[1], $slot[2], $slot[3], 2, $slot[4]]);
    $ttCount++;
}

echo "<span class='ok'>✓ $ttCount timetable slots inserted</span></div>";

// ═══════════════════════════════════════════
// Step 8: Insert Notices
// ═══════════════════════════════════════════
echo "<div class='step'><strong>Step 8:</strong> Inserting notices... ";
$notices = [
    ['all',     'Welcome to Smart Attendance',        'The Smart Attendance Tracker is now live for CSH Semester 2. Use your IIITN credentials to log in.', 'System Admin'],
    ['student', 'Maintain 75% Attendance',            'All students must maintain at least 75% attendance in every subject (including labs) to be eligible for end-semester exams.', 'System Admin'],
    ['teacher', 'OTP Session Guide',                  'OTP sessions are valid for 60 seconds. Students must enter the OTP within this window. You can also end sessions manually.', 'System Admin'],
];

$stmtNotice = $pdo->prepare("INSERT INTO notices (role_type, title, message, created_by) VALUES (?,?,?,?)");
foreach ($notices as $n) {
    $stmtNotice->execute($n);
}
echo "<span class='ok'>✓ " . count($notices) . " notices inserted</span></div>";

// ═══════════════════════════════════════════
// Step 9: Verification
// ═══════════════════════════════════════════
echo "<div class='step'><strong>Step 9:</strong> Verification...<br>";

$tables = [
    'admins' => 'Admins',
    'subjects' => 'Subjects',
    'teachers' => 'Teachers',
    'teacher_subjects' => 'Teacher-Subject Mappings',
    'students' => 'Students',
    'timetable' => 'Timetable Slots',
    'notices' => 'Notices',
];

echo "<table><tr><th>Table</th><th>Count</th><th>Status</th></tr>";
$allGood = true;
foreach ($tables as $tbl => $label) {
    $count = $pdo->query("SELECT COUNT(*) FROM $tbl")->fetchColumn();
    $ok = $count > 0 ? '✓' : '✗';
    $cls = $count > 0 ? 'ok' : 'fail';
    if ($count == 0) $allGood = false;
    echo "<tr><td>$label</td><td>$count</td><td><span class='$cls'>$ok</span></td></tr>";
}
echo "</table>";

if ($allGood) {
    echo "<span class='ok'>✓ All tables verified</span>";
} else {
    echo "<span class='fail'>✗ Some tables are empty</span>";
}
echo "</div>";

// ═══════════════════════════════════════════
// Summary
// ═══════════════════════════════════════════
echo "<div style='margin-top:24px; padding:16px; background:#e8f7f4; border-radius:8px;'>
  <strong>✅ Reseed complete — IIITN CSH Branch Data</strong>
  <p style='margin:8px 0 0;'>
    <strong>Login credentials</strong> (all use password: <code>password123</code>):
  </p>
  <table style='margin-top:8px;'>
    <tr><th>Role</th><th>ID</th><th>Example</th></tr>
    <tr><td>Student (A1)</td><td>BT25CSH001 – BT25CSH035</td><td>BT25CSH001</td></tr>
    <tr><td>Student (A2)</td><td>BT25CSH036 – BT25CSH069</td><td>BT25CSH036</td></tr>
    <tr><td>Teacher</td><td>TCH001 – TCH007</td><td>TCH001 (Dr. Shankar)</td></tr>
    <tr><td>Admin</td><td>admin</td><td>admin</td></tr>
  </table>
  <p style='margin-top:12px;'>Go to <a href='/ap/login.html'>/ap/login.html</a> to start using the system.</p>
</div>";

echo "</div></body></html>";
