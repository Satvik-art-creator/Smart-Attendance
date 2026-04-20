# 🎓 Smart Attendance Tracker

Welcome to the **Smart Attendance Tracker**, a modern, digital solution designed to streamline the attendance tracking process for universities and educational institutions. This platform entirely replaces traditional roll-calls and paper-based tracking by introducing a real-time, OTP-driven attendance system that is highly secure, fast, and transparent.

---

## 📖 About the Project

The Smart Attendance Tracker is a comprehensive web application built to simplify classroom management. It enables teachers to effortlessly take attendance by generating a time-sensitive, single-use One-Time Password (OTP). Students, logging in from their respective portals, submit this OTP dynamically during the active session to mark themselves as "Present".

Featuring a premium **Glassmorphous** aesthetic, the application provides an immersive, intuitive dashboard for all users—Students, Teachers, and System Administrators. It operates flawlessly across devices, ensuring everyone has access to critical attendance data and analytics precisely when they need it.

## 🚀 How It Works

1. **Session Initiation:** A teacher logs into their dashboard, selects their assigned subject, section, and semester, and begins a "Live Attendance Session". 
2. **OTP Generation:** The system securely generates a 6-digit OTP displayed on the teacher's screen along with a visual countdown timer (typically 60-300 seconds).
3. **Student Submission:** Students log into their dashboard and securely enter the active OTP before the countdown expires.
4. **Real-time Syncing:** The system instantaneously verifies the OTP. If valid, the student's attendance is accurately recorded in the database, and the teacher's live dashboard updates dynamically with the total present student count.

## ✨ Key Features

### 🧑‍🎓 Student Portal
- **Live OTP Submission:** Intuitive interface to submit dynamically generated OTPs during active classes securely.
- **Attendance History Analytics:** Visual charts and lists summarizing overall localized attendance percentages across all assigned subjects and labs.
- **Notices & Announcements:** Stay rigidly up-to-date with notices broadcasted by administration parameters or teachers.

### 👨‍🏫 Teacher Portal
- **Live Session Management:** Instantly create timed attendance sessions for specific subjects and student subsets (Sections A1, A2, etc.).
- **Manual Intervention:** Administrative capability to manually update or revoke attendance for students with valid reasons (e.g., latecomers or technical issues).
- **Defaulter Analytics:** Generate and view real-time metrics of students falling below mandatory attendance thresholds (e.g., 75%).
- **Documentation Reporting:** Export daily or monthly attendance records as structured CSV files for official collegiate documentation.

### 🦸‍♂️ Admin Console
- **System Overseer:** Full-scale capacity to dynamically onboard, update, or remove Students and Teachers.
- **Subject & Course Mapping:** Easily establish timetables and strictly map which teacher instructs which subjects.
- **System-Wide Alerts:** Broadcast essential localized system-wide alerts to all active portals natively.

---

## 🛠️ Tech Stack

This project was engineered emphasizing native web technologies without heavy JS frameworks to strictly ensure raw performance, tight database security, and modular lightweight styling.

* **Frontend Design:** HTML5, CSS3 (Custom Variables, Modern Glassmorphism UI), Vanilla JavaScript.
* **Server/Backend Logic:** Pure Object-Oriented PHP with Secure HTTP Session Management.
* **Database Management:** MySQL relational database utilizing PDO (PHP Data Objects) for security against potential SQL injections.
* **Architecture Design:** REST-like API endpoints seamlessly passing JSON structures to completely decoupled JavaScript frontends.

---

## ⚙️ How to Run Locally

Follow these simplified instructions to comprehensively set up the Smart Attendance Tracker on your local machine natively.

### Prerequisites
You need a local web server environment capable of concurrently running PHP and MySQL:
- **XAMPP**, **WAMP**, or **Laragon** (Windows)
- **MAMP** (Mac)

### Step-by-Step Installation

1. **Clone the Repository:**
   Open your native shell/terminal and clone the repository directly into your local server’s designated root directory (e.g., `C:\xampp\htdocs\` or `C:\laragon\www\`).
   ```bash
   git clone https://github.com/Satvik-art-creator/Smart-Attendance.git
   ```

2. **Navigate to the Project:**
   Make sure the folder is named appropriately (e.g., `ap` or `Smart-Attendance`) so your localhost can route it easily.
   
3. **Configure Database Connection:**
   Open `/config/database.php` and systematically verify the credentials. By default, it operates utilizing:
   ```php
   $host = 'localhost';
   $dbname = 'smart_attendance';
   $username = 'root';
   $password = ''; // Leave strictly blank if default XAMPP/Laragon
   ```

4. **Initialize the Database Schema:**
   The project actively ships with a built-in installer for simplicity. Open your preferred web browser and organically navigate towards:
   ```text
   http://localhost/your-folder-name/setup/install.php
   ```
   *Alternatively, you can manually import the `/setup/schema.sql` utilizing phpMyAdmin independently.*

5. **Populate Mock Dummy Data (Optional but Highly Recommended):**
   To seamlessly experience the application comprehensively without manually writing base entities, aggressively trigger the localized reseed script:
   ```text
   http://localhost/your-folder-name/setup/reseed.php
   ```
   If you wish to realistically simulate past timeline attendance records (ensuring analytic charts populate dynamically), directly invoke:
   ```text
   http://localhost/your-folder-name/setup/seed_attendance.php
   ```

6. **Start Using the Application:**
   Finally, consistently navigate efficiently back to the application's root directory actively via your browser:
   ```text
   http://localhost/your-folder-name/
   ```

### Default Access Credentials
Assuming you forcefully ran the `reseed.php` script previously, confidently authenticate utilizing these mock profiles natively:
- **System Admin:** 
  - Username: `admin` | Password: `password123`
- **Teacher (Demonstration Proxy):** 
  - Username: `TCH001` | Password: `password123`
- **Student (Demonstration Proxy):** 
  - Username: `BT25CSH001` | Password: `password123`

---

## 🤝 Contribution Guidelines
Contributions, bug issues, and specific functionality feature requests are consistently welcome! Feel free to review the official GitHub repository for more info.

*Developed with ❤️ to powerfully automate and streamline modern educational workflows.*
