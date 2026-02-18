-- PhilCST Class Card Dropping System Database Setup
-- Drop existing database if exists
DROP DATABASE IF EXISTS philcst_class_drops;

-- Create database
CREATE DATABASE philcst_class_drops;
USE philcst_class_drops;
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    course VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subjects table
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_no VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Class card drops table
CREATE TABLE class_card_drops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    subject_no VARCHAR(20) NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    remarks TEXT,
    status VARCHAR(50) DEFAULT 'Dropped',
    drop_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    drop_month VARCHAR(10) NOT NULL,
    drop_year INT NOT NULL,
    retrieve_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_teacher (teacher_id),
    INDEX idx_student (student_id),
    INDEX idx_month (drop_month),
    INDEX idx_year (drop_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better query performance
CREATE INDEX idx_student_id ON students(student_id);
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_user_role ON users(role);

-- Insert sample admin user (password: 123456)
INSERT INTO users (name, email, password, role) VALUES 
('Guidance Head', 'admin@test.com', '$2a$12$LSE5hfRSPocZOtdy.c8/xuCq4i1T59elPWpXffmcgrbGSmix28PI2', 'admin');

-- Insert sample teacher (password: 123456)
INSERT INTO users (name, email, password, role) VALUES 
('Juan Dela Cruz', 'teacher@test.com', '$2a$12$Lwb5bTLGrSuVqRWaB1QnhuJlypgd8P4lqVlXLDxnuXsjmFFlO6jM2', 'teacher');

-- Insert sample students
INSERT INTO students (student_id, name, course, year) VALUES 
('2021-0001', 'Maria Santos', 'BS Computer Science', 1),
('2021-0002', 'Jose Garcia', 'BS Computer Science', 1),
('2021-0003', 'Ana Lopez', 'BS Computer Science', 2),
('2021-0004', 'Carlos Mendoza', 'BS Information Technology', 1),
('2021-0005', 'Rosa Fernandez', 'BS Information Technology', 2);

-- Insert sample subjects
INSERT INTO subjects (subject_no, subject_name) VALUES 
('CS101', 'Introduction to Programming'),
('CS102', 'Data Structures'),
('CS201', 'Web Development'),
('IT101', 'Network Basics'),
('IT102', 'Database Design');
