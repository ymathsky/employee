<!--
FILENAME: employee/template/_announcements_panel.php
This is a reusable panel to show recent announcements on any page.
It fetches data from the 'api/get_announcements.php' endpoint.
-->
<div class="bg-white p-6 rounded-xl shadow-xl">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-800">
            <i class="fas fa-bullhorn text-indigo-500 mr-2"></i>Recent Announcements
        </h2>
        <?php
        // Only show link to management page if user is admin or manager
        if (isset($_SESSION['role']) && ($_SESSION['role'] === 'HR Admin' || $_SESSION['role'] === 'Super Admin' || $_SESSION['role'] === 'Manager')) {
            echo '<a href="announcement_management.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Manage</a>';
        }
        ?>
    </div>

    <div id="dashboard-announcements-list" class="space-y-4 max-h-96 overflow-y-auto pr-2">
        <!-- Loading state -->
        <p id="dashboard-announcements-loading" class="text-gray-500">Loading announcements...</p>
        <!-- Announcements will be injected here by JavaScript -->
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // We use a unique function name to avoid conflicts if this script is loaded on announcement_management.php
        async function fetchDashboardAnnouncements() {
            const listContainer = document.getElementById('dashboard-announcements-list');
            const loadingIndicator = document.getElementById('dashboard-announcements-loading');

            if (!listContainer) return; // Don't run if the container isn't on the page

            try {
                // Fetch the 5 most recent announcements
                const response = await fetch('api/get_announcements.php?limit=5');
                const result = await response.json();

                if (loadingIndicator) {
                    loadingIndicator.classList.add('hidden');
                }

                if (result.success && result.data.length > 0) {
                    // Clear any "loading" text
                    listContainer.innerHTML = '';

                    result.data.forEach(item => {
                        const post = document.createElement('div');
                        post.className = 'p-4 border border-gray-200 rounded-lg bg-gray-50';

                        // Simple function to escape HTML to prevent XSS
                        const escapeHTML = (str) => str.replace(/[&<>"']/g, (match) => {
                            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
                            return map[match];
                        });

                        const title = escapeHTML(item.title);
                        // Replace newlines with <br> tags for display
                        const content = escapeHTML(item.content).replace(/\n/g, '<br>');
                        const author = escapeHTML(item.author_name || 'System');
                        const date = new Date(item.created_at).toLocaleDateString([], { year: 'numeric', month: 'long', day: 'numeric' });

                        post.innerHTML = `
                        <h3 class="text-lg font-semibold text-gray-900">${title}</h3>
                        <p class="text-sm text-gray-700 mt-2 mb-3">${content}</p>
                        <p class="text-xs text-gray-500">By <strong>${author}</strong> on ${date}</p>
                    `;
                        listContainer.appendChild(post);
                    });
                } else if (result.success) {
                    listContainer.innerHTML = '<p class="text-gray-500">No announcements have been posted yet.</p>';
                } else {
                    listContainer.innerHTML = `<p class="text-red-500">Error: ${result.message || 'Could not load announcements.'}</p>`;
                }
            } catch (error) {
                console.error('Error fetching dashboard announcements:', error);
                if (loadingIndicator) {
                    loadingIndicator.classList.add('hidden');
                }
                if (listContainer) {
                    listContainer.innerHTML = '<p class="text-red-500">A network error occurred while loading announcements.</p>';
                }
            }
        }

        fetchDashboardAnnouncements();
    });
</script>
