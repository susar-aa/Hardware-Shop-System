<?php
// 1. Define the variables for this specific page
$page_title = 'User Management';
$active_page = 'users'; // This will highlight the "Users" link in the sidebar

// 2. Include the header
include 'admin/_header.php';

// 3. Include the sidebar
include 'admin/_sidebar.php';
?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen">
        
        <?php
        // 4. Include the top bar
        include 'admin/_topbar.php';
        ?>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
                <button id="add-user-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Add New User
                </button>
            </div>

            <!-- User Table -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div id="table-loader" class="flex justify-center items-center py-8">
                    <div class="loader"></div>
                </div>
                <div id="users-table-container" class="overflow-x-auto hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- User rows injected by JS -->
                        </tbody>
                    </table>
                </div>
                <div id="table-no-items" class="hidden text-center text-gray-500 py-8">
                    <i class="fas fa-users text-4xl text-gray-400"></i>
                    <p class="mt-2">No users found. Add one to get started!</p>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Overlay for mobile menu -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Modals -->

    <!-- Add/Edit User Modal -->
    <div id="user-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 id="modal-title" class="text-2xl font-bold mb-4">Add New User</h2>
            <form id="user-form">
                <input type="hidden" id="user-id" name="user_id">
                
                <div class="mb-4">
                    <label for="user-name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                    <input type="text" id="user-name" name="name" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                </div>

                <div class="mb-4">
                    <label for="user-email" class="block text-sm font-medium text-gray-700">Email *</label>
                    <input type="email" id="user-email" name="email" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                </div>

                <div class="mb-4">
                    <label for="user-role" class="block text-sm font-medium text-gray-700">Role *</label>
                    <select id="user-role" name="role" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <!-- NEW: Branch selection dropdown -->
                <div id="user-branch-wrapper" class="mb-4 hidden">
                    <label for="user-branch" class="block text-sm font-medium text-gray-700">Branch *</label>
                    <select id="user-branch" name="branch_id" class="mt-1 w-full border border-gray-300 rounded-md p-2">
                        <option value="">Loading branches...</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="user-password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="user-password" name="password" class="mt-1 w-full border border-gray-300 rounded-md p-2">
                    <p id="password-help" class="text-xs text-gray-500 mt-1">Required when creating a new user. Leave blank when editing to keep the password unchanged.</p>
                </div>

                <div id="form-error" class="hidden text-red-600 text-sm mt-4"></div>

                <div class="mt-6 flex justify-end">
                    <button type="button" id="cancel-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="save-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 class="text-xl font-bold mb-4">Confirm Deletion</h2>
            <p>Are you sure you want to delete <strong id="delete-user-name">this user</strong>? This action cannot be undone.</p>
            <div id="delete-error" class="hidden text-red-600 text-sm mt-4"></div>
            <div class="mt-6 flex justify-end">
                <button type="button" id="cancel-delete-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                <button type="button" id="confirm-delete-btn" class="px-4 py-2 bg-red-600 text-white rounded-md font-medium hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>

<!-- This page's specific JS file -->
<script src="assets/js/users.js"></script>

<?php
// 5. Include the footer (which includes main.js)
include 'admin/_footer.php';
?>