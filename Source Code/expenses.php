<?php
// 1. Define the variables for this specific page
$page_title = 'Expense Management';
$active_page = 'expenses';

// 2. Include the header
include 'admin/_header.php';

// 3. Include the sidebar
include 'admin/_sidebar.php';

$user_role = $_SESSION['role'] ?? 'staff';
?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen bg-gray-100">
        
        <?php include 'admin/_topbar.php'; ?>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto" id="expenses-page-container" data-role="<?php echo $user_role; ?>">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-2xl font-bold text-gray-900">Expenses</h1>
                
                <div class="flex gap-4">
                    <!-- NEW: Admin Filter for List -->
                    <div id="list-filter-wrapper" class="hidden">
                        <select id="filter-branch" class="border border-gray-300 rounded-md p-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Branches</option>
                            <!-- Branches loaded by JS -->
                        </select>
                    </div>

                    <button id="add-expense-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Record Expense
                    </button>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div id="table-loader" class="flex justify-center items-center py-8">
                    <div class="loader"></div>
                </div>
                <div id="expenses-table-container" class="overflow-x-auto hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="expenses-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>
                </div>
                <div id="table-no-items" class="hidden text-center text-gray-500 py-8">
                    <i class="fas fa-receipt text-4xl text-gray-400"></i>
                    <p class="mt-2">No expenses recorded found.</p>
                </div>
                <div id="table-error" class="hidden text-center text-red-500 py-4"></div>
            </div>
        </main>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Add/Edit Expense Modal -->
    <div id="expense-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 id="modal-title" class="text-xl font-bold mb-4">Record Expense</h2>
            <form id="expense-form">
                <input type="hidden" id="expense-id" name="expense_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Date *</label>
                    <input type="date" id="expense-date" name="expense_date" class="w-full border border-gray-300 rounded-md p-2" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Category *</label>
                    <select id="expense-category" name="category" class="w-full border border-gray-300 rounded-md p-2" required>
                        <option value="Rent">Rent</option>
                        <option value="Electricity">Electricity</option>
                        <option value="Water">Water</option>
                        <option value="Salary">Salary</option>
                        <option value="Tea/Refreshments">Tea/Refreshments</option>
                        <option value="Transport">Transport</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Amount (LKR) *</label>
                    <input type="number" id="expense-amount" name="amount" step="0.01" class="w-full border border-gray-300 rounded-md p-2" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="expense-description" name="description" rows="2" class="w-full border border-gray-300 rounded-md p-2"></textarea>
                </div>
                
                <!-- Admin Branch Select (Inside Modal) -->
                <div id="expense-branch-wrapper" class="mb-4 hidden">
                    <label class="block text-sm font-medium text-gray-700">Branch *</label>
                    <select name="branch_id" id="expense-branch-select" class="w-full border border-gray-300 rounded-md p-2">
                        <option value="">Select Branch</option>
                        <!-- Filled by JS -->
                    </select>
                </div>

                <div id="form-error" class="hidden text-red-600 text-sm mt-4 mb-2"></div>

                <div class="flex justify-end">
                    <button type="button" id="cancel-expense-btn" class="px-4 py-2 bg-gray-200 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="save-expense-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 class="text-xl font-bold mb-4">Confirm Deletion</h2>
            <p>Are you sure you want to delete this expense record?</p>
            <div id="delete-error" class="hidden text-red-600 text-sm mt-4"></div>
            <div class="mt-6 flex justify-end">
                <button type="button" id="cancel-delete-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                <button type="button" id="confirm-delete-btn" class="px-4 py-2 bg-red-600 text-white rounded-md font-medium hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>

<!-- This page's specific JS file -->
<script src="assets/js/expenses.js"></script>

<?php include 'admin/_footer.php'; ?>