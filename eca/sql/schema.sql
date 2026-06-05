CREATE DATABASE IF NOT EXISTS club_attendance
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE club_attendance;

CREATE TABLE IF NOT EXISTS clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    role ENUM('President', 'Assistant President', 'Facilitator', 'Member') NOT NULL,
    level VARCHAR(20) NOT NULL,
    intake VARCHAR(20) NOT NULL,
    `group` VARCHAR(10) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_members_club
        FOREIGN KEY (club_id) REFERENCES clubs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    member_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member_attendance_date (member_id, date),
    CONSTRAINT fk_attendance_club
        FOREIGN KEY (club_id) REFERENCES clubs(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_attendance_member
        FOREIGN KEY (member_id) REFERENCES members(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
