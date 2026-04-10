-- PhilCST Class Card Dropping System Database Setup
-- Drop existing database if exists
DROP DATABASE IF EXISTS philcst_class_drops;

-- Create database
CREATE DATABASE philcst_class_drops;
USE philcst_class_drops;

-- Departments table (Colleges)
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    college_name VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Department Courses table (Programs under each college)
CREATE TABLE department_courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NOT NULL,
    course_name VARCHAR(150) NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dept_course (department_id, course_code),
    INDEX idx_department (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'admin') NOT NULL,
    teacher_id VARCHAR(20),
    address TEXT,
    department VARCHAR(150),
    department_id INT,
    password_changed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_role (role),
    INDEX idx_department_id (department_id)
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

-- Subjects table (linked to departments and courses)
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    department_id INT NOT NULL,
    course_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES department_courses(id) ON DELETE SET NULL,
    UNIQUE KEY unique_code_dept (subject_code, department_id),
    INDEX idx_department (department_id),
    INDEX idx_course (course_id),
    INDEX idx_code (subject_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Class card drops table
CREATE TABLE class_card_drops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    subject_no VARCHAR(20) NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    remarks TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    drop_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    drop_month VARCHAR(10) NOT NULL,
    drop_year INT NOT NULL,
    retrieve_date DATETIME NULL,
    undrop_remarks TEXT NULL,
    approved_by INT NULL,
    approved_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_teacher (teacher_id),
    INDEX idx_student (student_id),
    INDEX idx_month (drop_month),
    INDEX idx_year (drop_year),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better query performance
CREATE INDEX idx_student_id ON students(student_id);
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_user_role ON users(role);

-- Insert Departments (Colleges)
INSERT INTO departments (college_name) VALUES 
('College of Engineering'),
('College of Accountancy and Business Education'),
('College of Education'),
('College of Criminal Justice'),
('College of Maritime Studies'),
('College of Hospitality and Tourism Management'),
('College of Computer Studies');

-- Insert Department Courses
-- College of Engineering (ID 1)
INSERT INTO department_courses (department_id, course_name, course_code) VALUES 
(1, 'BS in Civil Engineering', 'BSCE'),
(1, 'BS in Mechanical Engineering', 'BSME');

-- College of Accountancy and Business Education (ID 2)
INSERT INTO department_courses (department_id, course_name, course_code) VALUES 
(2, 'BS in Business Administration', 'BSBA'),
(2, 'BS in Accounting', 'BSACCT');

-- College of Education (ID 3)
INSERT INTO department_courses (department_id, course_name, course_code) VALUES 
(3, 'Bachelor of Elementary Education', 'BEEd'),
(3, 'Bachelor of Secondary Education', 'BSEd');

-- College of Criminal Justice (ID 4)
INSERT INTO department_courses (department_id, course_name, course_code) VALUES 
(4, 'BS in Criminology', 'BSCrim'),
(4, 'BS in Criminal Justice', 'BSCJ');

-- College of Maritime Studies (ID 5)
INSERT INTO department_courses (department_id, course_name, course_code) VALUES 
(5, 'BS in Maritime Transportation', 'BSMT'),
(5, 'BS in Marine Engineering', 'BSME-Mar');

-- College of Hospitality and Tourism Management (ID 6)
INSERT INTO department_courses (department_id, course_name, course_code) VALUES 
(6, 'BS in Hospitality Management', 'BSHM'),
(6, 'BS in Tourism Management', 'BSTM');

-- College of Computer Studies (ID 7)
INSERT INTO department_courses (department_id, course_name, course_code) VALUES 
(7, 'BS in Computer Science', 'BSCS'),
(7, 'BS in Information Technology', 'BSIT');

-- Insert Subjects for College of Engineering (10 subjects)
INSERT INTO subjects (subject_code, subject_name, department_id) VALUES 
('ENGR101', 'Engineering Mathematics I', 1),
('ENGR102', 'Physics for Engineers I', 1),
('ENGR103', 'Engineering Drawing', 1),
('ENGR104', 'Material Science', 1),
('ENGR201', 'Engineering Mathematics II', 1),
('ENGR202', 'Mechanics of Materials', 1),
('ENGR203', 'Thermodynamics', 1),
('ENGR301', 'Structural Analysis', 1),
('ENGR302', 'Hydraulics and Hydrology', 1),
('ENGR303', 'Sustainable Engineering', 1);

-- Insert Subjects for College of Accountancy and Business Education (10 subjects)
INSERT INTO subjects (subject_code, subject_name, department_id) VALUES 
('ACCT101', 'Principles of Accounting', 2),
('ACCT102', 'Financial Accounting', 2),
('BUS101', 'Business Management', 2),
('BUS102', 'Economics for Business', 2),
('ACCT201', 'Managerial Accounting', 2),
('ACCT202', 'Intermediate Accounting', 2),
('BUS201', 'Business Law', 2),
('ACCT301', 'Advanced Accounting', 2),
('BUS301', 'Strategic Business Management', 2),
('ACCT302', 'Auditing Principles', 2);

-- Insert Subjects for College of Education (10 subjects)
INSERT INTO subjects (subject_code, subject_name, department_id) VALUES 
('ED101', 'Introduction to Education', 3),
('ED102', 'Educational Psychology', 3),
('ED103', 'Curriculum Development', 3),
('ED201', 'Teaching Methodologies', 3),
('ED202', 'Assessment and Evaluation', 3),
('ED203', 'Classroom Management', 3),
('ED301', 'Educational Leadership', 3),
('ED302', 'Special Education', 3),
('ED303', 'Teacher Development', 3),
('ED304', 'Educational Technology', 3);

-- Insert Subjects for College of Criminal Justice (10 subjects)
INSERT INTO subjects (subject_code, subject_name, department_id) VALUES 
('CJ101', 'Introduction to Criminal Justice', 4),
('CJ102', 'Criminal Law', 4),
('CJ103', 'Criminology', 4),
('CJ201', 'Police Systems and Procedures', 4),
('CJ202', 'Correctional Systems', 4),
('CJ203', 'Criminal Investigation', 4),
('CJ301', 'Evidence and Procedure', 4),
('CJ302', 'Forensic Science', 4),
('CJ303', 'Ethics in Criminal Justice', 4),
('CJ304', 'Comparative Criminal Justice', 4);

-- Insert Subjects for College of Maritime Studies (10 subjects)
INSERT INTO subjects (subject_code, subject_name, department_id) VALUES 
('MAR101', 'Maritime Navigation', 5),
('MAR102', 'Ship Operations', 5),
('MAR103', 'Maritime Safety', 5),
('MAR201', 'Seamanship', 5),
('MAR202', 'Maritime Law', 5),
('MAR203', 'Ship Stability and Construction', 5),
('MAR301', 'Maritime Logistics', 5),
('MAR302', 'Engine Room Operations', 5),
('MAR303', 'Maritime Environmental Protection', 5),
('MAR304', 'International Maritime Regulations', 5);

-- Insert Subjects for College of Hospitality and Tourism Management (10 subjects)
INSERT INTO subjects (subject_code, subject_name, department_id) VALUES 
('HM101', 'Hotel and Restaurant Management', 6),
('HM102', 'Hospitality Customer Service', 6),
('HM103', 'Food and Beverage Operations', 6),
('HM201', 'Housekeeping Management', 6),
('HM202', 'Front Office Operations', 6),
('TM101', 'Tourism Development', 6),
('TM102', 'Travel and Tour Operations', 6),
('HM301', 'Revenue Management', 6),
('TM201', 'Destination Management', 6),
('TM202', 'Sustainable Tourism', 6);

-- Insert Subjects for College of Computer Studies (10 subjects)
INSERT INTO subjects (subject_code, subject_name, department_id) VALUES 
('CS101', 'Introduction to Programming', 7),
('CS102', 'Data Structures', 7),
('CS103', 'Discrete Mathematics', 7),
('CS201', 'Web Development', 7),
('CS202', 'Database Design', 7),
('CS203', 'Software Engineering', 7),
('CS301', 'Artificial Intelligence', 7),
('CS302', 'Network Security', 7),
('CS303', 'Cloud Computing', 7),
('CS304', 'Mobile Application Development', 7);

-- Insert sample admin user (email: PhilcstGuidance@gmail.com, password: Philcst@guidance2026)
INSERT INTO users (name, email, password, role) VALUES 
('Guidance Head', 'PhilcstGuidance@gmail.com', '$2y$10$oerS7/dytg16lbw06ABx1u83YmhWOogJtA.prpbypfTGBco0qu7Ru', 'admin');
