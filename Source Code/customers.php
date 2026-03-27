<?php
session_start();
// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$page_title = 'Customer Management';
$active_page = 'customers';
include 'admin/_header.php';
include 'admin/_sidebar.php';
?>

    <div class="flex-1 flex flex-col h-screen bg-gray-50">
        <?php include 'admin/_topbar.php'; ?>

        <main class="flex-1 p-6 overflow-auto">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Customer Management</h1>
                    <p class="text-sm text-gray-500 mt-1">View, edit, or remove registered customers from the system.</p>
                </div>
                <button onclick="openAddCustomerModal()" class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg shadow hover:bg-blue-700 transition flex items-center">
                    <i class="fas fa-user-plus mr-2"></i> Add New Customer
                </button>
            </div>

            <!-- Customers Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex flex-col md:flex-row justify-between gap-4">
                    <div class="w-full max-w-md relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" id="cust-search" class="w-full pl-10 p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Search by name, phone, or NIC...">
                    </div>
                </div>

                <div id="table-loader" class="flex justify-center py-12">
                    <div class="text-center">
                        <i class="fas fa-circle-notch fa-spin fa-3x text-blue-500"></i>
                        <p class="mt-4 text-gray-600 font-medium">Loading customers...</p>
                    </div>
                </div>

                <div id="table-container" class="hidden overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Customer Name</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">NIC</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Phone / Contact</th>
                                <th class="px-6 py-3 text-right font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="customers-tbody" class="bg-white divide-y divide-gray-200">
                            <!-- JS Populated -->
                        </tbody>
                    </table>
                </div>

                <div id="empty-state" class="hidden p-12 text-center text-gray-500">
                    <i class="fas fa-users-slash fa-3x mb-4 text-gray-300"></i>
                    <p class="text-lg">No customers found.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Customer Modal -->
    <div id="customer-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden modal transition-opacity duration-300 opacity-0">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 modal-content transform scale-95 opacity-0 transition-transform duration-300">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-xl">
                <h3 class="text-lg font-bold text-gray-900" id="customer-modal-title">Add Customer</h3>
                <button type="button" onclick="closeCustomerModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="customer-form">
                <div class="p-6 space-y-4">
                    <input type="hidden" id="modal-cust-id">
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" id="modal-cust-name" required class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">NIC (Optional)</label>
                            <input type="text" id="modal-cust-nic" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Phone (Optional)</label>
                            <input type="text" id="modal-cust-phone" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Address (Optional)</label>
                        <textarea id="modal-cust-address" rows="2" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 bg-gray-50 rounded-b-xl">
                    <button type="button" onclick="closeCustomerModal()" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 font-medium transition">Cancel</button>
                    <button type="submit" id="save-cust-btn" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition flex items-center">
                        <i class="fas fa-save mr-2"></i> Save Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

<script src="assets/js/customers.js"></script>
<?php include 'admin/_footer.php'; ?>
