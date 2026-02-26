<?php
// FILENAME: employee/config/app_config.php

/**
 * Defines application-wide constants, primarily for security and consistency.
 */

// --- User Roles ---
// All valid roles in the system. Used for strict server-side validation.
const APP_ROLES = [
    'Employee',
    'Manager',
    'HR Admin',
    'Super Admin'
];

// --- Leave Management Constants ---
const LEAVE_TYPES = [
    'Vacation',
    'Sick Leave',
    'Personal Day',
    'Annual Leave',
    'Maternity/Paternity'
];

const LEAVE_STATUSES = [
    'Pending',
    'Approved',
    'Rejected'
];

// --- NEW: Default Leave Policy Constants (in days per year) ---
const DEFAULT_VACATION_DAYS = 15;
const DEFAULT_SICK_DAYS = 5;
const DEFAULT_PERSONAL_DAYS = 2;
const DEFAULT_ANNUAL_DAYS = 12;
// --- END NEW ---

// --- General Settings Keys (Optional, but good practice for consistency) ---
const SETTING_COMPANY_NAME = 'company_name';
const SETTING_TIMEZONE = 'timezone';
const SETTING_CURRENCY = 'currency_symbol';
?>
