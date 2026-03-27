<?php
session_start();
$page_title = 'Credit Management';
$active_page = 'credits';

$user_role = $_SESSION['role'] ?? 'staff';

include 'admin/_header.php';
include 'admin/_sidebar.php';
?>

    <div class="flex-1 flex flex-col h-screen bg-gray-100">
        <?php include 'admin/_topbar.php'; ?>

        <main class="flex-1 p-6 overflow-auto" id="credits-page-container">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Credit Management (Debtors)</h1>
            </div>

            <!-- Debtors Table -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="w-full md:w-auto flex-1 max-w-md">
                        <input type="text" id="debtor-search" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Search customer...">
                    </div>
                    <div class="w-full md:w-auto">
                        <input type="month" id="credit-month-filter" class="w-full md:w-48 p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Metrics Grid -->
                <?php if ($user_role === 'admin'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6" id="credit-metrics-container">
                    <div class="bg-red-50 p-4 rounded-xl border border-red-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-red-600 uppercase tracking-wide">Total Outstanding Credit</p>
                            <h3 class="text-2xl font-extrabold text-gray-900 mt-1" id="metric-outstanding">LKR 0.00</h3>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-500 text-xl hidden sm:flex">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-xl border border-green-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-green-600 uppercase tracking-wide">Total Recovered Payments</p>
                            <h3 class="text-2xl font-extrabold text-gray-900 mt-1" id="metric-recovered">LKR 0.00</h3>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-500 text-xl hidden sm:flex">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tabs -->
                <div class="flex border-b border-gray-200 mb-6">
                    <button class="credit-tab-btn active-tab px-6 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600" data-tab="ongoing">
                        Ongoing Credits
                    </button>
                    <button class="credit-tab-btn px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="completed">
                        Completed Payments
                    </button>
                </div>

                <div id="debtor-loader" class="flex justify-center py-8"><div class="loader"></div></div>
                
                <div id="debtor-table-container" class="hidden overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Balance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="debtor-table-body" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Customer Detail Modal -->
    <div id="customer-detail-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-4xl p-6 transform -translate-y-10 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900" id="detail-cust-name">Customer Name</h2>
                    <div class="mt-2 text-sm text-gray-600 flex flex-col md:flex-row gap-2 md:gap-6">
                        <span><i class="fas fa-id-card mr-2 text-blue-500"></i><span id="detail-cust-nic">-</span></span>
                        <span><i class="fas fa-phone-alt mr-2 text-blue-500"></i><span id="detail-cust-phone">-</span></span>
                        <span><i class="fas fa-map-marker-alt mr-2 text-blue-500"></i><span id="detail-cust-address">-</span></span>
                    </div>
                </div>
                <button onclick="closeModal(document.getElementById('customer-detail-modal'))" class="text-gray-400 hover:text-gray-700 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-md border border-blue-100">
                    <div class="text-xs text-blue-600 font-bold uppercase">Total Debt</div>
                    <div class="text-xl font-bold" id="detail-total-debt">LKR 0.00</div>
                </div>
                <div class="bg-green-50 p-4 rounded-md border border-green-100">
                    <div class="text-xs text-green-600 font-bold uppercase">Total Paid</div>
                    <div class="text-xl font-bold" id="detail-total-paid">LKR 0.00</div>
                </div>
                <div class="bg-red-50 p-4 rounded-md border border-red-100">
                    <div class="text-xs text-red-600 font-bold uppercase">Current Balance</div>
                    <div class="text-xl font-bold" id="detail-balance">LKR 0.00</div>
                </div>
            </div>

            <div class="flex gap-4 mb-4">
                <button id="add-payment-btn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-medium">
                    <i class="fas fa-plus mr-2"></i>Record Payment
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Credit Bills</h3>
                    <div id="bill-history" class="text-sm bg-gray-50 rounded p-2 max-h-60 overflow-y-auto border"></div>
                </div>
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Payment History</h3>
                    <div id="payment-history" class="text-sm bg-gray-50 rounded p-2 max-h-60 overflow-y-auto border"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="payment-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-40 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 class="text-xl font-bold mb-4">Record Credit Payment</h2>
            <form id="payment-form">
                <input type="hidden" id="pay-cust-id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Amount (LKR) *</label>
                    <input type="number" id="pay-amount" step="0.01" class="w-full border p-2 rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="pay-notes" rows="2" class="w-full border p-2 rounded" placeholder="e.g. Paid by Cash, Bank transfer"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal(document.getElementById('payment-modal'))" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Submit Payment</button>
                </div>
            </form>
        </div>
    </div>

<script src="assets/js/credits.js"></script>
<?php include 'admin/_footer.php'; ?>