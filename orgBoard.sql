-- 1. Create database and select it
CREATE DATABASE IF NOT EXISTS orgboard_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE orgboard_db;

-- 2. Create tables
-- 2.1 USERS table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    avatar_path VARCHAR(500) NULL,
    role ENUM('Student', 'Officer', 'Finance', 'Event') NOT NULL,
    officer_role ENUM('President', 'Secretary', 'Treasurer') NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2.2 ORGANIZATIONS table
CREATE TABLE organizations (
    org_id INT AUTO_INCREMENT PRIMARY KEY,
    org_name VARCHAR(255) NOT NULL,
    org_description TEXT NULL,
    org_code VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2.3 EVENTS table (created_by is NULLable to support SET NULL)
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    event_description TEXT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    budget DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('Planning', 'Pending', 'Approved', 'Rejected', 'Completed', 'Cancelled') DEFAULT 'Planning',
    created_by INT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2.4 USER_ORGANIZATIONS table
CREATE TABLE user_organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    org_id INT NOT NULL,
    position VARCHAR(100) NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_org (user_id, org_id)
);

-- 2.5 FINANCIAL_REPORTS table
CREATE TABLE financial_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT NOT NULL,
    event_id INT NULL,
    report_title VARCHAR(255) NOT NULL,
    report_type ENUM('Budget', 'Expense', 'Income', 'Audit', 'Other') NOT NULL,
    description TEXT NULL,
    amount DECIMAL(10,2) DEFAULT 0.00,
    report_date DATE NOT NULL,
    file_path VARCHAR(500) NULL,
    uploaded_by INT NULL, -- Changed to NULL to allow ON DELETE SET NULL
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Add Indexes and Foreign Keys
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE organizations ADD INDEX idx_org_code (org_code);

-- Events Constraints
ALTER TABLE events
    ADD CONSTRAINT fk_events_org_id FOREIGN KEY (org_id) REFERENCES organizations(org_id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    ADD INDEX idx_event_date (event_date);

-- User Organizations Constraints
ALTER TABLE user_organizations
    ADD CONSTRAINT fk_uo_user_id FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_uo_org_id FOREIGN KEY (org_id) REFERENCES organizations(org_id) ON DELETE CASCADE;

-- Financial Reports Constraints
ALTER TABLE financial_reports
    ADD CONSTRAINT fk_fr_org_id FOREIGN KEY (org_id) REFERENCES organizations(org_id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_fr_event_id FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_fr_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_fr_approved_by FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL;

-- 4. Insert sample data
INSERT INTO organizations (org_name, org_description, org_code) VALUES 
('Student Government', 'Main student governing body', 'SG'),
('Computer Science Club', 'CS students organization', 'CSC'),
('Engineering Society', 'Engineering students association', 'ES');

INSERT INTO users (email, password_hash, full_name, first_name, role, officer_role) VALUES 
('admin@orgboard.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'System', 'Officer', 'President'),
('john.doe@orgboard.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'John', 'Student', NULL),
('jane.smith@orgboard.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'Jane', 'Officer', 'Secretary'),
('mike.wilson@orgboard.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Wilson', 'Mike', 'Finance', NULL),
('sarah.brown@orgboard.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Brown', 'Sarah', 'Event', NULL);

INSERT INTO user_organizations (user_id, org_id, position) VALUES 
(1, 1, 'President'), (2, 1, 'Member'), (3, 1, 'Secretary'), (4, 1, 'Treasurer'), (5, 1, 'Event Coordinator'), (2, 2, 'Member'), (3, 2, 'President');

INSERT INTO events (org_id, event_name, event_description, event_date, event_time, location, budget, status, created_by) VALUES 
(1, 'Spring Festival', 'Annual spring celebration event', '2024-04-15', '18:00:00', 'Campus Main Hall', 5000.00, 'Approved', 1),
(2, 'Tech Workshop', 'Programming and development workshop', '2024-03-20', '14:00:00', 'Computer Lab 101', 1500.00, 'Planning', 3),
(1, 'Leadership Summit', 'Student leadership development program', '2024-05-10', '09:00:00', 'Conference Center A', 3000.00, 'Pending', 1);

-- 5. Create database user
CREATE USER IF NOT EXISTS 'orgboard_user'@'localhost' IDENTIFIED BY 'secure_password_2024';
GRANT SELECT, INSERT, UPDATE, DELETE ON orgboard_db.* TO 'orgboard_user'@'localhost';
FLUSH PRIVILEGES;

-- 6. Summary
SELECT 'Database setup completed successfully!' as status;