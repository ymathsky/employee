<?php
// FILENAME: employee/announcement_management.php

// Set the page title before including the header
$pageTitle = 'Announcement Management';
include 'template/header.php';

// Only allow Admins
if ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin') {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Access Denied. You do not have permission to view this page.</div>";
    include 'template/footer.php';
    exit;
}
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Create/Edit Announcement Form -->
    <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-xl shadow-xl">
            <h2 id="form-title" class="text-xl font-semibold text-gray-800 mb-4">Create New Announcement</h2>
            <form id="announcementForm">
                <!-- Hidden input for editing -->
                <input type="hidden" id="announcement_id" name="announcement_id">

                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" id="title" name="title" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div class="mb-4">
                    <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                    <textarea id="content" name="content" rows="6" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                </div>

                <div id="form-message" class="mb-4 hidden p-3 rounded-lg text-center"></div>

                <div class="flex justify-between items-center">
                    <button type="button" id="cancelButton" onclick="resetForm()" class="text-gray-600 hover:text-gray-800 text-sm font-medium hidden">&larr; Cancel Edit</button>
                    <button type="submit" id="submitButton" class="px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        Post Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Announcements List -->
    <div class="lg:col-span-2">
        <div class="bg-white p-6 rounded-xl shadow-xl">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Published Announcements</h2>
            <div id="announcements-list" class="space-y-4">
                <!-- Loading state -->
                <p id="list-loading" class="text-gray-500">Loading announcements...</p>
                <!-- Announcements will be injected here -->
            </div>
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('announcementForm');
    const formTitle = document.getElementById('form-title');
    const formMessage = document.getElementById('form-message');
    const submitButton = document.getElementById('submitButton');
    const cancelButton = document.getElementById('cancelButton');
    const announcementIdInput = document.getElementById('announcement_id');
    const titleInput = document.getElementById('title');
    const contentInput = document.getElementById('content');
    const listContainer = document.getElementById('announcements-list');
    const listLoading = document.getElementById('list-loading');

    // --- Show Message ---
    function showMessage(message, isError = false) {
        formMessage.textContent = message;
        formMessage.className = `mb-4 p-3 rounded-lg text-center ${isError ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`;
        formMessage.classList.remove('hidden');
    }

    // --- Reset Form ---
    function resetForm() {
        form.reset();
        announcementIdInput.value = '';
        formTitle.textContent = 'Create New Announcement';
        submitButton.textContent = 'Post Announcement';
        cancelButton.classList.add('hidden');
        formMessage.classList.add('hidden');
    }

    // --- Load Announcements ---
    async function fetchAnnouncements() {
        try {
            const response = await fetch('api/get_announcements.php');
            const result = await response.json();

            listLoading.classList.add('hidden');
            listContainer.innerHTML = ''; // Clear

            if (result.success && result.data.length > 0) {
                result.data.forEach(item => {
                    const post = document.createElement('div');
                    post.className = 'p-4 border border-gray-200 rounded-lg';
                    post.id = `item-${item.announcement_id}`;
                    post.innerHTML = `
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-lg font-semibold text-gray-900">${item.title}</h3>
                            <div class="flex-shrink-0 space-x-3">
                                <button onclick="loadForEdit(${item.announcement_id}, '${escapeJS(item.title)}', '${escapeJS(item.content)}')" class="text-indigo-600 hover:text-indigo-900 text-sm">Edit</button>
                                <button onclick="deleteAnnouncement(${item.announcement_id})" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                            </div>
                        </div>
                        <p class="text-sm text-gray-700 mb-2">${item.content.replace(/\n/g, '<br>')}</p>
                        <p class="text-xs text-gray-400">By ${item.author_name || 'System'} on ${new Date(item.created_at).toLocaleDateString()}</p>
                    `;
                    listContainer.appendChild(post);
                });
            } else {
                listContainer.innerHTML = '<p class="text-gray-500">No announcements found.</p>';
            }
        } catch (error) {
            console.error('Error fetching announcements:', error);
            listLoading.classList.add('hidden');
            listContainer.innerHTML = '<p class="text-red-500">Error loading announcements.</p>';
        }
    }

    // --- Load for Edit ---
    function loadForEdit(id, title, content) {
        announcementIdInput.value = id;
        titleInput.value = title;
        contentInput.value = content;

        formTitle.textContent = 'Edit Announcement';
        submitButton.textContent = 'Update Post';
        cancelButton.classList.remove('hidden');
        window.scrollTo(0, 0); // Scroll to top
    }

    // --- Handle Form Submit (Create or Update) ---
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        submitButton.disabled = true;

        const id = announcementIdInput.value;
        const isEditing = !!id;

        const url = isEditing ? 'api/update_announcement.php' : 'api/add_announcement.php';
        const body = {
            title: titleInput.value,
            content: contentInput.value
        };

        if (isEditing) {
            body.announcement_id = id;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const result = await response.json();

            if (result.success) {
                showMessage(result.message, false);
                resetForm();
                await fetchAnnouncements(); // Refresh list
            } else {
                showMessage(result.message, true);
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            showMessage('A network error occurred.', true);
        } finally {
            submitButton.disabled = false;
        }
    });

    // --- Delete Announcement ---
    async function deleteAnnouncement(id) {
        if (!confirm('Are you sure you want to delete this announcement?')) {
            return;
        }

        try {
            const response = await fetch('api/delete_announcement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ announcement_id: id })
            });
            const result = await response.json();

            if (result.success) {
                document.getElementById(`item-${id}`).remove();
                showMessage('Announcement deleted.', false);
            } else {
                showMessage(result.message, true);
            }
        } catch (error) {
            console.error('Error deleting:', error);
            showMessage('A network error occurred.', true);
        }
    }

    // --- Helper to escape strings for JS ---
    function escapeJS(str) {
        return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0').replace(/\n/g, '\\n');
    }

    // --- Initial Load ---
    document.addEventListener('DOMContentLoaded', fetchAnnouncements);
</script>

<?php
// Include the footer
include 'template/footer.php';
?>
