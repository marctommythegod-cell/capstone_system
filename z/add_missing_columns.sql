-- Add missing columns to students table
USE philcst_class_drops;

ALTER TABLE students ADD COLUMN address VARCHAR(255) AFTER email;
ALTER TABLE students ADD COLUMN guardian_name VARCHAR(100) AFTER address;
ALTER TABLE students ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER year;
