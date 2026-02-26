# Employee System Mobile App (React Native Expo)

This folder contains a React Native (Expo) mobile application meant to provide a mobile-friendly view of employee attendance logs.

## Prerequisites
1.  **Node.js**: Ensure you have Node.js installed on your machine.
2.  **Expo Go App**: Install the "Expo Go" app on your Android or iOS device.

## Setup Instructions

1.  Open this folder in your terminal:
    ```bash
    cd c:\xampp\htdocs\employee\mobile-app
    ```
    *(Or navigate to wherever you placed this folder)*

2.  Install dependencies:
    ```bash
    npm install
    ```

3.  **Configuring API Access:**
    *   Open `App.js`.
    *   Find the `API_URL` constant at the top.
    *   **Android Emulator:** Use `http://10.0.2.2/employee/api/get_employee_daily_logs.php`
    *   **iOS Simulator:** Use `http://localhost/employee/api/get_employee_daily_logs.php`
    *   **Physical Device / LAN:** Use your computer's local IP address (e.g., `http://192.168.1.15/employee/api/get_employee_daily_logs.php`). Ensure your phone and computer are on the same Wi-Fi network.

4.  Start the app:
    ```bash
    npm start
    ```
    This will launch the Expo development server.

5.  **Run on Device:**
    *   Scan the QR code displayed in the terminal or browser with your Expo Go app (Android) or Camera app (iOS).
    *   The app should load and display the employee logs.

## Features
*   View daily attendance logs (Clock In, Clock Out, Total Hours).
*   See status (Present, Absent, Leave).
*   View daily deductions and remarks.
*   Pull-to-refresh functionality.

## Note
Currently, the app is hardcoded to fetch logs for `employee_id = 2` for demonstration purposes. In a full production app, you would implement a Login screen to authenticate the user and get their own employee ID.
