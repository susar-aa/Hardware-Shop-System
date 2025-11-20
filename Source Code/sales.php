<?php
// CRITICAL FIX: Start the session to access $_SESSION['role']
session_start();

// 1. Define the variables for this specific page
$page_title = 'Sales Tracker';
$active_page = 'sales'; // This will highlight the "Sales Tracker" link in the sidebar

// 2. Include the header
include 'admin/_header.php';

// 3. Include the sidebar
include 'admin/_sidebar.php';

// Get current user role for client-side logic
$user_role = $_SESSION['role'] ?? 'staff'; 
?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen">
        
        <?php
        // 4. Include the top bar
        include 'admin/_topbar.php';
        ?>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto">
            
            <!-- Passes user role to JS for filter logic -->
            <div id="sales-tracker-container" data-role="<?php echo $user_role; ?>">

                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Sales Transactions</h1>
                </div>

                <!-- Filter Controls -->
                <!-- The structure ensures Admin sees the Branch filter, Staff does not -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    <!-- Date Start -->
                    <div>
                        <label for="filter-start-date" class="block text-sm font-medium text-gray-700">From Date</label>
                        <input type="date" id="filter-start-date" class="mt-1 w-full border border-gray-300 rounded-md p-2">
                    </div>
                    
                    <!-- Date End -->
                    <div>
                        <label for="filter-end-date" class="block text-sm font-medium text-gray-700">To Date</label>
                        <input type="date" id="filter-end-date" class="mt-1 w-full border border-gray-300 rounded-md p-2">
                    </div>
                    
                    <!-- Branch Filter (Admin Only - Hidden by default, shown by JS if Admin) -->
                    <div id="admin-branch-filter-wrapper" class="hidden">
                        <label for="filter-branch" class="block text-sm font-medium text-gray-700">Branch</label>
                        <select id="filter-branch" class="mt-1 w-full border border-gray-300 rounded-md p-2">
                            <option value="">All Branches</option>
                            <!-- Branches loaded by JS -->
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div>
                        <button id="apply-filters-btn" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">
                            Apply Filters
                        </button>
                    </div>

                </div>

                <!-- Sales Table -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div id="table-loader" class="flex justify-center items-center py-8">
                        <div class="loader"></div>
                    </div>
                    <div id="sales-table-container" class="overflow-x-auto hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items Sold</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sold By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase admin-only">Action</th>
                                </tr>
                            </thead>
                            <tbody id="sales-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Sales rows injected by JS -->
                            </tbody>
                        </table>
                    </div>
                    <div id="table-no-items" class="hidden text-center text-gray-500 py-8">
                        <i class="fas fa-tags text-4xl text-gray-400"></i>
                        <p class="mt-2">No sales transactions found for the selected criteria.</p>
                    </div>
                    <div id="table-error" class="hidden text-center text-red-500 py-8"></div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Overlay for mobile menu -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Sale Reversal Modal (Edit/Delete) -->
    <div id="reversal-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 class="text-xl font-bold mb-4">Confirm Sale Reversal</h2>
            <p class="mb-4">You are about to reverse the following transaction. This will **add the stock back** to the branch's inventory.</p>
            
            <div class="p-3 bg-red-50 rounded-md border border-red-200 mb-4">
                <div class="text-sm font-semibold">Sale ID: <span id="reverse-id"></span></div>
                <div class="text-sm">Total Amount: <span id="reverse-total"></span></div>
                <div class="text-sm">Items Sold: <span id="reverse-items"></span></div>
            </div>

            <form id="reversal-form">
                <input type="hidden" id="reverse-sale-id">
                
                <div class="mb-4">
                    <label for="reversal-notes" class="block text-sm font-medium text-gray-700">Reason for Reversal (Required)</label>
                    <textarea id="reversal-notes" rows="3" class="mt-1 w-full border border-gray-300 rounded-md p-2" required placeholder="e.g., Customer refund, Wrong item billed"></textarea>
                </div>
                
                <div id="reversal-error" class="hidden text-red-600 text-sm mt-4"></div>

                <div class="mt-6 flex justify-end">
                    <button type="button" id="cancel-reversal-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="confirm-reversal-btn" class="px-4 py-2 bg-red-600 text-white rounded-md font-medium hover:bg-red-700">
                        Confirm Reversal
                    </button>
                </div>
            </form>
        </div>
    </div>

<!-- This page's specific JS file -->
<script src="assets/js/sales.js"></script>

<?php
// 5. Include the footer (which includes main.js)
include 'admin/_footer.php';
?>