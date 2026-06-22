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
    course VARCHAR(100) NULL,
    phone_number VARCHAR(20) NOT NULL,
    photo_path VARCHAR(255) NULL,
    sports_house ENUM('Amethyst', 'Amber', 'Sapphire', 'Jade') NULL,
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

CREATE TABLE IF NOT EXISTS intakes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intake_name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO intakes (intake_name) VALUES
    ('Intake 16'),
    ('Intake 17'),
    ('Intake 18'),
    ('Intake 19'),
    ('Intake 20');

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

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    event_name VARCHAR(120) NOT NULL,
    event_date DATE NOT NULL,
    start_time TIME NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_club
        FOREIGN KEY (club_id) REFERENCES clubs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
