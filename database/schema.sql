-- Create database
CREATE DATABASE IF NOT EXISTS syllabus_repository;
USE syllabus_repository;

-- Create ADMINISTRATOR table with AUTO_INCREMENT
CREATE TABLE IF NOT EXISTS ADMINISTRATOR (
    adminID INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    position VARCHAR(50),
    status VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create SYLLABUS table with AUTO_INCREMENT
CREATE TABLE IF NOT EXISTS SYLLABUS (
    syllabusID INT PRIMARY KEY AUTO_INCREMENT,
    instructorID INT,
    title VARCHAR(200) NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    description TEXT,
    status VARCHAR(20),
    submitted_at DATETIME,
    approved_at DATETIME,
    FOREIGN KEY (instructorID) REFERENCES ADMINISTRATOR(adminID)
);

-- Create FILE table with AUTO_INCREMENT
CREATE TABLE IF NOT EXISTS FILE (
    fileID INT PRIMARY KEY AUTO_INCREMENT,
    syllabusID INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    version_no INT DEFAULT 1,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_signed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (syllabusID) REFERENCES SYLLABUS(syllabusID) ON DELETE CASCADE
);

-- Create REVIEW table with AUTO_INCREMENT
CREATE TABLE IF NOT EXISTS REVIEW (
    reviewID INT PRIMARY KEY AUTO_INCREMENT,
    syllabusID INT NOT NULL,
    adminID INT NOT NULL,
    decision VARCHAR(20),
    comments TEXT,
    review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (syllabusID) REFERENCES SYLLABUS(syllabusID) ON DELETE CASCADE,
    FOREIGN KEY (adminID) REFERENCES ADMINISTRATOR(adminID)
);

-- Create ARCHIVE table with AUTO_INCREMENT
CREATE TABLE IF NOT EXISTS ARCHIVE (
    archiveID INT PRIMARY KEY AUTO_INCREMENT,
    adminID INT NOT NULL,
    archive_code VARCHAR(50) UNIQUE NOT NULL,
    location VARCHAR(200),
    archived_by VARCHAR(100),
    archived_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (adminID) REFERENCES ADMINISTRATOR(adminID)
);

-- Insert sample data

-- Sample administrators (password is 'admin123' hashed with bcrypt)
-- Note: To generate bcrypt hash, use: password_hash('admin123', PASSWORD_DEFAULT)
-- For demo, using plain text - CHANGE THIS IN PRODUCTION
INSERT INTO ADMINISTRATOR (full_name, password, email, position, status) VALUES
('Admin User', '$2y$10$YQz7Lq3xGJLK9nxKX6h.cO7bvYrJ8Y6ZGqhk2Bx8E1.vMWKFXYHQS', 'admin@university.edu', 'Administrator', 'active'),
('John Doe', '$2y$10$YQz7Lq3xGJLK9nxKX6h.cO7bvYrJ8Y6ZGqhk2Bx8E1.vMWKFXYHQS', 'john.doe@university.edu', 'Instructor', 'active'),
('Sarah Johnson', '$2y$10$YQz7Lq3xGJLK9nxKX6h.cO7bvYrJ8Y6ZGqhk2Bx8E1.vMWKFXYHQS', 'sarah.johnson@university.edu', 'Instructor', 'active'),
('Michael Chen', '$2y$10$YQz7Lq3xGJLK9nxKX6h.cO7bvYrJ8Y6ZGqhk2Bx8E1.vMWKFXYHQS', 'michael.chen@university.edu', 'Instructor', 'active');

-- Sample syllabi
INSERT INTO SYLLABUS (instructorID, title, course_code, description, status, submitted_at, approved_at) VALUES
(2, 'Introduction to Computer Science', 'CS-101', 'Fundamental concepts of computer science including programming, algorithms, and problem solving.', 'pending', NOW(), NULL),
(3, 'Data Structures & Algorithms', 'CS-301', 'Study of fundamental data structures and algorithmic techniques.', 'pending', NOW(), NULL),
(4, 'Database Systems', 'CS-401', 'Design and implementation of database systems.', 'approved', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Sample files
INSERT INTO FILE (syllabusID, file_path, version_no, uploaded_at, is_signed) VALUES
(1, 'uploads/syllabi/sample1.pdf', 1, NOW(), 0),
(2, 'uploads/syllabi/sample2.pdf', 1, NOW(), 0),
(3, 'uploads/syllabi/sample3.pdf', 1, DATE_SUB(NOW(), INTERVAL 5 DAY), 0),
(3, 'uploads/syllabi/sample3_signed.pdf', 2, DATE_SUB(NOW(), INTERVAL 3 DAY), 1);

-- Sample review
INSERT INTO REVIEW (syllabusID, adminID, decision, comments, review_date) VALUES
(3, 1, 'approved', 'Excellent syllabus structure. All requirements met.', DATE_SUB(NOW(), INTERVAL 3 DAY));