# 📚 Developer Learning Guide: Smart Attendance Tracker

This document provides a comprehensive walkthrough of the internal logic, core technologies, and primary features of the **Smart Attendance Tracker**. It is strictly structured to serve as a study guide and a presentation aid for explaining the project to evaluators or instructors.

---

## 🌟 1. Project Features & Functions

The application operates on a strict **Role-Based Access Control (RBAC)** model, compartmentalizing functionalities specifically tailored to Three core user types:

### 🎒 Students
- **Real-Time OTP Verification Mechanism:** Students enter an active 6-digit OTP distributed by the teacher to mark themselves present securely within a strictly limited timeframe (e.g., 60-300 seconds).
- **Attendance Aggregation Dashboard:** Dynamically plots historic attendance via charting libraries (e.g., Chart.js) and aggregates data (present vs. absent quotas) mapped specifically to the student's designated semester and section.
- **Access Restrictions:** Students are completely sandboxed from modifying sessions manually and modifying system metrics.

### 👨‍🏫 Teachers
- **Session Initialization Controller:** Teachers securely initiate class sessions by selecting mapped parameters (subject + section) yielding a dynamically generated OTP paired securely against a database countdown timer.
- **Live Roster Validation:** Dashboard automatically updates as students invoke the OTP via REST APIs on the backend in real-time.
- **Manual Data Override:** A built-in safety net allows teachers to manually alter false negatives, mark legitimate late students as present, or extract structured historical attendance metrics (CSVs).

### ⚙️ Admins
- **Database Modifiers:** Admins hold standard CRUD configurations over entities like Timetables, Users, and Course mappings natively.

---

## 🛠️ 2. The Tech Stack: How Things Connect

The architecture utilizes a completely decoupled Client-Server model relying heavily on AJAX calls:
* **Frontend:** Glassmorphism UI achieved via Vanilla CSS (Custom Variables + Backdrop-filters) and interactive state management utilizing Vanilla Javascript (`fetch` APIs).
* **Backend:** Built strictly using native Object-Oriented PHP logic. API endpoints act as REST endpoints, decoding incoming JSON payloads and returning strict JSON outputs.
* **Database:** MySQL heavily utilizing Foreign Key constraints (`ON DELETE CASCADE`) to ensure data integrity seamlessly executed via **PDO (PHP Data Objects)**.

---

## 💻 3. Core Coding Logic Explained

### A. The PHP Backend Logic
The core component of this project resides in robust, secure execution mechanisms. All inputs validate against SQL Injections using **PDO Prepared Statements**.

**Code Showcase: Initializing an OTP Session (`api/teacher/start_session.php`)**
```php
// Step 1. Ensure strictly authenticated JSON environment
header('Content-Type: application/json');
$user = requireLogin('teacher');

// Step 2. Verify mapping to prevent Teachers from starting rogue classes
$stmt = $db->prepare('
    SELECT id FROM teacher_subjects
    WHERE teacher_id = ? AND subject_id = ? AND semester = ? AND section = ?
');
$stmt->execute([$teacherId, $subjectId, $semester, $section]);

if (!$stmt->fetch()) jsonError('You are not assigned to this subject/section');

// Step 3. Generate randomized OTP & Input directly into DB with expiry
$otp = generateOTP(); 

$stmt = $db->prepare('
    INSERT INTO attendance_sessions
    (teacher_id, subject_id, semester, section, otp_code, start_time, expiry_time, status)
    VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND), "active")
');
$stmt->execute([$teacherId, $subjectId, $semester, $section, $otp, OTP_EXPIRY_SECONDS]);
```
**Explanation for Teacher:** 
*We don't rely securely on JS for timers. We strictly tell MySQL to set an exact `expiry_time` explicitly utilizing `DATE_ADD(NOW(), INTERVAL ...)`. This reliably removes tampering vulnerabilities.*

**Code Showcase: Student Submission (`api/student/mark_attendance.php`)**
```php
// 1. Database level OTP validation ensuring 0 ambiguity
$stmt = $db->prepare('SELECT status, otp_code, section, semester FROM attendance_sessions WHERE id = ?');
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if ($session['status'] !== 'active') jsonError('This session has expired.');
if ($session['otp_code'] !== $otp)   jsonError('Incorrect OTP.');

// 2. Ensuring the student doesn't vote twice 
$stmt = $db->prepare('SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?');
$stmt->execute([$sessionId, $studentId]);

if ($stmt->fetch()) jsonError('Attendance already marked.');

// 3. Mark safely
$stmt = $db->prepare('INSERT INTO attendance_records (session_id, student_id, present_time, marked_by) VALUES (?, ?, NOW(), "otp")');
$stmt->execute([$sessionId, $studentId]);
```

### B. The JavaScript Frontend (Asynchronous Operations)
To provide users with an uninterrupted "App-Like" mechanism minimizing page refreshes, vanilla JS extensively calls endpoints completely behind the scenes utilizing `async/await`.

**Code Showcase: JS Handling Form Submissions smoothly (`assets/js/student.js`)**
```javascript
async function submitOTP(sessionId, otpCode) {
    try {
        const response = await fetch('/api/student/mark_attendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: sessionId, otp: otpCode })
        });
        
        const result = await response.json();
        
        // DOM dynamic updates natively without reloading
        if (result.success) {
            showNotification('Attendance Marked successfully!', 'success');
            renderAttendanceChart(); // Silently reload chart UI
        } else {
            showNotification(result.error, 'error');
        }
    } catch (e) {
        showNotification('Fatal network error occurred', 'error');
    }
}
```

### C. MySQL Relational Schema Structure
The schema is strategically broken into interconnected associative entities emphasizing strict normalization.

1. **`students` / `teachers`**: Standard user profiles secured strictly using PHP's robust dynamically salted `password_hash(PASSWORD_BCRYPT)`.
2. **`teacher_subjects`**: Maps specific subjects and distinct sections exclusively to teachers ensuring standard RBAC restrictions.
3. **`attendance_sessions`**: Highly crucial tracking schema capturing specific timeframe metadata: `(id, teacher_id, otp_code, start_time, expiry_time, status["active", "expired"])`.
4. **`attendance_records`**: Captures explicit definitive interactions: `(session_id, student_id, present_time, marked_by["otp", "teacher"])`.

**Explanation for Teacher:** 
*The relational setup dictates an explicit cascade logic. If a class subject is deleted arbitrarily, its connected historic attendance data automatically cleans itself safely up minimizing storage bloat using constraints: `FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE`.*

---

## 🎯 Final Presentation Tip
When actively explaining the project architecture, definitively emphasize the following hierarchy:
1. **The aesthetics (UI):** Smooth variables dynamically shifting. 
2. **The logic (JS):** Asynchronous fetches bypassing hard refreshes.
3. **The security (PHP):** Strict environment checks preventing duplicate inputs.
4. **The integrity (MySQL):** Normalized structure executing cascading deletion seamlessly.
