-- ============================================================
-- STUDENT GRADES SYSTEM - DATABASE SCHEMA
-- One-to-Many: Student (parent) -> Grades (child)
-- ============================================================

CREATE DATABASE IF NOT EXISTS student_grades_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_grades_db;

-- ----------------------------------------
-- USERS TABLE: stores system login accounts
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------
-- STUDENTS TABLE: parent entity
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_no   VARCHAR(20)  NOT NULL UNIQUE,
    first_name   VARCHAR(100) NOT NULL,
    last_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    course       VARCHAR(100) NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------
-- GRADES TABLE: child entity (many per student)
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS grades (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT          NOT NULL,
    subject     VARCHAR(100) NOT NULL,
    grade       DECIMAL(5,2) NOT NULL,
    semester    VARCHAR(50)  NOT NULL,
    school_year VARCHAR(20)  NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ----------------------------------------
-- ACTIVITY LOGS TABLE: audit trail
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL,
    action      VARCHAR(20)  NOT NULL,   -- CREATE, READ, UPDATE, DELETE
    entity      VARCHAR(50)  NOT NULL,   -- students, grades, users
    description TEXT         NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
