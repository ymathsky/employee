<?php
// FILENAME: employee/audit_log_viewer.php
$pageTitle = 'Audit Log Viewer';
include 'template/header.php'; // Handles session, auth, DB

// --- Page-Specific Role Check ---
// The header already redirects general admins, but we enforce Super Admin explicitly here.
if ($_SESSION['role'] !== 'Super Admin') {
    header('Location: dashboard.php');
    exit;
}
?>

<div class="bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center space-x-2">
        <i class="fas fa-clipboard-list text-red-600"></i>
        <span>System Audit Log</span>
    </h2>
    <p class="text-gray-600 mb-6">
        Displays the last 500 actions performed in the system, including administrative changes and security events.
    </p>

    <div id="log-message" class="mb-4 hidden p-3 rounded-lg text-center"></div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Timestamp
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Performer
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Action Type
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Details
                </th>
            </tr>
            </thead>
            <tbody id="log-body" class="bg-white divide-y divide-gray-200">
            <tr>
                <td colspan="4" class="text-center p-6 text-gray-500">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Loading audit logs...
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const logBody = document.getElementById('log-body');
    const logMessage = document.getElementById('log-message');

    function showMessage(message, className) {
        logMessage.textContent = message;
        logMessage.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        logMessage.classList.remove('hidden');
    }

    function getActionClass(action) {
        if (action.includes('FAILED') || action.includes('ERROR')) return 'bg-red-100 text-red-800';
        if (action.includes('CREATED') || action.includes('SUCCESS') || action.includes('GENERATED')) return 'bg-green-100 text-green-800';
        if (action.includes('UPDATED') || action.includes('DELETED')) return 'bg-yellow-100 text-yellow-800';
        return 'bg-gray-100 text-gray-800';
    }

    async function loadAuditLogs() {
        try {
            const response = await fetch('api/get_audit_logs.php');
            const result = await response.json();

            logBody.innerHTML = ''; // Clear loading row

            if (result.success && result.data.length > 0) {
                result.data.forEach(log => {
                    const actionClass = getActionClass(log.action);

                    const row = `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${new Date(log.log_timestamp).toLocaleString()}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ${log.performer_name}
                                ${log.performer_id > 0 ? `<span class="text-xs text-gray-400 block">(ID: ${log.performer_id})</span>` : ''}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${actionClass}">
                                    ${log.action}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xl">
                                ${log.description}
                            </td>
                        </tr>
                    `;
                    logBody.insertAdjacentHTML('beforeend', row);
                });
            } else if (result.success) {
                logBody.innerHTML = '<tr><td colspan="4" class="text-center p-6 text-gray-500">No audit logs found.</td></tr>';
            } else {
                showMessage(result.message, 'bg-red-100 text-red-700');
                logBody.innerHTML = '<tr><td colspan="4" class="text-center p-6 text-red-500">Failed to load logs.</td></tr>';
            }

        } catch (error) {
            console.error('Network Error:', error);
            showMessage('Network error fetching audit logs.', 'bg-red-100 text-red-700');
            logBody.innerHTML = '<tr><td colspan="4" class="text-center p-6 text-red-500">Network error.</td></tr>';
        }
    }

    document.addEventListener('DOMContentLoaded', loadAuditLogs);
</script>

<?php
include 'template/footer.php';
?>
