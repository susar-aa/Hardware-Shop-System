<?php
// 1. Define the variables for this specific page
$page_title = 'Stock Adjustments';
$active_page = 'stock_adjust'; // This will highlight the link in the sidebar

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
            
            <!-- We'll use this ID in main.js to detect this page -->
            <div id="stock-adjust-tabs">
                <!-- Tab Headers -->
                <div class="mb-6 border-b border-gray-200">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <button id="tab-stock-in" class="tab-btn whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            <i class="fas fa-plus-circle mr-2"></i> Stock In (Add)
                        </button>
                        <button id="tab-stock-out" class="tab-btn whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            <i class="fas fa-minus-circle mr-2"></i> Stock Out (Remove)
                        </button>
                        <!-- Stock Levels is the default -->
                        <button id="tab-stock-levels" class="tab-btn whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm text-blue-600 border-blue-500" aria-current="page">
                            <i class="fas fa-warehouse mr-2"></i> Stock Levels
                        </button>
                    </nav>
                </div>

                <!-- Tab Panels -->
                <div>
                    <!-- Stock In Panel -->
                    <div id="panel-stock-in" class="tab-panel hidden">
                        <form id="stock-in-form" class="bg-white rounded-lg shadow-md p-6 max-w-lg mx-auto">
                            <h2 class="text-xl font-bold text-gray-900 mb-6">Record Stock In</h2>
                            
                            <div id="stock-in-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-4"></div>
                            <div id="stock-in-success" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md mb-4"></div>

                            <!-- UPDATED: Product Search -->
                            <div class="mb-4 relative">
                                <label for="stock-in-product-search" class="block text-sm font-medium text-gray-700">Product *</label>
                                <input type="text" id="stock-in-product-search" placeholder="Type to search product..." class="mt-1 w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500" autocomplete="off">
                                <input type="hidden" id="stock-in-product-id" name="product_id" required>
                                <!-- Search Results Dropdown -->
                                <div id="stock-in-results" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                            </div>

                            <!-- Branch Dropdown -->
                            <div class="mb-4">
                                <label for="stock-in-branch" class="block text-sm font-medium text-gray-700">Branch *</label>
                                <select id="stock-in-branch" name="branch_id" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                                    <option value="">Loading branches...</option>
                                </select>
                            </div>
                            
                            <div id="stock-in-current-stock" class="hidden text-sm text-gray-600 mt-2 p-3 bg-gray-50 rounded-md">
                                Current Stock: <strong id="stock-in-current-value">...</strong>
                            </div>

                            <!-- Quantity -->
                            <div class="mb-4 mt-4">
                                <label for="stock-in-quantity" class="block text-sm font-medium text-gray-700">Quantity *</label>
                                <input type="number" id="stock-in-quantity" name="quantity" min="1" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="stock-in-notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                <textarea id="stock-in-notes" name="notes" rows="3" class="mt-1 w-full border border-gray-300 rounded-md p-2" placeholder="e.g., New shipment from supplier X"></textarea>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" id="stock-in-submit" class="px-6 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 disabled:bg-gray-400">
                                    Add Stock
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Stock Out Panel -->
                    <div id="panel-stock-out" class="tab-panel hidden">
                        <form id="stock-out-form" class="bg-white rounded-lg shadow-md p-6 max-w-lg mx-auto">
                            <h2 class="text-xl font-bold text-gray-900 mb-6">Record Stock Out</h2>

                            <div id="stock-out-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-4"></div>
                            <div id="stock-out-success" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md mb-4"></div>
                            
                            <!-- UPDATED: Product Search -->
                            <div class="mb-4 relative">
                                <label for="stock-out-product-search" class="block text-sm font-medium text-gray-700">Product *</label>
                                <input type="text" id="stock-out-product-search" placeholder="Type to search product..." class="mt-1 w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500" autocomplete="off">
                                <input type="hidden" id="stock-out-product-id" name="product_id" required>
                                <!-- Search Results Dropdown -->
                                <div id="stock-out-results" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                            </div>

                            <!-- Branch Dropdown -->
                            <div class="mb-4">
                                <label for="stock-out-branch" class="block text-sm font-medium text-gray-700">Branch *</label>
                                <select id="stock-out-branch" name="branch_id" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                                    <option value="">Loading branches...</option>
                                </select>
                            </div>

                            <div id="stock-out-current-stock" class="hidden text-sm text-gray-600 mt-2 p-3 bg-gray-50 rounded-md">
                                Current Stock: <strong id="stock-out-current-value">...</strong>
                            </div>

                            <!-- Quantity -->
                            <div class="mb-4 mt-4">
                                <label for="stock-out-quantity" class="block text-sm font-medium text-gray-700">Quantity *</label>
                                <input type="number" id="stock-out-quantity" name="quantity" min="1" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                            </div>

                            <div class="mb-4">
                                <label for="stock-out-notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                <textarea id="stock-out-notes" name="notes" rows="3" class="mt-1 w-full border border-gray-300 rounded-md p-2" placeholder="e.g., Sold to customer Y, Internal use, Damaged goods"></textarea>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" id="stock-out-submit" class="px-6 py-2 bg-red-600 text-white rounded-md font-medium hover:bg-red-700 disabled:bg-gray-400">
                                    Remove Stock
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Stock Levels Panel -->
                    <div id="panel-stock-levels" class="tab-panel">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <!-- Branch Filter -->
                            <div class="mb-4 max-w-sm">
                                <label for="stock-level-branch-filter" class="block text-sm font-medium text-gray-700">Select Branch</label>
                                <select id="stock-level-branch-filter" name="branch_id" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                                    <option value="">Loading branches...</option>
                                </select>
                            </div>

                            <!-- Stock Table -->
                            <div id="stock-level-loader" class="flex justify-center items-center py-8 hidden">
                                <div class="loader"></div>
                            </div>
                            <div id="stock-level-table-container" class="overflow-x-auto hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Code</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reorder Level</th>
                                        </tr>
                                    </thead>
                                    <tbody id="stock-level-table-body" class="bg-white divide-y divide-gray-200">
                                        <!-- Stock rows injected by JS -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="stock-level-no-items" class="hidden text-center text-gray-500 py-8">
                                <i class="fas fa-box-open text-4xl text-gray-400"></i>
                                <p class="mt-2">No stock found for this branch.</p>
                            </div>
                            <div id="stock-level-error" class="hidden text-center text-red-500 py-8"></div>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>
    
    <!-- Overlay for mobile menu -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

<?php
// 5. Include the footer
include 'admin/_footer.php';
?>