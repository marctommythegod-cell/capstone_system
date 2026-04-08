-- Add password_changed column to users table
USE philcst_class_drops;

ALTER TABLE users ADD COLUMN password_changed BOOLEAN DEFAULT FALSE AFTER updated_at;
ALTER TABLE users ADD COLUMN teacher_id VARCHAR(50) AFTER password_changed;
ALTER TABLE users ADD COLUMN address VARCHAR(255) AFTER teacher_id;
ALTER TABLE users ADD COLUMN department VARCHAR(100) AFTER address;
ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER department;
