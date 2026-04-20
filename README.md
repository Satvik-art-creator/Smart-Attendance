# Smart Attendance Tracker 🎓

A modern, role-based web application for tracking student attendance using real-time OTP verification. Built with PHP, MySQL, and Vanilla CSS (Glassmorphism design), it provides dedicated dashboards for Students, Teachers, and System Admins.

## Features ✨
- **Role-Based Access Control**: Separate secure portals for Students, Teachers, and Admins.
- **OTP-Based Attendance**: Teachers can initiate live attendance sessions generating a 6-digit OTP valid for a configured duration, which students must submit to be marked present.
- **Dynamic Dashboards**: Real-time attendance statistics, recent activity timeline, and class subject mapping summaries.
- **Glassmorphic UI**: Premium, modern user interface implemented completely in Vanilla CSS for smooth responsiveness.

## Tech Stack 🛠️
- **Frontend**: HTML5, Vanilla JS, CSS3 (Glassmorphism & Custom properties)
- **Backend**: Native PHP (PDO for secure DB interaction)
- **Database**: MySQL

## Setup Instructions 🚀

1. Clone the repository to your local web server (XAMPP / Laragon etc).
   ```bash
   git clone https://github.com/Satvik-art-creator/Smart-Attendance.git
   ```
2. Set up the Database:
   Navigate to the `/setup` folder and configure database settings via `install.php`, or run the provided `schema.sql` directly into MySQL.
3. Seeding Dummy Data (Optional):
   Run `/setup/reseed.php` to inject complete mock data for students, teachers, subjects, and timetables. You can also run `/setup/seed_attendance.php` to populate historical attendance.
4. Open the application by navigating to the project's root folder `http://localhost/your_folder/`.

## Default Credentials 🔑
(After running the setup/seed scripts):
- **Admin**: `admin` / `password123`
- **Teacher**: `TCH001` to `TCH007` / `password123`
- **Student**: `BT25CSH001` to `BT25CSH069` / `password123`

---
*Created dynamically for efficient university class management.*
