<?php
session_start();
$page_title = 'Cheque Management';
$active_page = 'cheques';

include 'admin/_header.php';
include 'admin/_sidebar.php';
?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <?php include 'admin/_topbar.php'; ?>

        <!-- Page Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Cheque Management</h1>
                
                <div class="mt-4 md:mt-0 flex gap-4 items-center bg-white p-2 rounded-lg shadow-sm">
                    <label for="cheque-month-filter" class="text-sm font-medium text-gray-700 whitespace-nowrap"><i class="fas fa-calendar-alt mr-2 text-blue-500"></i>Filter Month:</label>
                    <input type="month" id="cheque-month-filter" class="p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Metrics Cards -->
            <?php if ($user_role === 'admin'): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Cheque Payments -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-semibold uppercase tracking-wide">Total Payments</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-1" id="metric-total">LKR 0.00</h3>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-money-check fa-2x text-blue-600"></i>
                    </div>
                </div>

                <!-- Cheques to Bank -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-semibold uppercase tracking-wide">Pending / To Bank</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-1" id="metric-to-bank">LKR 0.00</h3>
                    </div>
                    <div class="p-3 bg-orange-100 rounded-full">
                        <i class="fas fa-university fa-2x text-orange-600"></i>
                    </div>
                </div>

                <!-- Nearest Banking Date -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-semibold uppercase tracking-wide">Nearest Bank Date</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-1" id="metric-nearest-date">-</h3>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-calendar-check fa-2x text-green-600"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Data Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800">Cheque History</h2>
                </div>
                
                <div id="table-loader" class="p-8 text-center hidden">
                    <i class="fas fa-circle-notch fa-spin fa-3x text-blue-500"></i>
                    <p class="mt-4 text-gray-600 font-medium">Loading cheque data...</p>
                </div>
                
                <div id="table-container" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sale Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Bank Name</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cheque Num</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cheque Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cheques-tbody" class="bg-white divide-y divide-gray-200">
                            <!-- Dynamic Rows -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div id="empty-state" class="hidden p-12 text-center text-gray-500">
                    <i class="fas fa-search fa-3x mb-4 text-gray-300"></i>
                    <p class="text-lg">No cheque records found for this month.</p>
                </div>

            </div>
            
        </main>
    </div>

    <!-- Edit Cheque Modal -->
    <div id="edit-cheque-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden modal">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 modal-content transform scale-95 opacity-0">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-900">Edit Cheque</h3>
                <button type="button" id="close-edit-cheque" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="edit-cheque-form">
                <div class="p-6 space-y-4">
                    <input type="hidden" id="edit-cheque-id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                        <input type="text" id="edit-cheque-bank" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 uppercase" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cheque Number</label>
                        <input type="text" id="edit-cheque-number" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cheque Date</label>
                        <input type="date" id="edit-cheque-date" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                        <input type="number" step="0.01" id="edit-cheque-amount" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 bg-gray-50 rounded-b-xl">
                    <button type="button" id="cancel-edit-cheque" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded shadow-sm hover:bg-gray-50 font-medium">Cancel</button>
                    <button type="submit" id="save-edit-cheque" class="px-4 py-2 bg-blue-600 text-white rounded shadow-sm hover:bg-blue-700 font-medium">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

<script src="assets/js/cheques.js"></script>
<?php include 'admin/_footer.php'; ?>
