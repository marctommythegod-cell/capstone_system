-- Add teacher fields to users table
ALTER TABLE users ADD COLUMN teacher_id VARCHAR(50) AFTER id;
ALTER TABLE users ADD COLUMN address VARCHAR(255) AFTER email;
ALTER TABLE users ADD COLUMN department VARCHAR(100) AFTER address;
