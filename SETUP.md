# XAMPP Configuration for Smart Attendance Tracker

## Quick Setup Guide

### Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org
2. Install to `E:\xampp` (or your preferred E: drive location)
3. Start **Apache** and **MySQL** from XAMPP Control Panel

### Step 2: Configure Apache to Serve the Project
Since the project is at `E:\ap`, you need to add an alias in Apache.

Open file: `E:\xampp\apache\conf\httpd.conf`

Add this at the end:
```apache
# Smart Attendance Tracker
Alias /ap "E:/ap"
<Directory "E:/ap">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php index.html
</Directory>
```

Then restart Apache from XAMPP Control Panel.

### Step 3: Run the Installer
1. Open browser: http://localhost/ap/setup/install.php
2. This will automatically:
   - Create the `smart_attendance` database
   - Create all 9 tables
   - Seed sample data (students, teachers, admin, subjects, timetable)
3. You'll see ✓ green checkmarks for each step

### Step 4: Access the App
- Login page: http://localhost/ap/login.html
- Direct links (after login):
  - Student: http://localhost/ap/student/
  - Teacher: http://localhost/ap/teacher/
  - Admin: http://localhost/ap/admin/

### Test Credentials (all password: `password123`)

| Role         | ID                            | Example         |
|--------------|-------------------------------|-----------------|
| Student (A1) | BT25CSH001 – BT25CSH035      | BT25CSH001      |
| Student (A2) | BT25CSH036 – BT25CSH069      | BT25CSH036      |
| Teacher      | TCH001 – TCH007               | TCH001           |
| Admin        | admin                         | admin            |

#### Teacher Codes

| Code   | Name                        | Subject(s)         |
|--------|-----------------------------|--------------------|
| TCH001 | Dr. Shankar Bhattacharjee   | AE, AE Lab         |
| TCH002 | Mr. Daud Ali                | MTTDE              |
| TCH003 | Mr. Nikhil                  | GDDT               |
| TCH004 | Ms. Akansha Goel            | AP, AP Lab          |
| TCH005 | Dr. Aatish Daryapurkar      | APG                |
| TCH006 | Dr. Shailesh Janbandhu      | EVS                |
| TCH007 | Dr. Santosh Kumar Sahu      | DS, DS Lab          |

### Troubleshooting

**Apache won't start?**
- Check if port 80 is in use (Skype, IIS)
- Use XAMPP → Config → change port to 8080

**MySQL won't start?**
- Make sure no other MySQL service is running

**"Access denied" error?**
- Default MySQL user is `root` with empty password
- Edit `E:\ap\config\database.php` if your credentials differ

**404 errors on API?**
- Make sure the Alias directive is added correctly in httpd.conf
- Restart Apache after changes
