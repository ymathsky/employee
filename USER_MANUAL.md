# Employee Management System - Comprehensive User Manual

This manual provides a complete guide to using the Employee Management System for Administrators, Managers, and Employees.

---

## Table of Contents

1.  [Introduction](#introduction)
2.  [User Roles](#user-roles)
3.  [Getting Started](#getting-started)
4.  [Admin Module (HR & Super Admin)](#admin-module-hr--super-admin)
    *   [Employee Management](#employee-management)
    *   [Attendance & Scheduling](#attendance--scheduling)
    *   [Leave Management](#leave-management)
    *   [Deduction Management](#deduction-management)
    *   [Payroll Processing](#payroll-processing)
    *   [Financials: Cash Advances](#financials-cash-advances)
    *   [Reports](#reports)
5.  [Manager Module](#manager-module)
6.  [Employee Self-Service](#employee-self-service)

---

## Introduction

The Employee Management System is a comprehensive web-based platform designed to streamline HR processes, including attendance tracking, leave management, payroll calculation, and employee record-keeping. It supports multi-user access with distinct roles for Admins, Managers, and Standard Employees.

---

## User Roles

*   **Super Admin / HR Admin**: Full access to all system features, including settings, payroll generation, and sensitive employee data.
*   **Manager**: Can view team attendance and approve/reject leave requests for their designated department.
*   **Employee**: Restricted access. Can only view their own profile, attendance logs, payslips, and request leave.

---

## Getting Started

### Login
1.  Navigate to the login page (`login.html`).
2.  Enter your **Email Address** and **Password**.
3.  Click **Login**.
    *   *Note*: If you forget your password, contact an Administrator to reset it.

### Dashboard Overview
*   **Admin Dashboard**: Displays key metrics like *Total Employees*, *On Time Today*, *Late Today*, and *Pending Leave Requests*.
*   **Manager Dashboard**: Shows team-specific attendance summaries and pending approvals.
*   **Employee Dashboard**: Shows personal attendance stats, remaining leave balance, and recent announcements.

---

## Admin Module (HR & Super Admin)

### Employee Management
Manage your workforce database.

*   **Add New Employee**: 
    1.  Go to **Employee Management** > **Add Employee**.
    2.  Fill in required details (Name, Email, Job Title, Department).
    3.  **Crucial**: Set the correct **Hired Date** for accurate leave accrual.
    4.  Set the **Basic Rate** (daily/hourly).
*   **Edit Employee**: 
    *   Click the **Edit** (Pencil) icon on an employee's row to update details.
    *   Change status to *Inactive* to disable login access without deleting data.
*   **PIN Management**: 
    *   Reset an employee's Kiosk PIN if they forget it.
*   **Department Transfer**: 
    *   Update an employee's department to change their reporting line (Manager).

### Attendance & Scheduling
Manage work hours and track time.

*   **Schedules**:
    *   **Standard Schedule**: Define the default Start/End times for the company (e.g., 9:00 AM - 6:00 PM).
    *   **Employee Schedules**: Assign specific work days or shift timings to individual employees.
*   **Grace Period**:
    *   Go to **Global Settings**.
    *   Set **Late Grace Period (Minutes)**. Example: If set to 15, employees clocking in at 9:15 AM are still marked "On Time".
*   **Time Logs**:
    *   View raw daily logs in **Attendance Logs**.
    *   **Edit Logs**: Fix missing punches or incorrect times manually.
    *   **Add Remarks**: specific notes (e.g., "Field Work", "Forgot ID") to a log entry.

### Leave Management
Handle leave policies and requests.

*   **Leave Policies**:
    *   Go to **Manage Leave** > **Leave Policy**.
    *   Define types (Vacation, Sick, Emergency).
    *   Set **Annual Accrual Days**.
    *   Use **Auto-Calculate** to update balances for all employees based on their tenure.
*   **Processing Requests**:
    *   Go to **Manage Leave**.
    *   **Approve**: Deducts days from the employee's balance and marks attendance as "Leave".
    *   **Reject**: Returns the days to the employee's balance.
    *   **Delete**: Permanently remove a specialized request (e.g., entered in error).
*   **Manual Entry**:
    *   Admins can file leave on behalf of an employee using **Admin Add Leave**.

### Deduction Management
Configure standard and specific deductions for payroll.

*   **Global Deductions**:
    *   Create deductions that apply to *everyone* (e.g., Tax, SSS, PhilHealth).
    *   **Exclusions**: When editing a Global Deduction, check the "Exclude Employees" list to exempt specific staff members from that deduction.
*   **Specific Deductions**:
    *   Create a deduction targeting a *single employee* (e.g., Loan Repayment, Uniform Fee).
*   **Printing**:
    *   Click the **Print List** button on the Deduction Management page to generate a hard copy of all active deduction rates.

### Payroll Processing
Generate salaries and payslips.

1.  **Generate Payroll**:
    *   Go to **Payroll**.
    *   Select the **Pay Period** (Start Date - End Date).
    *   Click **Generate**.
    *   The system calculates:
        *   (+) Basic Pay (Days Worked × Rate)
        *   (+) Overtime (if enabled)
        *   (-) Lates / Undertime
        *   (-) Global Deductions (skipping Exclusions)
        *   (-) Specific Deductions
        *   (-) Cash Advances
2.  **Review & Recalculate**:
    *   **Important**: If you find an error (e.g., wrong deduction), **Delete** the payroll run from the history list, fix the settings, and **Regenerate** it. *Changes to settings do not apply to already-generated payrolls.*
3.  **Payslips**:
    *   Click **View Payslip** next to an employee's name in the payroll run.
    *   **Print**: Send to a printer.
    *   **Download PDF**: Save a digital copy.

### Financials: Cash Advances
*   **CA Management**: Record cash advances (Vales) given to employees.
*   **Repayment**: The system automatically deducts outstanding CA balances from the next payroll generation. Ad-hoc payments can simply be recorded by reducing the CA balance.

### Reports
*   **Attendance Report**: Comprehensive view of Present, Late, Absent, and On-Leave days for a selected period. Exportable to Excel/PDF.
*   **Payroll Summary**: Total payout, total deductions, and net pay for a specific period.

---

## Manager Module

*   **Team Attendance**:
    *   monitor real-time status (Who is In, Who is Late) for your specific department.
*   **Leave Approvals**:
    *   Receive notifications for new leave requests.
    *   Review reasoning and dates before Approving or Rejecting.

---

## Employee Self-Service

### Clocking In/Out
*   **Kiosk Mode**:
    1.  Use the company tablet/PC.
    2.  Find your name in the list.
    3.  Enter your personal **PIN**.
    4.  Select Action: **Time In**, **Lunch Start**, **Lunch End**, or **Time Out**.
*   **QR Code**:
    1.  Scan your personal QR code at the designated scanner.
    2.  The system confirms your log entry automatically.

### My Portal
*   **My Profile**: View your employment details and QR code.
*   **My Payslips**: Securely view and download your past payslips.
*   **My Leave**: 
    *   Check available leave credits.
    *   Submit new leave requests.
    *   Track the status of pending requests.
*   **My Daily Logs**: View your own attendance history to verify accuracy.
