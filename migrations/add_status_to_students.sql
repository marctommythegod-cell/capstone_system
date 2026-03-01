-- Add status column to students table
ALTER TABLE students ADD COLUMN status VARCHAR(20) DEFAULT 'active';
