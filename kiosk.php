<?php
// FILENAME: employee/kiosk.php
// Start session here ONLY to check for an existing login session,
// but do not enforce login.
session_start();

require_once 'api/db_connect.php';

// Fetch Settings (Timezone & Company Name)
$timezone = 'Asia/Manila'; // Default fallback
$company_name = 'Your Company'; // Default fallback

try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM global_settings WHERE setting_key IN ('timezone', 'company_name')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    if (!empty($settings['timezone'])) {
        $timezone = $settings['timezone'];
    }
    if (!empty($settings['company_name'])) {
        $company_name = $settings['company_name'];
    }
} catch (Exception $e) {
    // Keep defaults
}

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? null;

$admin_pages = [
    'HR Admin' => 'admin_dashboard.php',
    'Super Admin' => 'admin_dashboard.php',
    'Manager' => 'manager_dashboard.php'
];

$dashboard_link = $admin_pages[$user_role] ?? 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Kiosk | Time Clock</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Primary CDN (Standard) -->
    <script src="https://unpkg.com/html5-qrcode@2.3.4/html5-qrcode.min.js"></script>
    <!-- Fallback CDN (Official) -->
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.4/html5-qrcode.min.js"></script>
    <!-- Font Awesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .kiosk-card {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background-color: #1f2937; /* Darker Blue-Gray */
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
        }
        #qr-reader {
            width: 100%;
            height: 350px;
            margin: 0 auto;
            border: 4px solid #3b82f6; /* Blue border for camera area */
            border-radius: 8px;
            overflow: hidden;
            background-color: #000;
        }
        /* Custom status classes with enhanced visual appearance */
        .status-success { background-color: #10b981; color: #fff; } /* Emerald Green */
        .status-error { background-color: #ef4444; color: #fff; } /* Red */
        .status-warning { background-color: #f59e0b; color: #fff; } /* Amber */
        .status-info { background-color: #3b82f6; color: #fff; } /* Blue for scanning/loading */

        /* Styles for the HTML5-QR Code Reader */
        #qr-reader__dashboard_section_csr {
            padding: 0 !important;
        }
        #qr-reader__scan_region {
            border-radius: 4px;
        }
        #qr-reader div {
            padding: 0 !important;
        }

        /* TOAST STYLING */
        #message-box {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100;
            opacity: 0;
            transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
            min-width: 300px;
            max-width: 90%;
        }
        #message-box.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* SHAKE ANIMATION */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center justify-center p-4">

<div class="kiosk-card p-8 text-center">
    <h1 class="text-3xl font-bold mb-2 text-indigo-400">Time Clock</h1>
    
    <!-- Digital Clock -->
    <div class="mb-6">
        <div id="digital-clock" class="text-5xl font-mono font-bold text-white tracking-wider">00:00:00</div>
        <div id="current-date" class="text-lg text-gray-400 mt-1">Loading Date...</div>
    </div>

    <p class="text-md text-gray-400 mb-6">Scan your employee QR code to time in or out.</p>

    <div id="qr-reader" class="rounded-lg overflow-hidden mb-4"></div>

    <!-- Camera Selection Dropdown -->
    <div class="mb-4">
        <select id="camera-select"
                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-white focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
            <option value="">Select a Camera...</option>
        </select>
    </div>

    <!-- Scanner Control Buttons -->
    <div class="flex space-x-4 mb-4">
        <button id="start-scan-btn" onclick="startScanning()" disabled
                class="flex-1 px-4 py-3 border border-transparent rounded-lg shadow-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:bg-gray-500 disabled:cursor-not-allowed transition-colors duration-200">
            <i class="fas fa-video mr-2"></i>Start Scanning
        </button>
        <button id="stop-scan-btn" onclick="stopScanning()" disabled
                class="flex-1 px-4 py-3 border border-transparent rounded-lg shadow-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:bg-gray-500 disabled:cursor-not-allowed transition-colors duration-200">
            <i class="fas fa-stop-circle mr-2"></i>Stop Scanning
        </button>
    </div>

    <!-- PIN Button -->
    <div class="mb-4">
        <button onclick="showPinModal()" class="w-full px-4 py-3 border border-gray-600 rounded-lg shadow-md text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
            <i class="fas fa-keyboard mr-2"></i>Use PIN Code
        </button>
    </div>

    <!-- Conditional link based on session status -->
    <div class="mt-6 text-gray-500 text-xs">
        &copy; <?php echo date('Y'); ?> <strong><?php echo htmlspecialchars($company_name); ?></strong>. All rights reserved. |
        <?php if ($is_logged_in): ?>
            <a href="<?php echo htmlspecialchars($dashboard_link); ?>" class="text-indigo-400 hover:text-indigo-300">
                Go to <?php echo htmlspecialchars($user_role); ?> Dashboard
            </a>
            <span class="text-gray-600"> | </span>
            <a href="api/logout.php" class="text-indigo-400 hover:text-indigo-300">Logout</a>
        <?php else: ?>
            <a href="login.html" class="text-indigo-400 hover:text-indigo-300">Admin Login</a>
        <?php endif; ?>
    </div>
</div>

<!-- Fixed Footer for Kiosk -->
<div class="fixed bottom-0 w-full py-2 bg-gray-800 border-t border-gray-700 text-center text-gray-400 text-xs z-50">
    Developed by <span class="font-semibold text-indigo-400">Ymath</span>
</div>

<!-- MODAL POPUP -->
<div id="status-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <!-- Centering trick -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div id="modal-icon-container" class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                        <!-- Icon injected here -->
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Status
                        </h3>
                        <div class="mt-2">
                            <p class="text-xl text-gray-700 font-semibold" id="modal-message">
                                Message goes here...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-center">
                <p class="text-sm text-gray-500">Resuming in <span id="modal-timer">5</span> seconds...</p>
            </div>
        </div>
    </div>
</div>
<!-- END MODAL POPUP -->

<!-- PIN MODAL -->
<div id="pin-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-90 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-middle bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-sm sm:w-full border border-gray-700">
            <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="text-center">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">Enter Employee PIN</h3>
                    
                    <!-- Error Message -->
                    <p id="pin-error-msg" class="text-red-500 text-sm mb-2 hidden font-semibold"></p>

                    <!-- PIN Display -->
                    <div class="mb-6">
                        <input type="text" id="pin-input" readonly class="w-full text-center text-3xl tracking-widest bg-gray-700 border border-gray-600 rounded-lg py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="------">
                    </div>
                    
                    <!-- Keypad -->
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <button onclick="appendPin('1')" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg text-xl">1</button>
                        <button onclick="appendPin('2')" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg text-xl">2</button>
                        <button onclick="appendPin('3')" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg text-xl">3</button>
                        <button onclick="appendPin('4')" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg text-xl">4</button>
                        <button onclick="appendPin('5')" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg text-xl">5</button>
                        <button onclick="appendPin('6')" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg text-xl">6</button>
                        <button onclick="appendPin('7')" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg text-xl">7</button>
                        <button onclick="appendPin('8')" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg text-xl">8</button>
                        <button onclick="appendPin('9')" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg text-xl">9</button>
                        <button onclick="clearPin()" class="bg-red-900 hover:bg-red-800 text-red-100 font-bold py-4 rounded-lg text-sm flex items-center justify-center">CLR</button>
                        <button onclick="appendPin('0')" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg text-xl">0</button>
                        <button onclick="backspacePin()" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-4 rounded-lg text-xl flex items-center justify-center"><i class="fas fa-backspace"></i></button>
                    </div>
                </div>
            </div>
            <div class="bg-gray-700 px-4 py-3 sm:px-6 flex flex-row-reverse">
                <button onclick="submitPin()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Submit
                </button>
                <button onclick="hidePinModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-500 shadow-sm px-4 py-2 bg-gray-800 text-base font-medium text-gray-300 hover:bg-gray-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
<!-- END PIN MODAL -->

<!-- TOAST MESSAGE CONTAINER -->
<div id="message-box" class="p-4 rounded-lg text-lg font-semibold hidden flex items-center justify-center space-x-3" role="alert">
    <!-- Message content will be inserted here -->
</div>
<!-- END TOAST CONTAINER -->


<script>
    const messageBox = document.getElementById('message-box');
    const cameraSelect = document.getElementById('camera-select');
    const startScanBtn = document.getElementById('start-scan-btn');
    const stopScanBtn = document.getElementById('stop-scan-btn');
    const systemTimezone = "<?php echo htmlspecialchars($timezone); ?>";

    // --- Digital Clock Logic ---
    function updateClock() {
        const now = new Date();
        
        // Format Time with Timezone
        const timeOptions = { 
            timeZone: systemTimezone,
            hour: 'numeric', 
            minute: '2-digit', 
            second: '2-digit', 
            hour12: true 
        };
        const timeString = now.toLocaleTimeString('en-US', timeOptions);
        document.getElementById('digital-clock').textContent = timeString;

        // Format Date with Timezone
        const dateOptions = { 
            timeZone: systemTimezone,
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        const dateString = now.toLocaleDateString('en-US', dateOptions);
        document.getElementById('current-date').textContent = dateString;
    }

    // Update clock immediately and then every second
    updateClock();
    setInterval(updateClock, 1000);

    const scanCooldown = 3000; // 3 seconds cooldown
    let lastScanTime = 0;
    let html5Qrcode = null;
    let currentCameraId = null;
    let isScanning = false; // New state tracker
    let isProcessing = false; // Flag to prevent multiple scans during processing

    // --- Modal Functions ---
    const statusModal = document.getElementById('status-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const modalIconContainer = document.getElementById('modal-icon-container');
    const modalTimer = document.getElementById('modal-timer');
    let modalInterval = null; // Store interval ID to clear it properly

    function showModal(title, message, type, callback = null) {
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        
        // Reset classes
        modalIconContainer.className = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10';
        
        if (type === 'success') {
            modalIconContainer.classList.add('bg-green-100');
            modalIconContainer.innerHTML = '<i class="fas fa-check text-green-600 text-lg"></i>';
        } else {
            modalIconContainer.classList.add('bg-red-100');
            modalIconContainer.innerHTML = '<i class="fas fa-times text-red-600 text-lg"></i>';
        }

        statusModal.classList.remove('hidden');
        
        // Clear any existing interval
        if (modalInterval) clearInterval(modalInterval);

        // Countdown timer
        let timeLeft = 5;
        modalTimer.textContent = timeLeft;
        
        modalInterval = setInterval(() => {
            timeLeft--;
            modalTimer.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(modalInterval);
                hideModal();
                if (callback) callback();
            }
        }, 1000);
    }

    function hideModal() {
        statusModal.classList.add('hidden');
        if (modalInterval) clearInterval(modalInterval);
    }

    // --- Utility Functions ---
    function getStatusIcon(type) {
        switch (type) {
            case 'success': return '<i class="fas fa-check-circle text-2xl"></i>';
            case 'error': return '<i class="fas fa-times-circle text-2xl"></i>';
            case 'warning': return '<i class="fas fa-exclamation-triangle text-2xl"></i>';
            case 'info': return '<i class="fas fa-video text-2xl"></i>'; // Changed info icon
            default: return '';
        }
    }

    function showMessage(message, type) {
        // Use a slight delay to ensure the DOM is ready for the message update
        setTimeout(() => {
            const icon = getStatusIcon(type);
            const className = `status-${type}`;

            // 1. Update content and classes
            messageBox.innerHTML = `${icon} <span>${message}</span>`;
            messageBox.className = `p-4 rounded-lg text-lg font-semibold flex items-center justify-center space-x-3`;
            messageBox.classList.add(className);

            // 2. Show the toast
            messageBox.classList.remove('hidden');
            // Timeout to apply the transition after classes are set
            setTimeout(() => {
                messageBox.classList.add('show');
            }, 10);

            // 3. Hide message after 5 seconds
            setTimeout(() => {
                messageBox.classList.remove('show');
                // Hide element fully after transition completes
                setTimeout(() => {
                    messageBox.classList.add('hidden');
                    // Clean up specific state classes
                    messageBox.classList.remove(className);
                }, 500); // Transition time
            }, 5000);
        }, 50);
    }

    // --- Camera Control Logic ---

    function updateButtonState(state) {
        if (state === 'ready') {
            startScanBtn.disabled = false;
            stopScanBtn.disabled = true;
            cameraSelect.disabled = false;
        } else if (state === 'scanning') {
            startScanBtn.disabled = true;
            stopScanBtn.disabled = false;
            cameraSelect.disabled = true;
        } else if (state === 'loading') {
            startScanBtn.disabled = true;
            stopScanBtn.disabled = true;
            cameraSelect.disabled = true;
        }
    }

    async function getCameras() {
        try {
            updateButtonState('loading');
            const cameras = await Html5Qrcode.getCameras();

            if (cameras.length === 0) {
                showMessage('No cameras found on this device.', 'error');
                return;
            }

            // Clear old options
            cameraSelect.innerHTML = '<option value="">Select Camera (Default)</option>';

            cameras.forEach(camera => {
                const option = document.createElement('option');
                option.value = camera.id;
                option.text = camera.label || `Camera ${cameraSelect.options.length}`;
                cameraSelect.appendChild(option);
            });

            // Set default camera (usually the back camera or first one available)
            currentCameraId = cameras[0].id;
            cameraSelect.value = currentCameraId;
            updateButtonState('ready');

            // Auto-start scanning on the best available camera
            startScanning(currentCameraId);

        } catch (err) {
            console.error('Error fetching cameras:', err);
            showMessage('Error starting camera. Please check permissions.', 'error');
            updateButtonState('ready');
        }
    }

    // --- Scanner Logic ---

    async function startScanning(cameraId = currentCameraId) {
        if (isScanning || !cameraId || !html5Qrcode) return;

        try {
            updateButtonState('loading');
            showMessage('Starting camera...', 'info');
            await html5Qrcode.start(
                cameraId,
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                (errorMessage) => { /* Ignore minor failures */ }
            );
            isScanning = true;
            currentCameraId = cameraId;
            showMessage('Scanning active. Ready to scan QR code.', 'info');
            updateButtonState('scanning');
        } catch (err) {
            console.error('Error starting scan:', err);
            showMessage('Failed to start scanning. Check camera permissions.', 'error');
            updateButtonState('ready');
        }
    }

    async function stopScanning() {
        if (!isScanning || !html5Qrcode) return;
        try {
            updateButtonState('loading');
            await html5Qrcode.stop();
            isScanning = false;
            showMessage('Scanning stopped. Click Start to resume.', 'warning');
            updateButtonState('ready');
        } catch (err) {
            console.error('Error stopping scan:', err);
            isScanning = false; // Force state update
            updateButtonState('ready');
        }
    }

    function onScanSuccess(decodedText) {
        if (isProcessing) return; // Ignore if already processing

        const now = Date.now();
        if (now - lastScanTime < scanCooldown) {
            // Cooldown active, ignore scan
            return;
        }

        lastScanTime = now;

        // Vibrate for feedback
        if (navigator.vibrate) {
            navigator.vibrate(100);
        }

        // Send the scanned token to the server
        sendScanData(decodedText);
    }

    async function sendScanData(qrToken) {
        isProcessing = true;
        
        // Pause the camera feed to freeze the frame
        if(html5Qrcode && isScanning) {
            try {
                html5Qrcode.pause(true); 
            } catch(e) {
                console.warn("Could not pause camera", e);
            }
        }

        // Basic check to see if the scanned data looks like our expected token format (a long base64 string)
        if (qrToken.length < 10) {
            showModal('Invalid QR Code', 'The scanned code is invalid.', 'error', resumeScanning);
            return;
        }

        try {
            const response = await fetch('api/log_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                // Send the decoded text as the secure token
                body: JSON.stringify({ qr_token: qrToken })
            });

            const result = await response.json();

            if (result.success) {
                showModal('Success!', result.message, 'success', resumeScanning);
            } else {
                showModal('Error', result.message, 'error', resumeScanning);
            }

        } catch (error) {
            console.error('Error:', error);
            showModal('System Error', 'Could not connect to log server.', 'error', resumeScanning);
        }
    }

    function resumeScanning() {
        hideModal();
        if(html5Qrcode && isScanning) {
            try {
                html5Qrcode.resume();
            } catch(e) {
                console.warn("Could not resume camera", e);
            }
        }
        isProcessing = false;
    }

    // --- Initialization ---

    function initializeScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            // If Html5Qrcode is not yet defined, retry initialization shortly
            console.warn('Html5Qrcode not defined, retrying...');
            setTimeout(initializeScanner, 100);
            return;
        }

        html5Qrcode = new Html5Qrcode('qr-reader', { verbose: false });

        // Attach event listener for camera selection change
        cameraSelect.addEventListener('change', (e) => {
            const newCameraId = e.target.value;
            if (isScanning) {
                // If scanning, stop first, then restart with the new ID
                stopScanning().then(() => {
                    startScanning(newCameraId);
                });
            } else {
                currentCameraId = newCameraId;
            }
        });

        // Find cameras and start scanning
        getCameras();
    }

    document.addEventListener('DOMContentLoaded', initializeScanner);

    // --- PIN Modal Logic ---
    const pinModal = document.getElementById('pin-modal');
    const pinInput = document.getElementById('pin-input');
    const pinErrorMsg = document.getElementById('pin-error-msg');
    let currentPin = '';
    let pinMaskTimeout = null;

    function showPinModal() {
        pinModal.classList.remove('hidden');
        currentPin = '';
        updatePinDisplay(false);
        pinErrorMsg.classList.add('hidden'); // Hide error on open
        pinErrorMsg.textContent = '';
        
        // Pause scanner if active
        if(html5Qrcode && isScanning) {
            try {
                html5Qrcode.pause(true); 
            } catch(e) {
                console.warn("Could not pause camera", e);
            }
        }
        // Add keyboard listener
        document.addEventListener('keydown', handleKeyboardInput);
    }

    function hidePinModal() {
        pinModal.classList.add('hidden');
        currentPin = '';
        updatePinDisplay(false);
        pinErrorMsg.classList.add('hidden');
        
        // Resume scanner if it was active
        if(html5Qrcode && isScanning) {
            try {
                html5Qrcode.resume();
            } catch(e) {
                console.warn("Could not resume camera", e);
            }
        }
        // Remove keyboard listener
        document.removeEventListener('keydown', handleKeyboardInput);
    }

    function handleKeyboardInput(e) {
        if (e.key >= '0' && e.key <= '9') {
            appendPin(e.key);
        } else if (e.key === 'Backspace') {
            backspacePin();
        } else if (e.key === 'Escape') {
            hidePinModal();
        } else if (e.key === 'Enter') {
            submitPin();
        }
    }

    function appendPin(digit) {
        // Clear error when typing starts
        if (pinErrorMsg.textContent) {
            pinErrorMsg.classList.add('hidden');
            pinErrorMsg.textContent = '';
        }

        if (currentPin.length < 6) {
            currentPin += digit;
            
            // Show last digit
            updatePinDisplay(true);
            
            // Clear existing timeout
            if (pinMaskTimeout) clearTimeout(pinMaskTimeout);
            
            // Set timeout to mask after 1 second
            pinMaskTimeout = setTimeout(() => {
                updatePinDisplay(false);
            }, 1000);

            // Auto-submit if length is 6
            if (currentPin.length === 6) {
                setTimeout(submitPin, 300);
            }
        }
    }

    function clearPin() {
        currentPin = '';
        updatePinDisplay(false);
        pinErrorMsg.classList.add('hidden');
    }

    function backspacePin() {
        currentPin = currentPin.slice(0, -1);
        updatePinDisplay(false);
        pinErrorMsg.classList.add('hidden');
    }

    function updatePinDisplay(showLastDigit) {
        if (!currentPin) {
            pinInput.value = '';
            return;
        }

        const maskChar = '•'; // Bullet character

        if (showLastDigit) {
            const maskedPart = maskChar.repeat(currentPin.length - 1);
            const lastDigit = currentPin.slice(-1);
            pinInput.value = maskedPart + lastDigit;
        } else {
            pinInput.value = maskChar.repeat(currentPin.length);
        }
    }

    async function submitPin() {
        if (currentPin.length !== 6) {
            return;
        }

        // Disable input while processing (optional but good UX)
        
        try {
            const response = await fetch('api/log_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ pin: currentPin })
            });

            const result = await response.json();

            // Define a callback to resume scanning after the modal closes
            const resumeCallback = () => {
                 if(html5Qrcode && isScanning) {
                    try {
                        html5Qrcode.resume();
                    } catch(e) {
                        console.warn("Could not resume camera", e);
                    }
                }
            };

            if (result.success) {
                // Success: Close PIN modal and show Success Modal
                hidePinModal(); // This handles removing listeners and resuming scanner logic internally, but we override resume logic in callback
                showModal('Success!', result.message, 'success', resumeCallback);
            } else {
                // Error: Keep PIN modal open, Shake, Show Error Text
                
                // 1. Show Error Text
                pinErrorMsg.textContent = result.message || 'Invalid PIN';
                pinErrorMsg.classList.remove('hidden');
                
                // 2. Shake Animation
                const modalContent = pinModal.querySelector('.inline-block'); // The modal card
                modalContent.classList.remove('shake'); // Reset animation
                void modalContent.offsetWidth; // Trigger reflow
                modalContent.classList.add('shake');
                
                // 3. Clear PIN
                currentPin = '';
                updatePinDisplay(false);
            }

        } catch (error) {
            console.error('Error:', error);
            // System error can still use the main modal or the inline error
            pinErrorMsg.textContent = 'System Error. Try again.';
            pinErrorMsg.classList.remove('hidden');
        }
    }
</script>
</body>
</html>
