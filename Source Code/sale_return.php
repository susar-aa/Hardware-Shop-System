<?php
session_start();
$page_title = 'Process Sale Return';
$active_page = 'sale_return';

include 'admin/_header.php';
include 'admin/_sidebar.php';

$user_role = $_SESSION['role'] ?? 'staff';
?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen bg-gray-100">
        
        <?php include 'admin/_topbar.php'; ?>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto">
            
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Process Sale Return</h1>
                </div>

                <!-- Search Box -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <label for="sale-id-input" class="block text-sm font-medium text-gray-700 mb-2">Enter Sale ID / Invoice Number</label>
                    <div class="flex gap-4">
                        <input type="number" id="sale-id-input" class="flex-1 border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. 15">
                        <button id="search-sale-btn" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Search Sale
                        </button>
                    </div>
                    <div id="search-error" class="hidden mt-3 text-red-600 text-sm"></div>
                </div>

                <!-- Sale Details & Return Form (Hidden initially) -->
                <div id="return-section" class="hidden bg-white rounded-lg shadow-md p-6">
                    
                    <!-- Header Info -->
                    <div class="flex justify-between border-b border-gray-200 pb-4 mb-4">
                        <div>
                            <div class="text-sm text-gray-500">Sale ID</div>
                            <div class="text-lg font-bold text-gray-900" id="display-sale-id">-</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Date</div>
                            <div class="text-lg font-bold text-gray-900" id="display-sale-date">-</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Total Paid</div>
                            <div class="text-lg font-bold text-green-600" id="display-sale-total">-</div>
                        </div>
                    </div>
                    
                    <div class="mb-2 text-sm text-gray-500">Branch: <span id="display-branch-name" class="font-medium text-gray-800"></span></div>

                    <!-- Return Items Table -->
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sold Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Return Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Refund</th>
                                </tr>
                            </thead>
                            <tbody id="return-items-body" class="bg-white divide-y divide-gray-200">
                                <!-- Items injected by JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Return Summary & Submit -->
                    <div class="border-t border-gray-200 pt-4">
                        <div class="mb-4">
                            <label for="return-reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Return *</label>
                            <textarea id="return-reason" rows="2" class="w-full border border-gray-300 rounded-md p-2" placeholder="e.g. Item damaged, Customer changed mind..."></textarea>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <div class="text-xl">Total Refund: <span id="total-refund-display" class="font-bold text-red-600">LKR 0.00</span></div>
                            <button id="submit-return-btn" class="px-6 py-3 bg-red-600 text-white rounded-md hover:bg-red-700 font-medium disabled:bg-gray-400" disabled>
                                Process Return
                            </button>
                        </div>
                    </div>
                    
                    <div id="process-message" class="hidden mt-4 p-3 rounded-md text-center"></div>

                </div>

            </div>
        </main>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

<!-- Page Script -->
<script src="assets/js/sale_return.js"></script>

<?php include 'admin/_footer.php'; ?>