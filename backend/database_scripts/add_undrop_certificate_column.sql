-- Add column for undrop certificate types
ALTER TABLE class_card_drops 
ADD COLUMN undrop_certificates VARCHAR(255) NULL AFTER undrop_remarks;
