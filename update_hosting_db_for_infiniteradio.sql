-- SQL Script to update Infinite Radio Hosting Database
-- Based on comparison with local development version

-- 1. Create 'companies' table (for multi-tenant support structure)
CREATE TABLE IF NOT EXISTS `companies` (
  `company_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `default_timezone` varchar(100) NOT NULL DEFAULT 'UTC',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default company if not exists
INSERT INTO `companies` (`company_id`, `company_name`, `address`, `contact_email`, `default_timezone`) 
SELECT 1, 'Default Company', '123 Main St', 'admin@default.com', 'Asia/Manila' 
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `company_id` = 1);

-- 2. Create 'dedicated_off_days' table (for specific employee off-days)
CREATE TABLE IF NOT EXISTS `dedicated_off_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `day_of_week` enum('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
  `effective_date` date NOT NULL COMMENT 'The date this off-day rule starts applying',
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Update 'deduction_types' table to support employee-specific deductions
-- Note: If this fails, the column might already exist.
-- ALTER TABLE `deduction_types` ADD COLUMN `employee_id` int(11) DEFAULT NULL AFTER `deduction_id`;

-- 4. Update 'users' table to link to companies
-- Note: If this fails with "Duplicate column", comment it out.
ALTER TABLE `users` ADD COLUMN `company_id` int(11) DEFAULT NULL AFTER `employee_id`;
UPDATE `users` SET `company_id` = 1 WHERE `company_id` IS NULL;

-- 5. Update 'payroll' table to track attendance deductions separately
-- Note: If this fails with "Duplicate column", comment it out.
ALTER TABLE `payroll` ADD COLUMN `attendance_deductions` decimal(10,2) DEFAULT 0.00 AFTER `gross_pay`;

-- 6. Update 'attendance_logs' table for remarks
-- Note: If this fails with "Duplicate column", comment it out.
ALTER TABLE `attendance_logs` ADD COLUMN `remarks` varchar(255) DEFAULT NULL AFTER `time_out`;

-- 7. Update 'employees' table for Kiosk PIN security
-- Note: If this fails with "Duplicate column", comment it out.
ALTER TABLE `employees` ADD COLUMN `pin_secret` varchar(255) DEFAULT NULL AFTER `email`;

-- 8. Create 'ca_deductions_history' table for tracking CA repayments in payroll
CREATE TABLE IF NOT EXISTS `ca_deductions_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `deduction_date` date NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `payroll_id` (`payroll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. Insert new Global Settings
-- Grace period for late attendance
INSERT IGNORE INTO `global_settings` (`setting_key`, `setting_value`) VALUES ('late_grace_period_minutes', '0');

-- Allow manual edit of attendance
INSERT IGNORE INTO `global_settings` (`setting_key`, `setting_value`) VALUES ('allow_manual_attendance_edit', '1');

-- Hide pay rate from employee dashboard/payslip
INSERT IGNORE INTO `global_settings` (`setting_key`, `setting_value`) VALUES ('hide_pay_rate_from_employee', '0');

-- 10. Ensure 'schedules' and 'standard_schedules' exist (Just in case)
CREATE TABLE IF NOT EXISTS `schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `shift_start` time NOT NULL,
  `shift_end` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`schedule_id`),
  UNIQUE KEY `unique_employee_day` (`employee_id`,`work_date`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `standard_schedules` (
  `standard_schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `day_of_week` enum('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_rest_day` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`standard_schedule_id`),
  UNIQUE KEY `unique_employee_day_standard` (`employee_id`,`day_of_week`),
  KEY `fk_employee_schedule` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 11. Ensure 'attendance_logs' has 'scheduled_start_time'
-- This might fail if it exists, which is fine.
ALTER TABLE `attendance_logs` ADD COLUMN `scheduled_start_time` datetime DEFAULT NULL COMMENT 'The expected start time of the shift on this day (standard or exception).';
