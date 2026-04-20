-- Smart Attendance Tracker — Database Schema
-- Run this file once to set up the database

CREATE DATABASE IF NOT EXISTS smart_attendance
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smart_attendance;

-- ============================================================
-- STUDENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  roll_no     VARCHAR(30)  NOT NULL UNIQUE,
  full_name   VARCHAR(120) NOT NULL,
  email       VARCHAR(150) DEFAULT NULL,
  password    VARCHAR(255) NOT NULL,
  year        TINYINT      NOT NULL DEFAULT 1,
  semester    TINYINT      NOT NULL DEFAULT 1,
  section     VARCHAR(10)  NOT NULL DEFAULT 'A1',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TEACHERS
-- ============================================================
CREATE TABLE IF NOT EXISTS teachers (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  teacher_code  VARCHAR(30)  NOT NULL UNIQUE,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(150) DEFAULT NULL,
  password      VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- ADMINS (separate table as per user choice)
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50)  NOT NULL UNIQUE,
  full_name   VARCHAR(120) NOT NULL,
  email       VARCHAR(150) DEFAULT NULL,
  password    VARCHAR(255) NOT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SUBJECTS
-- ============================================================
CREATE TABLE IF NOT EXISTS subjects (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  subject_code  VARCHAR(20)  NOT NULL UNIQUE,
  subject_name  VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

-- ============================================================
-- TEACHER ↔ SUBJECT mapping (which teacher teaches which subject for which semester/section)
-- ============================================================
CREATE TABLE IF NOT EXISTS teacher_subjects (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id  INT         NOT NULL,
  subject_id  INT         NOT NULL,
  semester    TINYINT     NOT NULL,
  section     VARCHAR(10) NOT NULL,
  UNIQUE KEY uq_teacher_subject_section (teacher_id, subject_id, semester, section),
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TIMETABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS timetable (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  day_name    ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  start_time  TIME        NOT NULL,
  end_time    TIME        NOT NULL,
  subject_id  INT         NOT NULL,
  semester    TINYINT     NOT NULL,
  section     VARCHAR(10) NOT NULL,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ATTENDANCE SESSIONS (created by teacher when starting OTP)
-- ============================================================
CREATE TABLE IF NOT EXISTS attendance_sessions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id  INT         NOT NULL,
  subject_id  INT         NOT NULL,
  semester    TINYINT     NOT NULL,
  section     VARCHAR(10) NOT NULL,
  otp_code    VARCHAR(6)  NOT NULL,
  start_time  DATETIME    NOT NULL,
  expiry_time DATETIME    NOT NULL,
  status      ENUM('active','expired','ended') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_session_active ON attendance_sessions(status, expiry_time);

-- ============================================================
-- ATTENDANCE RECORDS (one row per student per session)
-- ============================================================
CREATE TABLE IF NOT EXISTS attendance_records (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  session_id   INT NOT NULL,
  student_id   INT NOT NULL,
  present_time DATETIME NOT NULL,
  marked_by    ENUM('otp','teacher') NOT NULL DEFAULT 'otp',
  UNIQUE KEY uq_session_student (session_id, student_id),
  FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id)            ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- NOTICES
-- ============================================================
CREATE TABLE IF NOT EXISTS notices (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  role_type   ENUM('all','student','teacher') NOT NULL DEFAULT 'all',
  subject_id  INT DEFAULT NULL,
  title       VARCHAR(200) NOT NULL,
  message     TEXT         NOT NULL,
  created_by  VARCHAR(100) NOT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
) ENGINE=InnoDB;
