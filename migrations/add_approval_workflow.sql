-- Migration: Add Approval Workflow for Class Card Drops
-- Adds approval status and tracking fields to class_card_drops table

ALTER TABLE class_card_drops MODIFY COLUMN status VARCHAR(50) DEFAULT 'Pending';
ALTER TABLE class_card_drops ADD COLUMN approved_by INT NULL;
ALTER TABLE class_card_drops ADD COLUMN approved_date DATETIME NULL;
ALTER TABLE class_card_drops ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE class_card_drops ADD INDEX idx_status (status);

-- Update any existing 'Dropped' status records to mark them as approved by system
UPDATE class_card_drops SET approved_by = 1, approved_date = NOW() WHERE status = 'Dropped' AND approved_by IS NULL;
