-- Add password reset columns to users table

ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(255) NULL AFTER password;
ALTER TABLE users ADD COLUMN password_reset_expiry DATETIME NULL AFTER password_reset_token;

-- Create index for faster token lookup
CREATE INDEX idx_password_reset_token ON users(password_reset_token);
