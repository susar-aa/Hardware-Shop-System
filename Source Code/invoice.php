<?php
session_start();
$page_title = 'Sale Invoice';
$active_page = 'sales'; // Highlight Sales in sidebar

include 'admin/_header.php';
include 'admin/_sidebar.php';

$user_role = $_SESSION['role'] ?? 'staff';
?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen bg-gray-100">
        
        <?php include 'admin/_topbar.php'; ?>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto" id="invoice-page-container" data-role="<?php echo $user_role; ?>">
            
            <!-- Back Button & Controls -->
            <div class="flex justify-between items-center mb-6 max-w-4xl mx-auto w-full">
                <a href="sales.php" class="text-gray-600 hover:text-gray-900 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Sales
                </a>
                <div class="flex gap-2">
                    <button onclick="window.print()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                </div>
            </div>

            <!-- Invoice Paper -->
            <div class="bg-white shadow-lg rounded-lg max-w-4xl mx-auto p-8" id="invoice-content">
                
                <!-- Loading State -->
                <div id="invoice-loader" class="flex justify-center py-12">
                    <div class="loader"></div>
                </div>
                
                <div id="invoice-data" class="hidden">
                    <!-- Header -->
                    <div class="flex justify-between border-b pb-6 mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 mb-2">INVOICE</h1>
                            <div class="text-sm text-gray-500">
                                <p>ID: <span id="inv-id" class="font-mono text-gray-800">#</span></p>
                                <p>Date: <span id="inv-date"></span></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h2 class="text-xl font-bold text-gray-700">DMA ELECTRICALS</h2>
                            <p class="text-sm text-gray-500" id="inv-branch">Branch</p>
                            <p class="text-sm text-gray-500">Sold by: <span id="inv-user"></span></p>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <table class="w-full mb-8">
                        <thead>
                            <tr class="text-left border-b border-gray-200">
                                <th class="pb-3 font-semibold text-gray-600">Item</th>
                                <th class="pb-3 font-semibold text-gray-600 text-center">Price</th>
                                <th class="pb-3 font-semibold text-gray-600 text-center">Qty</th>
                                <th class="pb-3 font-semibold text-gray-600 text-right">Total</th>
                                <th class="pb-3 font-semibold text-gray-600 text-right w-24 print:hidden">Action</th>
                            </tr>
                        </thead>
                        <tbody id="inv-items-body" class="text-gray-700">
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>

                    <!-- Totals -->
                    <div class="flex justify-end">
                        <div class="w-1/2 md:w-1/3 border-t pt-4">
                            <div class="flex justify-between mb-2">
                                <span class="font-semibold">Total Amount</span>
                                <span class="font-bold text-xl" id="inv-total">LKR 0.00</span>
                            </div>
                            <div id="inv-status-wrapper" class="mt-4 text-right">
                                <!-- Status badges injected here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="border-t mt-8 pt-8 text-center text-xs text-gray-400">
                        Thank you for your business!
                    </div>
                </div>

                <div id="invoice-error" class="hidden text-center text-red-500 py-12"></div>
            </div>

        </main>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Return Item Modal -->
    <div id="return-item-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 class="text-xl font-bold mb-2">Return Item</h2>
            <p class="text-sm text-gray-500 mb-4">This will update the invoice total and restore stock.</p>
            
            <div class="bg-gray-50 p-3 rounded-md mb-4">
                <div class="font-medium" id="modal-prod-name">Product Name</div>
                <div class="text-sm text-gray-500">Purchased: <span id="modal-max-qty">0</span></div>
                <div class="text-sm text-gray-500">Unit Price: <span id="modal-unit-price">0</span></div>
            </div>

            <form id="return-item-form">
                <input type="hidden" id="modal-prod-id">
                <input type="hidden" id="modal-sale-id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity to Return</label>
                    <input type="number" id="modal-return-qty" class="w-full border border-gray-300 rounded-md p-2" min="1" required>
                    <p class="text-xs text-blue-600 mt-1">Refund Amount: <span id="modal-refund-calc" class="font-bold">LKR 0.00</span></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <textarea id="modal-return-reason" rows="2" class="w-full border border-gray-300 rounded-md p-2" required placeholder="e.g. Defective, Wrong item"></textarea>
                </div>

                <div id="modal-error" class="hidden text-red-600 text-sm mb-4"></div>

                <div class="flex justify-end gap-2">
                    <button type="button" id="close-modal-btn" class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="confirm-return-btn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>

<script src="assets/js/invoice.js"></script>
<?php include 'admin/_footer.php'; ?>