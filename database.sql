-- ============================================================
-- database.sql
-- Student Academic Result Dissemination System
-- Federal University of Lafia - BSc Final Year Project
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+01:00';  -- WAT (West Africa Time)

-- ─── 1. Admin / Staff Users ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(80)     NOT NULL UNIQUE,
    `password`   VARCHAR(255)    NOT NULL COMMENT 'Bcrypt hash',
    `full_name`  VARCHAR(150)    NOT NULL,
    `role`       ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 2. Students ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `students` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `matric_no`    VARCHAR(20)   NOT NULL UNIQUE COMMENT 'Matriculation number, e.g. CSC/2021/001',
    `full_name`    VARCHAR(150)  NOT NULL,
    `phone_number` VARCHAR(20)   NOT NULL COMMENT 'E.164 format, e.g. +2348012345678',
    `department`   VARCHAR(100)  NOT NULL,
    `level`        SMALLINT      NOT NULL DEFAULT 100 COMMENT '100, 200, 300, 400, 500',
    `email`        VARCHAR(150)          DEFAULT NULL,
    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_matric_no` (`matric_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 3. Results ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `results` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`   INT UNSIGNED NOT NULL,
    `semester`     ENUM('First','Second') NOT NULL,
    `session`      VARCHAR(10)  NOT NULL COMMENT 'e.g. 2023/2024',
    `course_code`  VARCHAR(15)  NOT NULL COMMENT 'e.g. CSC 301',
    `course_title` VARCHAR(150) NOT NULL,
    `credit_unit`  TINYINT      NOT NULL DEFAULT 2,
    `score`        DECIMAL(5,2) NOT NULL DEFAULT 0,
    `grade`        CHAR(1)      NOT NULL COMMENT 'A B C D E F',
    `grade_point`  DECIMAL(3,1) NOT NULL DEFAULT 0.0,
    `total`        DECIMAL(8,2) GENERATED ALWAYS AS (`credit_unit` * `grade_point`) STORED,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_student_semester` (`student_id`, `semester`, `session`),
    CONSTRAINT `fk_results_student`
        FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 4. SMS Logs ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sms_logs` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `message`    TEXT         NOT NULL,
    `status`     ENUM('sent','failed','pending') NOT NULL DEFAULT 'pending',
    `twilio_sid` VARCHAR(60)  DEFAULT NULL COMMENT 'Twilio message SID for reference',
    `date_sent`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sms_student` (`student_id`),
    CONSTRAINT `fk_sms_student`
        FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SAMPLE DATA (useful during development and project defense)
-- ============================================================

-- Default admin: username=admin  password=admin123
INSERT IGNORE INTO `users` (`username`, `password`, `full_name`, `role`) VALUES
('admin', '$2y$10$h8UqGkzBauJZKoN16nBrSu3wk6B.LBhOCi.3P9TRzNf1cQJVuE4ZS', 'System Administrator', 'admin'),
('staff1', '$2y$10$h8UqGkzBauJZKoN16nBrSu3wk6B.LBhOCi.3P9TRzNf1cQJVuE4ZS', 'Dr. Abubakar Sani', 'staff');

-- Sample students
INSERT IGNORE INTO `students` (`matric_no`, `full_name`, `phone_number`, `department`, `level`, `email`) VALUES
('CSC/2021/001', 'Aminu Ibrahim Mohammed', '+2348012345678', 'Computer Science', 300, 'aminu@student.fulafia.edu.ng'),
('CSC/2021/002', 'Fatima Usman Aliyu',    '+2348023456789', 'Computer Science', 300, 'fatima@student.fulafia.edu.ng'),
('CSC/2021/003', 'Chukwuemeka Obi',       '+2348034567890', 'Computer Science', 300, 'emeka@student.fulafia.edu.ng'),
('EEE/2021/001', 'Musa Abdullahi Bello',  '+2348045678901', 'Electrical Engineering', 300, 'musa@student.fulafia.edu.ng'),
('MTH/2021/001', 'Aisha Garba Umar',      '+2348056789012', 'Mathematics',         300, 'aisha@student.fulafia.edu.ng');

-- Sample results (First Semester 2023/2024)
INSERT IGNORE INTO `results` (`student_id`, `semester`, `session`, `course_code`, `course_title`, `credit_unit`, `score`, `grade`, `grade_point`) VALUES
(1, 'First', '2023/2024', 'CSC 301', 'Data Structures & Algorithms', 3, 78.5, 'A', 5.0),
(1, 'First', '2023/2024', 'CSC 303', 'Computer Architecture',        3, 62.0, 'B', 4.0),
(1, 'First', '2023/2024', 'CSC 305', 'Operating Systems',            3, 55.0, 'C', 3.0),
(1, 'First', '2023/2024', 'MTH 301', 'Numerical Methods',            3, 48.0, 'D', 2.0),
(1, 'First', '2023/2024', 'GST 301', 'Entrepreneurship Studies',     2, 70.0, 'A', 5.0),
(2, 'First', '2023/2024', 'CSC 301', 'Data Structures & Algorithms', 3, 85.0, 'A', 5.0),
(2, 'First', '2023/2024', 'CSC 303', 'Computer Architecture',        3, 71.0, 'A', 5.0),
(2, 'First', '2023/2024', 'CSC 305', 'Operating Systems',            3, 66.0, 'B', 4.0),
(2, 'First', '2023/2024', 'MTH 301', 'Numerical Methods',            3, 59.0, 'C', 3.0),
(2, 'First', '2023/2024', 'GST 301', 'Entrepreneurship Studies',     2, 75.0, 'A', 5.0),
(3, 'First', '2023/2024', 'CSC 301', 'Data Structures & Algorithms', 3, 42.0, 'E', 1.0),
(3, 'First', '2023/2024', 'CSC 303', 'Computer Architecture',        3, 35.0, 'F', 0.0),
(3, 'First', '2023/2024', 'CSC 305', 'Operating Systems',            3, 50.0, 'C', 3.0),
(3, 'First', '2023/2024', 'MTH 301', 'Numerical Methods',            3, 61.0, 'B', 4.0),
(3, 'First', '2023/2024', 'GST 301', 'Entrepreneurship Studies',     2, 68.0, 'B', 4.0);
