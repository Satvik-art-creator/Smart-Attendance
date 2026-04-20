<?php
/**
 * XAMPP Setup Script — IIITN CSH Branch
 * Run this once to:
 *  1. Create the database and tables
 *  2. Seed sample data with proper password hashes
 *  3. Verify everything works
 *
 * Access via: http://localhost/ap/setup/install.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Setup — Smart Attendance Tracker</title>";
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
  .creds { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
  .cred-card { padding: 14px; border-radius: 8px; border: 1px solid #dde4ed; }
  .cred-card h3 { margin: 0 0 8px; font-size: .95rem; }
  .cred-card code { display: block; margin: 4px 0; font-size: .85rem; }
</style></head><body><div class='card'>";

echo "<h1>🚀 Smart Attendance Tracker — Setup (IIITN CSH)</h1>";

$host = 'localhost';
$user = 'root';
$pass = '';

// Step 1: Connect to MySQL
echo "<div class='step'><strong>Step 1:</strong> Connecting to MySQL... ";
try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "<span class='ok'>✓ Connected</span>";
} catch (PDOException $e) {
    echo "<span class='fail'>✗ Failed: " . $e->getMessage() . "</span>";
    echo "<p class='info'>Make sure XAMPP MySQL is running.</p>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// Step 2: Create database
echo "<div class='step'><strong>Step 2:</strong> Creating database... ";
$pdo->exec("CREATE DATABASE IF NOT EXISTS smart_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "<span class='ok'>✓ Database ready</span></div>";

$pdo->exec("USE smart_attendance");

// Step 3: Run schema
echo "<div class='step'><strong>Step 3:</strong> Creating tables... ";
$schema = file_get_contents(__DIR__ . '/schema.sql');
$schema = preg_replace('/CREATE DATABASE.*?;/s', '', $schema);
$schema = preg_replace('/USE\s+smart_attendance;/i', '', $schema);

$statements = array_filter(array_map('trim', explode(';', $schema)));
$tableCount = 0;
foreach ($statements as $stmt) {
    if (!empty($stmt) && stripos($stmt, 'CREATE') !== false || stripos($stmt, 'INDEX') !== false) {
        try {
            $pdo->exec($stmt);
            $tableCount++;
        } catch (PDOException $e) {
            if ($e->getCode() != '42S01') {
                echo "<br><span class='fail'>Warning: " . $e->getMessage() . "</span>";
            }
        }
    }
}
echo "<span class='ok'>✓ $tableCount statements executed</span></div>";

// Step 4: Seed data with proper hashes
echo "<div class='step'><strong>Step 4:</strong> Seeding IIITN CSH data... ";

$defaultPass = password_hash('password123', PASSWORD_BCRYPT);

try {
    // Check if already seeded
    $check = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($check > 0) {
        echo "<span class='ok'>✓ Data already exists (skipped). Run <a href='reseed.php'>reseed.php</a> to reset.</span>";
    } else {
        // Admin
        $pdo->prepare("INSERT INTO admins (username, full_name, email, password) VALUES (?,?,?,?)")
            ->execute(['admin', 'System Admin', 'admin@iiitn.ac.in', $defaultPass]);

        // Subjects (7 theory + 3 labs)
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
        foreach ($subjects as $s) {
            $pdo->prepare("INSERT INTO subjects (subject_code, subject_name) VALUES (?,?)")->execute($s);
        }

        // Teachers (7)
        $teachers = [
            ['TCH001', 'Dr. Shankar Bhattacharjee', 'shankar.b@iiitn.ac.in'],
            ['TCH002', 'Mr. Daud Ali',              'daud.ali@iiitn.ac.in'],
            ['TCH003', 'Mr. Nikhil',                'nikhil@iiitn.ac.in'],
            ['TCH004', 'Ms. Akansha Goel',          'akansha.goel@iiitn.ac.in'],
            ['TCH005', 'Dr. Aatish Daryapurkar',    'aatish.d@iiitn.ac.in'],
            ['TCH006', 'Dr. Shailesh Janbandhu',    'shailesh.j@iiitn.ac.in'],
            ['TCH007', 'Dr. Santosh Kumar Sahu',    'santosh.sahu@iiitn.ac.in'],
        ];
        foreach ($teachers as $t) {
            $pdo->prepare("INSERT INTO teachers (teacher_code, full_name, email, password) VALUES (?,?,?,?)")
                ->execute([$t[0], $t[1], $t[2], $defaultPass]);
        }

        // Teacher-Subject assignments
        // IDs: 1=AE,2=DS,3=MTTDE,4=APG,5=AP,6=GDDT,7=EVS,8=AE-LAB,9=DS-LAB,10=AP-LAB
        // Teachers: 1=Shankar,2=Daud,3=Nikhil,4=Akansha,5=Aatish,6=Shailesh,7=Santosh
        $assigns = [
            [1,1,2,'A1'],[1,1,2,'A2'],[1,8,2,'A1'],[1,8,2,'A2'],  // Shankar→AE+Lab
            [2,3,2,'A1'],[2,3,2,'A2'],                              // Daud→MTTDE
            [3,6,2,'A1'],[3,6,2,'A2'],                              // Nikhil→GDDT
            [4,5,2,'A1'],[4,5,2,'A2'],[4,10,2,'A1'],[4,10,2,'A2'], // Akansha→AP+Lab
            [5,4,2,'A1'],[5,4,2,'A2'],                              // Aatish→APG
            [6,7,2,'A1'],[6,7,2,'A2'],                              // Shailesh→EVS
            [7,2,2,'A1'],[7,2,2,'A2'],[7,9,2,'A1'],[7,9,2,'A2'],  // Santosh→DS+Lab
        ];
        foreach ($assigns as $a) {
            $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id, semester, section) VALUES (?,?,?,?)")
                ->execute($a);
        }

        // Students (69) — BT25CSH001 to BT25CSH069
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

        for ($i = 1; $i <= 69; $i++) {
            $roll = 'BT25CSH' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $name = $indianNames[$i - 1];
            $emailPrefix = strtolower(str_replace(' ', '.', $name));
            $section = ($i <= 35) ? 'A1' : 'A2';
            $pdo->prepare("INSERT INTO students (roll_no, full_name, email, password, year, semester, section) VALUES (?,?,?,?,?,?,?)")
                ->execute([$roll, $name, "$emailPrefix@iiitn.ac.in", $defaultPass, 1, 2, $section]);
        }

        // Timetable — Theory (both sections)
        $theorySlots = [
            ['Monday','09:00','10:00',1],['Monday','10:00','11:00',2],
            ['Monday','11:00','12:00',3],['Monday','12:00','13:00',4],
            ['Tuesday','09:00','10:00',1],['Tuesday','10:00','11:00',1],
            ['Tuesday','11:00','12:00',4],['Tuesday','12:00','13:00',2],
            ['Wednesday','09:00','10:00',6],['Wednesday','10:00','11:00',5],
            ['Wednesday','11:00','12:00',3],['Wednesday','12:00','13:00',4],
            ['Thursday','09:00','10:00',6],['Thursday','10:00','11:00',5],
            ['Thursday','11:00','12:00',3],['Thursday','12:00','13:00',3],
            ['Thursday','14:00','15:00',7],
            ['Friday','09:00','10:00',3],['Friday','10:00','11:00',5],
            ['Friday','11:00','12:00',7],['Friday','12:00','13:00',2],
        ];
        foreach ($theorySlots as $slot) {
            $pdo->prepare("INSERT INTO timetable (day_name,start_time,end_time,subject_id,semester,section) VALUES (?,?,?,?,?,?)")
                ->execute([$slot[0],$slot[1],$slot[2],$slot[3],2,'A1']);
            $pdo->prepare("INSERT INTO timetable (day_name,start_time,end_time,subject_id,semester,section) VALUES (?,?,?,?,?,?)")
                ->execute([$slot[0],$slot[1],$slot[2],$slot[3],2,'A2']);
        }

        // Timetable — Labs (section-specific)
        $labSlots = [
            ['Monday','15:00','17:00',10,'A2'],  // AP Lab A2
            ['Monday','15:00','17:00',9,'A1'],   // DS Lab A1
            ['Tuesday','15:00','17:00',10,'A1'],  // AP Lab A1
            ['Wednesday','14:00','16:00',8,'A1'], // AE Lab A1
            ['Wednesday','16:00','18:00',8,'A2'], // AE Lab A2
            ['Friday','15:00','17:00',9,'A2'],    // DS Lab A2
        ];
        foreach ($labSlots as $slot) {
            $pdo->prepare("INSERT INTO timetable (day_name,start_time,end_time,subject_id,semester,section) VALUES (?,?,?,?,?,?)")
                ->execute([$slot[0],$slot[1],$slot[2],$slot[3],2,$slot[4]]);
        }

        // Notices
        $notices = [
            ['all','Welcome to Smart Attendance','The Smart Attendance Tracker is now live for CSH Semester 2. Use your IIITN credentials to log in.','System Admin'],
            ['student','Maintain 75% Attendance','All students must maintain at least 75% attendance in every subject (including labs) to be eligible for end-semester exams.','System Admin'],
            ['teacher','OTP Session Guide','OTP sessions are valid for 60 seconds. Students must enter the OTP within this window. You can also end sessions manually.','System Admin'],
        ];
        foreach ($notices as $n) {
            $pdo->prepare("INSERT INTO notices (role_type, title, message, created_by) VALUES (?,?,?,?)")
                ->execute($n);
        }

        echo "<span class='ok'>✓ IIITN CSH data seeded (10 subjects, 7 teachers, 69 students)</span>";
    }
} catch (PDOException $e) {
    echo "<span class='fail'>✗ " . $e->getMessage() . "</span>";
}
echo "</div>";

// Step 5: Verify
echo "<div class='step'><strong>Step 5:</strong> Verification...<br>";
$tables = ['students','teachers','admins','subjects','teacher_subjects','timetable','attendance_sessions','attendance_records','notices'];
echo "<table><tr><th>Table</th><th>Rows</th><th>Status</th></tr>";
$allGood = true;
foreach ($tables as $t) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        $ok = ($t === 'attendance_sessions' || $t === 'attendance_records') ? '✓' : ($count > 0 ? '✓' : '✗');
        $cls = ($t === 'attendance_sessions' || $t === 'attendance_records') ? 'ok' : ($count > 0 ? 'ok' : 'fail');
        echo "<tr><td>$t</td><td>$count</td><td><span class='$cls'>$ok</span></td></tr>";
    } catch (PDOException $e) {
        echo "<tr><td>$t</td><td>—</td><td><span class='fail'>✗ Missing</span></td></tr>";
        $allGood = false;
    }
}
echo "</table>";
if ($allGood) echo "<span class='ok'>✓ All 9 tables verified</span>";
echo "</div>";

// Credentials Summary
echo "<h2 style='margin-top:24px;'>🔑 Test Credentials</h2>";
echo "<p class='info'>All accounts use password: <strong>password123</strong></p>";

echo "<div class='creds'>";
echo "<div class='cred-card' style='border-color:#368f8b;'>
  <h3>👤 Student (A1)</h3>
  <code>Roll: BT25CSH001</code>
  <code>Pass: password123</code>
  <p class='info'>Range: BT25CSH001 – BT25CSH035</p>
</div>";
echo "<div class='cred-card' style='border-color:#368f8b;'>
  <h3>👤 Student (A2)</h3>
  <code>Roll: BT25CSH036</code>
  <code>Pass: password123</code>
  <p class='info'>Range: BT25CSH036 – BT25CSH069</p>
</div>";
echo "<div class='cred-card' style='border-color:#eb5849;'>
  <h3>👨‍🏫 Teacher</h3>
  <code>Code: TCH001</code>
  <code>Pass: password123</code>
  <p class='info'>TCH001–TCH007 (7 teachers)</p>
</div>";
echo "<div class='cred-card' style='border-color:#1d2530;'>
  <h3>🔒 Admin</h3>
  <code>User: admin</code>
  <code>Pass: password123</code>
</div>";
echo "</div>";

echo "<h3 style='margin-top:20px;'>Teacher Mapping</h3>";
echo "<table>
  <tr><th>Code</th><th>Name</th><th>Subject(s)</th></tr>
  <tr><td>TCH001</td><td>Dr. Shankar Bhattacharjee</td><td>AE + AE Lab</td></tr>
  <tr><td>TCH002</td><td>Mr. Daud Ali</td><td>MTTDE</td></tr>
  <tr><td>TCH003</td><td>Mr. Nikhil</td><td>GDDT</td></tr>
  <tr><td>TCH004</td><td>Ms. Akansha Goel</td><td>AP + AP Lab</td></tr>
  <tr><td>TCH005</td><td>Dr. Aatish Daryapurkar</td><td>APG</td></tr>
  <tr><td>TCH006</td><td>Dr. Shailesh Janbandhu</td><td>EVS</td></tr>
  <tr><td>TCH007</td><td>Dr. Santosh Kumar Sahu</td><td>DS + DS Lab</td></tr>
</table>";

echo "<div style='margin-top:24px; padding:16px; background:#e8f7f4; border-radius:8px;'>
  <strong>✅ Setup complete!</strong>
  <p style='margin:8px 0 0;'>Go to <a href='/ap/login.html'>/ap/login.html</a> to start using the system.</p>
</div>";

echo "</div></body></html>";
