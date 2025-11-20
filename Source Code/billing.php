<?php
// 1. Define the variables for this specific page
$page_title = 'New Sale / Billing';
$active_page = 'billing'; // This will highlight the "Billing" link in the sidebar

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
            
            <!-- This ID is used by assets/js/billing.js to initialize the page -->
            <div id="billing-page-container">

                <!-- Page Header -->
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">New Sale / Billing</h1>
                </div>

                <!-- Form Error/Success Messages -->
                <div id="billing-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-4"></div>
                <div id="billing-success" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md mb-4"></div>

                <!-- Branch Info (Visible for Staff, Selector for Admin) -->
                <div id="branch-info-section" class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 p-4 mb-6 rounded-md shadow-sm">
                    <div id="branch-display-static" class="text-lg font-semibold hidden">
                        Billing from: <span id="current-branch-name" class="font-bold text-gray-900">...</span>
                    </div>
                    
                    <!-- Branch Selector (Only visible for Admin) -->
                    <div id="branch-selector-wrapper" class="hidden">
                        <label for="billing-branch-select" class="block text-sm font-medium text-gray-700 mb-1">Select Branch for Sale *</label>
                        <select id="billing-branch-select" class="w-full border border-gray-300 rounded-md p-2" required>
                            <option value="">Loading branches...</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Products shown below will be filtered by this branch's stock.</p>
                    </div>
                </div>

                <!-- Main Billing Layout (2-column) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- Left Column: Product Search & Add -->
                    <div class="md:col-span-1 bg-white rounded-lg shadow-md p-6 h-fit">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Search & Add Item</h2>
                        
                        <!-- Search Bar -->
                        <div class="mb-4 relative">
                            <label for="product-search-input" class="block text-sm font-medium text-gray-700">Search Product by Name/Code</label>
                            <input type="text" id="product-search-input" placeholder="Type product name or code..." class="mt-1 w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500" disabled>
                            <div id="search-loader" class="absolute right-3 top-8 hidden">
                                <i class="fas fa-spinner fa-spin text-gray-500"></i>
                            </div>

                            <!-- Search Results Dropdown -->
                            <div id="search-results-list" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden">
                                <!-- Results injected here -->
                            </div>
                        </div>

                        <p id="search-status" class="text-sm text-red-600 hidden">Please select a branch first.</p>
                        
                    </div>

                    <!-- Right Column: Current Bill -->
                    <div class="md:col-span-2 bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Current Bill</h2>
                        
                        <div id="bill-loader" class="flex justify-center items-center py-8 hidden">
                            <div class="loader"></div>
                        </div>
                        
                        <!-- Bill Items Table -->
                        <div id="bill-table-container" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                                    </tr>
                                </thead>
                                <tbody id="billing-cart-body" class="bg-white divide-y divide-gray-200">
                                    <!-- Bill items will be injected by JS -->
                                </tbody>
                            </table>
                        </div>
                        <div id="bill-no-items" class="text-center text-gray-500 py-8">
                            <i class="fas fa-shopping-cart text-4xl text-gray-400"></i>
                            <p class="mt-2">No items added to the bill yet.</p>
                        </div>

                        <!-- Bill Summary -->
                        <div class="mt-6 flex justify-end items-center">
                            <div class="text-2xl font-bold text-gray-900">
                                Total: <span id="billing-total-display">LKR 0.00</span>
                            </div>
                            <button id="submit-sale-btn" class="ml-4 px-6 py-3 bg-green-600 text-white rounded-md font-medium hover:bg-green-700 disabled:bg-gray-400" disabled>
                                Submit Sale
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Overlay for mobile menu -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Product Quantity Modal -->
    <div id="quantity-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-sm p-6 transform -translate-y-10">
            <h2 class="text-2xl font-bold mb-4">Add Item Quantity</h2>
            <div id="modal-product-name" class="text-lg font-semibold mb-3"></div>
            
            <div id="modal-product-details" class="p-3 bg-gray-50 rounded-md text-sm text-gray-700 mb-4">
                <div>Unit Price: <strong id="modal-price-display">LKR 0.00</strong></div>
                <div>Stock at Branch: <strong id="modal-stock-display">0</strong></div>
            </div>

            <form id="quantity-form">
                <input type="hidden" id="modal-product-id">
                <input type="hidden" id="modal-product-price">
                <input type="hidden" id="modal-product-stock">

                <div class="mb-4">
                    <label for="modal-quantity-input" class="block text-sm font-medium text-gray-700">Quantity to Add *</label>
                    <input type="number" id="modal-quantity-input" name="quantity" min="1" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                    <p id="modal-qty-error" class="text-xs text-red-500 mt-1 hidden">Quantity exceeds available stock.</p>
                </div>
                
                <div id="modal-error" class="hidden text-red-600 text-sm mt-4"></div>

                <div class="mt-6 flex justify-end">
                    <button type="button" id="cancel-quantity-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="add-item-to-bill-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">Add to Bill</button>
                </div>
            </form>
        </div>
    </div>

<!-- This page's specific JS file -->
<script src="assets/js/billing.js"></script>

<?php
// 5. Include the footer (which includes main.js)
include 'admin/_footer.php';
?>