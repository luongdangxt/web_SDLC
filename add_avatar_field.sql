-- Add avatar field to users table (for base64 storage)
ALTER TABLE users ADD COLUMN Avatar LONGTEXT DEFAULT NULL AFTER Phonenumber;

-- Add index for better performance
CREATE INDEX idx_users_avatar ON users(Avatar); 