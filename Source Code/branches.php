<?php
// 1. Define the variables for this specific page
$page_title = 'Branch Management';
$active_page = 'branches'; // This will highlight the "Branches" link in the sidebar

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
                <h1 class="text-2xl font-bold text-gray-900">Branch Management</h1>
                <button id="add-branch-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Add New Branch
                </button>
            </div>

            <!-- Branch Table -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div id="table-loader" class="flex justify-center items-center py-8">
                    <div class="loader"></div>
                </div>
                <div id="branches-table-container" class="overflow-x-auto hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="branches-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Branch rows injected by JS -->
                        </tbody>
                    </table>
                </div>
                <div id="table-no-items" class="hidden text-center text-gray-500 py-8">
                    <i class="fas fa-store text-4xl text-gray-400"></i>
                    <p class="mt-2">No branches found. Add one to get started!</p>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Overlay for mobile menu -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Modals -->

    <!-- Add/Edit Branch Modal -->
    <div id="branch-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 id="modal-title" class="text-2xl font-bold mb-4">Add New Branch</h2>
            <form id="branch-form">
                <input type="hidden" id="branch-id" name="branch_id">
                
                <div>
                    <label for="branch-name" class="block text-sm font-medium text-gray-700">Branch Name *</label>
                    <input type="text" id="branch-name" name="branch_name" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                </div>

                <div id="form-error" class="hidden text-red-600 text-sm mt-4"></div>

                <div class="mt-6 flex justify-end">
                    <button type="button" id="cancel-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="save-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">Save Branch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 class="text-xl font-bold mb-4">Confirm Deletion</h2>
            <p>Are you sure you want to delete <strong id="delete-branch-name">this branch</strong>? All stock associated with this branch will also be deleted. This action cannot be undone.</p>
            <div id="delete-error" class="hidden text-red-600 text-sm mt-4"></div>
            <div class="mt-6 flex justify-end">
                <button type="button" id="cancel-delete-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                <button type="button" id="confirm-delete-btn" class="px-4 py-2 bg-red-600 text-white rounded-md font-medium hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>

<!-- This page's specific JS file -->
<script src="assets/js/branches.js"></script>

<?php
// 5. Include the footer (which includes main.js)
include 'admin/_footer.php';
?>