-- Add status column to users table if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active';

-- Update existing users to have active status
UPDATE users SET status = 'active' WHERE status IS NULL; 