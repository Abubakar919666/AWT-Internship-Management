-- =======================================================
-- AWT Intern Management System (AWT-IMS)
-- Database Schema for MySQL 8.0+ / MariaDB 10.4+
-- Organization: Alamgir Welfare Trust Int'l (AWT)
-- =======================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `task_submissions`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `certificates`;
DROP TABLE IF EXISTS `intern_evaluations`;
DROP TABLE IF EXISTS `interns`;
DROP TABLE IF EXISTS `supervisors`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------
-- 1. Table: settings
-- Global organization configuration and defaults
-- -------------------------------------------------------
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Table: users
-- Core authentication table for Admin, Supervisor, Intern
-- -------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `username` VARCHAR(100) NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'supervisor', 'intern') NOT NULL DEFAULT 'intern',
    `intern_id` INT NULL,
    `supervisor_id` INT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `avatar` VARCHAR(255) NULL DEFAULT 'assets/images/default-avatar.png',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_role` (`role`),
    INDEX `idx_user_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. Table: supervisors
-- Organization supervisors and department heads
-- -------------------------------------------------------
CREATE TABLE `supervisors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(50) NULL,
    `department` VARCHAR(100) NULL,
    `designation` VARCHAR(100) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_supervisor_dept` (`department`),
    CONSTRAINT `fk_supervisor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 4. Table: interns
-- Fully preserves all 60 columns from existing MS Access Student table
-- -------------------------------------------------------
CREATE TABLE `interns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `sname` VARCHAR(200) NOT NULL,
    `Father_name` VARCHAR(200) NULL,
    `Cellnumber` VARCHAR(100) NULL,
    `Email` VARCHAR(150) NULL,
    `Address` TEXT NULL,
    `sInstitute` VARCHAR(200) NULL,
    `Degree` VARCHAR(150) NULL,
    `sterm` VARCHAR(100) NULL,
    `Location` VARCHAR(150) NULL,
    `ERPNO` VARCHAR(100) NULL,
    
    -- Internship Timing & Sessions
    `iyear` VARCHAR(20) NULL,
    `Summer` TINYINT(1) NOT NULL DEFAULT 0,
    `Hours` VARCHAR(100) NULL,
    `dateassignfrom` DATETIME NULL,
    `dateassignto` DATETIME NULL,
    `dateprefferedfrom` DATETIME NULL,
    `dateprefferedto` DATETIME NULL,
    `DateofInternship` DATETIME NULL,
    `Dateofentry` DATETIME NULL,
    `Date_of_Submission` DATETIME NULL,
    
    -- Grouping
    `GroupName` INT NULL,
    `GroupTime` VARCHAR(100) NULL,
    `GroupN` VARCHAR(100) NULL,
    
    -- Status & Workflow
    `confirmed` TINYINT(1) NOT NULL DEFAULT 0,
    `Waiting` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('pending', 'confirmed', 'waiting', 'active', 'completed', 'terminated') NOT NULL DEFAULT 'pending',
    
    -- Organization & Mentorship
    `Organizationname` VARCHAR(200) DEFAULT 'Alamgir Welfare Trust Int''l',
    `Department` VARCHAR(150) NULL,
    `Supervisor` VARCHAR(150) NULL,
    `supervisor_id` INT NULL,
    `Mentor` VARCHAR(150) NULL,
    `Designationanddepartment` VARCHAR(200) NULL,
    `HRperson` VARCHAR(150) DEFAULT 'Nisar Ahmed',
    `HRdesignation` VARCHAR(150) DEFAULT 'Coordinator',
    
    -- Document Submission Checklist (True/False in Access)
    `Request_Form` TINYINT(1) NOT NULL DEFAULT 0,
    `Photograph` TINYINT(1) NOT NULL DEFAULT 0,
    `CV` TINYINT(1) NOT NULL DEFAULT 0,
    `Recommendation_Letter` TINYINT(1) NOT NULL DEFAULT 0,
    `CNIC_copy` TINYINT(1) NOT NULL DEFAULT 0,
    `Student_ID` TINYINT(1) NOT NULL DEFAULT 0,
    `photo` VARCHAR(255) NULL,
    
    -- Performance Appraisal Scores (1 to 10 scale)
    `punctuality` INT DEFAULT 0,
    `regularity` INT DEFAULT 0,
    `productivity` INT DEFAULT 0,
    `relationship_with_others` INT DEFAULT 0,
    `Initiative` INT DEFAULT 0,
    `Maturity` INT DEFAULT 0,
    `Confidence` INT DEFAULT 0,
    `Analytical_ability` INT DEFAULT 0,
    `abilityhardword` INT DEFAULT 0,
    `knowledge` INT DEFAULT 0,
    `total_score` INT GENERATED ALWAYS AS (
        `punctuality` + `regularity` + `productivity` + `relationship_with_others` +
        `Initiative` + `Maturity` + `Confidence` + `Analytical_ability` +
        `abilityhardword` + `knowledge`
    ) STORED,
    
    -- Work, Assignments & Feedback
    `Assignedwork` TEXT NULL,
    `comments` TEXT NULL,
    `Assignment1` TINYINT(1) NOT NULL DEFAULT 0,
    `Assignment2` TINYINT(1) NOT NULL DEFAULT 0,
    `Assignment3` TINYINT(1) NOT NULL DEFAULT 0,
    `Assignment4` TINYINT(1) NOT NULL DEFAULT 0,
    `Assignment5` TINYINT(1) NOT NULL DEFAULT 0,
    `Assignments` TEXT NULL,
    `Remarks` TEXT NULL,
    `Certificate_Period_Detail` VARCHAR(255) NULL,
    
    -- System timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_intern_institute` (`sInstitute`),
    INDEX `idx_intern_year` (`iyear`),
    INDEX `idx_intern_status` (`status`),
    INDEX `idx_intern_confirmed` (`confirmed`),
    INDEX `idx_intern_waiting` (`Waiting`),
    INDEX `idx_intern_dept` (`Department`),
    CONSTRAINT `fk_intern_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_intern_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 5. Table: tasks
-- Task assignment and milestone tracking
-- -------------------------------------------------------
CREATE TABLE `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `intern_id` INT NOT NULL,
    `supervisor_id` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `priority` ENUM('Low', 'Medium', 'High', 'Urgent') NOT NULL DEFAULT 'Medium',
    `status` ENUM('Pending', 'In Progress', 'Completed', 'Under Review') NOT NULL DEFAULT 'Pending',
    `due_date` DATE NULL,
    `assigned_date` DATE NOT NULL DEFAULT (CURRENT_DATE),
    `completed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_task_intern` (`intern_id`),
    INDEX `idx_task_status` (`status`),
    CONSTRAINT `fk_task_intern` FOREIGN KEY (`intern_id`) REFERENCES `interns` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_task_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 6. Table: task_submissions
-- Intern task work submissions and supervisor feedback
-- -------------------------------------------------------
CREATE TABLE `task_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT NOT NULL,
    `intern_id` INT NOT NULL,
    `submission_text` TEXT NOT NULL,
    `file_path` VARCHAR(255) NULL,
    `file_name` VARCHAR(255) NULL,
    `feedback` TEXT NULL,
    `status` ENUM('Submitted', 'Changes Requested', 'Approved') NOT NULL DEFAULT 'Submitted',
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME NULL,
    CONSTRAINT `fk_sub_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sub_intern` FOREIGN KEY (`intern_id`) REFERENCES `interns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 7. Table: attendance
-- Daily attendance recording and historical reports
-- -------------------------------------------------------
CREATE TABLE `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `intern_id` INT NOT NULL,
    `attendance_date` DATE NOT NULL,
    `status` ENUM('Present', 'Absent', 'Leave', 'Late') NOT NULL DEFAULT 'Present',
    `time_in` TIME NULL,
    `time_out` TIME NULL,
    `remarks` VARCHAR(255) NULL,
    `marked_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_intern_date` (`intern_id`, `attendance_date`),
    INDEX `idx_att_date` (`attendance_date`),
    INDEX `idx_att_status` (`status`),
    CONSTRAINT `fk_att_intern` FOREIGN KEY (`intern_id`) REFERENCES `interns` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_user` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 8. Table: certificates
-- Issued internship certificates with verification token
-- -------------------------------------------------------
CREATE TABLE `certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `intern_id` INT NOT NULL,
    `certificate_no` VARCHAR(100) NOT NULL UNIQUE,
    `issue_date` DATE NOT NULL,
    `period_detail` VARCHAR(255) NULL,
    `hours_duration` VARCHAR(100) NULL,
    `qr_token` VARCHAR(64) NOT NULL UNIQUE,
    `status` ENUM('Draft', 'Issued', 'Revoked') NOT NULL DEFAULT 'Issued',
    `remarks` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cert_token` (`qr_token`),
    CONSTRAINT `fk_cert_intern` FOREIGN KEY (`intern_id`) REFERENCES `interns` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cert_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 9. Table: activity_logs
-- System audit trail and security logs
-- -------------------------------------------------------
CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_log_user` (`user_id`),
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
