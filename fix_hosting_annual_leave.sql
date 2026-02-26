-- Fix for "Unknown column 'annual_days_accrued'" error

-- Add annual_days_accrued column
ALTER TABLE `leave_balances` ADD COLUMN `annual_days_accrued` decimal(5,2) DEFAULT 0.00 COMMENT 'Annual policy in days' AFTER `personal_days_accrued`;

-- Optional: Add annual_days_used if you plan to track it separately
-- ALTER TABLE `leave_balances` ADD COLUMN `annual_days_used` decimal(5,2) DEFAULT 0.00 AFTER `personal_days_used`;
