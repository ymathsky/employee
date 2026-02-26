<?php
// FILENAME: employee/my_qr_code.php
$pageTitle = 'My QR Code';
include 'template/header.php'; // Handles session and login check

// Get the employee ID from the session
$employee_id = $_SESSION['user_id'];
$employee_name = $_SESSION['username'];

// The data to be encoded in the QR code is now a secure token
$qr_data = ''; // Initialized to empty, will be set by JavaScript
?>

<div class="container mx-auto max-w-md text-center">

    <div class="bg-white p-8 rounded-xl shadow-xl">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Your Secure Employee QR Code</h2>
        <p class="text-gray-600 mb-6">This code changes every minute for security. Show the **active** code to the kiosk.</p>

        <div class="flex justify-center mb-6">
            <!-- This is the container where JavaScript will draw the QR code -->
            <div id="qrcode-container" class="border-4 border-gray-300 rounded-lg p-2 flex items-center justify-center">
                <i class="fas fa-spinner fa-spin text-4xl text-indigo-500"></i>
            </div>
        </div>

        <p class="text-lg font-medium text-gray-700">
            Employee: <?php echo htmlspecialchars($employee_name); ?>
        </p>
        <p class="text-gray-500">
            ID: <?php echo htmlspecialchars($employee_id); ?>
        </p>

        <div id="countdown-message" class="mt-4 text-sm font-medium text-red-600"></div>

        <!-- NEW: PIN Display -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Kiosk PIN</h3>
            <p class="text-sm text-gray-500 mb-4">Use this PIN if you cannot scan your QR code. It refreshes every 15 seconds.</p>
            
            <div class="bg-gray-100 rounded-lg p-4 inline-block">
                <div id="pin-display" class="text-4xl font-mono font-bold text-indigo-600 tracking-widest">
                    ------
                </div>
            </div>
            <div id="pin-countdown" class="mt-2 text-xs text-gray-500"></div>
        </div>

        <button onclick="window.print()" class="mt-6 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Print Code
        </button>
    </div>
</div>

<!-- Using the more reliable qrcodejs library link -->
<script src="https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>
    const container = document.getElementById("qrcode-container");
    const countdownMessage = document.getElementById("countdown-message");
    const pinDisplay = document.getElementById("pin-display");
    const pinCountdown = document.getElementById("pin-countdown");
    
    // Timers
    let qrTimeout = null;
    let pinTimeout = null;
    let countdownInterval = null;
    let pinCountdownInterval = null;

    // Constants
    const QR_DURATION = 60; // seconds
    const PIN_DURATION = 15; // seconds
    
    // NEW: Setting
    let autoRefreshEnabled = true;

    // 0. Fetch Settings
    async function loadSettings() {
        try {
            const res = await fetch('api/get_settings.php');
            const data = await res.json();
            if (data.success && data.data.auto_refresh_qr == '0') {
                autoRefreshEnabled = false;
            }
        } catch(e) { console.error('Settings load error', e); }
    }

    // 1. Fetch QR Code (Every 60s)
    async function updateQR() {
        try {
            // Only show spinner if it's the first load or we want to indicate loading
            if (!container.hasChildNodes() || container.innerHTML.includes('fa-spinner')) {
                 // Keep spinner
            }
            
            const response = await fetch('api/generate_qr_token.php?mode=qr');
            const result = await response.json();

            if (result.success && result.token) {
                renderQRCode(result.token);
                
                if (autoRefreshEnabled) {
                    startCountdown(QR_DURATION);
                    // Schedule next update only if enabled
                    if (qrTimeout) clearTimeout(qrTimeout);
                    qrTimeout = setTimeout(updateQR, QR_DURATION * 1000);
                } else {
                    countdownMessage.textContent = "Static QR Code (Does not expire)";
                    countdownMessage.className = "text-xl text-blue-600 font-bold mt-4";
                    if (countdownInterval) clearInterval(countdownInterval);
                }

            } else {
                container.innerHTML = '<i class="fas fa-times-circle text-4xl text-red-500"></i>';
                countdownMessage.textContent = `Error: ${result.message || 'Unknown error'}`;
            }
        } catch (error) {
            console.error('Error fetching QR:', error);
            container.innerHTML = '<i class="fas fa-times-circle text-4xl text-red-500"></i>';
            countdownMessage.textContent = 'Network error. Could not load token.';
        }
    }

    // 2. Fetch PIN (Every 15s)
    async function updatePIN() {
        try {
            const response = await fetch('api/generate_qr_token.php?mode=pin');
            const result = await response.json();

            if (result.success && result.pin) {
                pinDisplay.textContent = result.pin;
                startPinCountdown(PIN_DURATION);
            }
        } catch (error) {
            console.error('Error fetching PIN:', error);
            pinDisplay.textContent = 'Error';
        }

        // Schedule next update
        pinTimeout = setTimeout(updatePIN, PIN_DURATION * 1000);
    }

    // 3. Render the QR Code
    function renderQRCode(token) {
        if (typeof QRCode === 'undefined') {
            setTimeout(() => renderQRCode(token), 100); // Retry if script not loaded
            return;
        }

        // Clear old content
        container.innerHTML = '';

        // Generate the new QR Code
        new QRCode(container, {
            text: token,
            width: 300,
            height: 300,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    }

    // 4. Start the visual countdown for the user
    function startCountdown(duration) {
        // Clear existing countdown interval to prevent overlaps
        if (countdownInterval) clearInterval(countdownInterval);

        let remaining = duration;

        // Update immediately
        updateCountdownDisplay(remaining);

        countdownInterval = setInterval(() => {
            remaining--;
            if (remaining < 0) {
                clearInterval(countdownInterval);
                countdownMessage.textContent = "Refreshing...";
                return;
            }
            updateCountdownDisplay(remaining);
        }, 1000);
    }

    function updateCountdownDisplay(remaining) {
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;

        countdownMessage.textContent = `Time until refresh: ${minutes}:${seconds.toString().padStart(2, '0')}`;

        if (remaining < 10) {
            countdownMessage.classList.add('text-red-600');
            countdownMessage.classList.remove('text-green-600');
        } else {
            countdownMessage.classList.add('text-green-600');
            countdownMessage.classList.remove('text-red-600');
        }
    }

    // 5. Start PIN Countdown
    function startPinCountdown(duration) {
        if (pinCountdownInterval) clearInterval(pinCountdownInterval);
        
        let remaining = duration;
        pinCountdown.textContent = `Refreshes in: ${remaining}s`;
        
        pinCountdownInterval = setInterval(() => {
            remaining--;
            if (remaining < 0) {
                clearInterval(pinCountdownInterval);
                pinCountdown.textContent = "Refreshing...";
                return;
            }
            pinCountdown.textContent = `Refreshes in: ${remaining}s`;
        }, 1000);
    }

    function clearTimers() {
        if (qrTimeout) clearTimeout(qrTimeout);
        if (pinTimeout) clearTimeout(pinTimeout);
        if (countdownInterval) clearInterval(countdownInterval);
        if (pinCountdownInterval) clearInterval(pinCountdownInterval);
    }

    // Initial load and cleanup on exit
    document.addEventListener('DOMContentLoaded', async () => {
        await loadSettings();
        updateQR();
        updatePIN();
    });
    window.addEventListener('beforeunload', clearTimers);

</script>

<?php
include 'template/footer.php';
?>
