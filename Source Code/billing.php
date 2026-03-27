<?php
// 1. Define the variables for this specific page
$page_title = 'New Sale / Billing';
$active_page = 'billing'; 

include 'admin/_header.php';
include 'admin/_sidebar.php';
?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen">
        
        <?php include 'admin/_topbar.php'; ?>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto">
            
            <div id="billing-page-container">

                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">New Sale / Billing</h1>
                </div>

                <div id="billing-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-4"></div>
                <div id="billing-success" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md mb-4"></div>

                <div id="branch-info-section" class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 p-4 mb-6 rounded-md shadow-sm">
                    <div id="branch-display-static" class="text-lg font-semibold hidden">
                        Billing from: <span id="current-branch-name" class="font-bold text-gray-900">...</span>
                    </div>
                    
                    <div id="branch-selector-wrapper" class="hidden">
                        <label for="billing-branch-select" class="block text-sm font-medium text-gray-700 mb-1">Select Branch for Sale *</label>
                        <select id="billing-branch-select" class="w-full border border-gray-300 rounded-md p-2" required>
                            <option value="">Loading branches...</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- Left Column: Product Search -->
                    <div class="md:col-span-1 bg-white rounded-lg shadow-md p-6 h-fit">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Search & Add Item</h2>
                        
                        <div class="mb-4 relative">
                            <label for="product-search-input" class="block text-sm font-medium text-gray-700">Search Product by Name/Code</label>
                            <input type="text" id="product-search-input" placeholder="Type name or code..." class="mt-1 w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500" disabled autocomplete="off">
                            <div id="search-loader" class="absolute right-3 top-8 hidden">
                                <i class="fas fa-spinner fa-spin text-gray-500"></i>
                            </div>

                            <div id="search-results-list" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-64 overflow-y-auto">
                                <!-- Results injected here -->
                            </div>
                        </div>

                        <p id="search-status" class="text-sm text-red-600 hidden">Please select a branch first.</p>
                    </div>

                    <!-- Right Column: Current Bill -->
                    <div class="md:col-span-2 bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Current Bill</h2>
                        
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
                                <tbody id="billing-cart-body" class="bg-white divide-y divide-gray-200"></tbody>
                            </table>
                        </div>
                        <div id="bill-no-items" class="text-center text-gray-500 py-8">
                            <i class="fas fa-shopping-cart text-4xl text-gray-400"></i>
                            <p class="mt-2">No items added to the bill yet.</p>
                        </div>

                        <div class="mt-6 border-t pt-4">
                            <div id="selected-customer-display" class="hidden mb-4 flex justify-between items-center bg-blue-50 p-3 rounded-md border border-blue-200">
                                <div>
                                    <span class="text-sm text-blue-800 font-bold">Customer:</span>
                                    <span id="cust-name-display" class="ml-2 font-medium">John Doe</span>
                                    <span id="cust-nic-display" class="ml-2 text-sm text-gray-500 hidden"></span>
                                </div>
                                <button id="remove-customer-btn" class="text-red-500 hover:text-red-700 text-xs font-bold">Remove</button>
                            </div>

                            <div class="flex flex-col md:flex-row justify-between items-center">
                                <div class="text-2xl font-bold text-gray-900 mb-4 md:mb-0">
                                    Total: <span id="billing-total-display">LKR 0.00</span>
                                </div>
                                
                                <div class="flex gap-3">
                                    <button id="cheque-sale-btn" class="px-6 py-3 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 disabled:bg-gray-400 transition-colors" disabled>
                                        <i class="fas fa-money-check mr-2"></i>Cheque Sale
                                    </button>
                                    <button id="credit-sale-btn" class="px-6 py-3 bg-orange-600 text-white rounded-md font-medium hover:bg-orange-700 disabled:bg-gray-400 transition-colors" disabled>
                                        <i class="fas fa-credit-card mr-2"></i>Credit Sale
                                    </button>
                                    <button id="submit-sale-btn" class="px-6 py-3 bg-green-600 text-white rounded-md font-medium hover:bg-green-700 disabled:bg-gray-400 transition-colors" disabled>
                                        <i class="fas fa-money-bill-wave mr-2"></i>Cash Sale
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Product Quantity & Price Modal -->
    <div id="quantity-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-sm p-6 transform -translate-y-10">
            <h2 class="text-2xl font-bold mb-4">Add Item Details</h2>
            <div id="modal-product-name" class="text-lg font-semibold mb-3"></div>
            <div id="modal-product-details" class="p-3 bg-gray-50 rounded-md text-sm text-gray-700 mb-4">
                <div>Stock at Branch: <strong id="modal-stock-display">0</strong></div>
            </div>
            <form id="quantity-form">
                <input type="hidden" id="modal-product-id">
                <input type="hidden" id="modal-product-stock">
                
                <!-- Unit Price Field (NEW) -->
                <div class="mb-4">
                    <label for="modal-price-input" class="block text-sm font-medium text-gray-700">Unit Price (LKR) *</label>
                    <input type="number" id="modal-price-input" name="price" step="0.01" class="w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500" required>
                </div>

                <div class="mb-4">
                    <label for="modal-quantity-input" class="block text-sm font-medium text-gray-700">Quantity to Add *</label>
                    <input type="number" id="modal-quantity-input" name="quantity" min="1" class="w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500" required>
                    <p id="modal-qty-error" class="text-xs text-red-500 mt-1 hidden"></p>
                </div>

                <div class="mb-4 p-2 bg-blue-50 rounded border border-blue-100 flex justify-between items-center">
                    <span class="text-sm text-blue-800">Row Total:</span>
                    <span id="modal-refund-calc" class="font-bold text-blue-900 text-lg">LKR 0.00</span>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" id="cancel-quantity-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="add-item-to-bill-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">Add to Bill</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Customer Selection Modal -->
    <div id="customer-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-lg p-6 transform -translate-y-10">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Select Credit Customer</h2>
                <button type="button" id="close-cust-modal" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>
            <div class="mb-4 border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button id="tab-select-cust" class="tab-btn w-1/2 py-2 px-4 text-center border-b-2 font-medium text-sm text-blue-600 border-blue-500">Search Existing</button>
                    <button id="tab-add-cust" class="tab-btn w-1/2 py-2 px-4 text-center border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">Add New Customer</button>
                </nav>
            </div>
            <div id="view-select-cust">
                <input type="text" id="customer-search-input" class="w-full border border-gray-300 rounded-md p-2 mb-2 focus:ring-blue-500" placeholder="Search by Name or Phone..." autocomplete="off">
                <div id="customer-list" class="border border-gray-200 rounded-md max-h-48 overflow-y-auto bg-gray-50">
                    <div class="p-3 text-center text-gray-400 text-sm">Type to search...</div>
                </div>
            </div>
            <div id="view-add-cust" class="hidden">
                <form id="new-customer-form">
                    <div class="mb-3"><label class="block text-sm font-medium text-gray-700">Name *</label><input type="text" name="name" class="w-full border border-gray-300 rounded-md p-2" required></div>
                    <div class="mb-3"><label class="block text-sm font-medium text-gray-700">NIC (Optional)</label><input type="text" name="nic" class="w-full border border-gray-300 rounded-md p-2" placeholder="e.g. 199012345678"></div>
                    <div class="mb-3"><label class="block text-sm font-medium text-gray-700">Phone</label><input type="text" name="phone" class="w-full border border-gray-300 rounded-md p-2"></div>
                    <div class="mb-3"><label class="block text-sm font-medium text-gray-700">Address</label><textarea name="address" rows="2" class="w-full border border-gray-300 rounded-md p-2"></textarea></div>
                    <button type="submit" class="w-full py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium">Save & Select</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Cheque Details Modal -->
    <div id="cheque-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-40 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-sm p-6 transform -translate-y-10">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Cheque Details</h2>
                <button type="button" id="close-cheque-modal" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>
            <form id="cheque-details-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Bank Name *</label>
                    <input type="text" id="cheque-bank" name="cheque_bank" class="w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500" placeholder="e.g. BOC, Commercial" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Cheque Date *</label>
                    <input type="date" id="cheque-date" name="cheque_date" class="w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Cheque Number *</label>
                    <input type="text" id="cheque-number" name="cheque_number" class="w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500" placeholder="e.g. 123456" required>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" id="cancel-cheque-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

<script src="assets/js/billing.js"></script>
<?php include 'admin/_footer.php'; ?>