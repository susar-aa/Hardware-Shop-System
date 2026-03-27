<?php
session_start();
$page_title = 'Dashboard';
$active_page = 'dashboard';

include 'admin/_header.php';
include 'admin/_sidebar.php';

$user_role = $_SESSION['role'] ?? 'staff';
?>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen bg-gray-100">
        
        <?php include 'admin/_topbar.php'; ?>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto" id="dashboard-container" data-role="<?php echo $user_role; ?>">
            
            <!-- Header & Filter -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-sm text-gray-500">Overview of your inventory and sales performance.</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Branch Filter (Admin Only) -->
                    <div id="dashboard-branch-wrapper" class="hidden">
                        <select id="dashboard-branch-select" class="border border-gray-300 rounded-md p-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Branches</option>
                            <!-- Loaded by JS -->
                        </select>
                    </div>

                    <a href="billing.php" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 shadow-sm text-sm">
                        <i class="fas fa-plus mr-2"></i>New Sale
                    </a>
                </div>
            </div>

            <!-- ROLE-BASED DASHBOARD CONTENT -->
            <?php if ($user_role === 'admin'): ?>
                <!-- ADMIN VIEW -->

                <!-- 1. Stats Cards Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Today -->
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">Today's Sales</p>
                                <h3 class="text-xl font-bold text-gray-900 mt-1" id="card-today">LKR 0.00</h3>
                            </div>
                            <div class="p-2 bg-blue-50 rounded-md text-blue-600">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Yesterday -->
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-indigo-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">Yesterday</p>
                                <h3 class="text-xl font-bold text-gray-900 mt-1" id="card-yesterday">LKR 0.00</h3>
                            </div>
                            <div class="p-2 bg-indigo-50 rounded-md text-indigo-600">
                                <i class="fas fa-history"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Weekly -->
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">This Week</p>
                                <h3 class="text-xl font-bold text-gray-900 mt-1" id="card-week">LKR 0.00</h3>
                            </div>
                            <div class="p-2 bg-green-50 rounded-md text-green-600">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Monthly -->
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">This Month</p>
                                <h3 class="text-xl font-bold text-gray-900 mt-1" id="card-month">LKR 0.00</h3>
                            </div>
                            <div class="p-2 bg-purple-50 rounded-md text-purple-600">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    
                    <!-- Left Column (Charts) -->
                    <div class="lg:col-span-2 flex flex-col gap-6">
                        <!-- Sales Trend Chart -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Sales Trend (Last 7 Days)</h3>
                            <div class="relative h-64 w-full">
                                <canvas id="dashboardSalesChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Grid 2-col inside Left Column -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Payment Methods Chart -->
                            <div class="bg-white rounded-lg shadow-md p-6">
                                <h3 class="text-md font-bold text-gray-900 mb-4">Payment Methods (30 Days)</h3>
                                <div class="relative h-48 w-full flex justify-center">
                                    <canvas id="paymentMethodsChart"></canvas>
                                </div>
                            </div>
                            <!-- Recent Transactions List -->
                            <div class="bg-white rounded-lg shadow-md flex-1 p-4">
                                <h3 class="text-md font-bold text-gray-900 mb-3">Recent Sales</h3>
                                <div class="overflow-y-auto max-h-48">
                                    <table class="min-w-full">
                                        <tbody id="recent-sales-body" class="text-sm text-gray-600">
                                            <tr><td class="py-2 text-center text-gray-400">Loading...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 text-center">
                                    <a href="sales.php" class="text-xs font-medium text-blue-600 hover:text-blue-800">View All Transactions</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (Alerts & Reminders) -->
                    <div class="lg:col-span-1 flex flex-col gap-6">
                        <!-- Cheque Reminders Panel -->
                        <div class="bg-white rounded-lg shadow-md p-5 border-t-4 border-yellow-500 flex-1 flex flex-col">
                            <div class="flex items-center mb-4">
                                <div class="p-2 bg-yellow-100 rounded-md text-yellow-600 mr-3">
                                    <i class="fas fa-money-check-alt"></i>
                                </div>
                                <h3 class="text-md font-bold text-gray-900">Cheque Banking Reminders</h3>
                            </div>
                            
                            <div class="flex-1 overflow-y-auto max-h-[400px] pr-2" id="cheque-reminders-list">
                                <div class="text-center py-8 text-gray-400 text-sm">Loading...</div>
                            </div>
                            
                            <div class="mt-4 pt-3 border-t border-gray-100 text-center">
                                <a href="cheques.php" class="text-xs font-medium text-yellow-600 hover:text-yellow-800">Manage All Cheques</a>
                            </div>
                        </div>

                        <!-- Low Stock Alert -->
                        <div class="bg-red-50 rounded-lg border border-red-100 p-6">
                            <div class="flex items-center">
                                <div class="p-3 bg-red-100 rounded-full text-red-600 mr-4">
                                    <i class="fas fa-exclamation-triangle text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-red-600 font-medium">Low Stock Alerts</p>
                                    <h3 class="text-2xl font-bold text-red-900" id="card-low-stock">0</h3>
                                    <p class="text-xs text-red-500 mt-1">Products below reorder level</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="stock_adjust.php" class="text-sm font-medium text-red-700 hover:text-red-800">
                                    View Inventory &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- STAFF VIEW (Simplified & Enlarged) -->
                
                <div class="max-w-4xl mx-auto mt-8">
                    <!-- Daily Totals -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                        <!-- Today -->
                        <div class="bg-white rounded-2xl shadow-md p-8 border-t-8 border-blue-500 hover:shadow-lg transition-shadow">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-lg font-medium text-gray-500 uppercase tracking-wide">Today's Sales</p>
                                    <h3 class="text-5xl font-extrabold text-blue-600 mt-3" id="staff-today">LKR 0.00</h3>
                                </div>
                                <div class="p-4 bg-blue-50 rounded-full text-blue-500">
                                    <i class="fas fa-cash-register text-5xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Yesterday -->
                        <div class="bg-white rounded-2xl shadow-md p-8 border-t-8 border-gray-400 hover:shadow-lg transition-shadow">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-lg font-medium text-gray-400 uppercase tracking-wide">Yesterday</p>
                                    <h3 class="text-4xl font-bold text-gray-600 mt-3" id="staff-yesterday">LKR 0.00</h3>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-full text-gray-400">
                                    <i class="fas fa-history text-4xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <h2 class="text-xl font-bold text-gray-800 mb-6 text-center">Quick Actions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <a href="billing.php" class="flex flex-col items-center justify-center p-8 bg-white rounded-xl shadow border border-gray-100 hover:bg-blue-50 hover:border-blue-200 transition-colors group">
                            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors mb-4">
                                <i class="fas fa-file-invoice text-3xl"></i>
                            </div>
                            <span class="text-lg font-bold text-gray-800">New Invoice</span>
                            <span class="text-sm text-gray-500 mt-1 text-center">Create a new sale transaction</span>
                        </a>
                        
                        <a href="products.php" class="flex flex-col items-center justify-center p-8 bg-white rounded-xl shadow border border-gray-100 hover:bg-green-50 hover:border-green-200 transition-colors group">
                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-green-600 group-hover:bg-green-600 group-hover:text-white transition-colors mb-4">
                                <i class="fas fa-boxes text-3xl"></i>
                            </div>
                            <span class="text-lg font-bold text-gray-800">Browse Products</span>
                            <span class="text-sm text-gray-500 mt-1 text-center">Check prices and availability</span>
                        </a>

                        <a href="customers.php" class="flex flex-col items-center justify-center p-8 bg-white rounded-xl shadow border border-gray-100 hover:bg-purple-50 hover:border-purple-200 transition-colors group">
                            <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors mb-4">
                                <i class="fas fa-users text-3xl"></i>
                            </div>
                            <span class="text-lg font-bold text-gray-800">Manage Customers</span>
                            <span class="text-sm text-gray-500 mt-1 text-center">View caller info or credit</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Developer Credits -->
            <div class="text-center py-6 border-t border-gray-200 mt-auto">
                <p class="text-sm text-gray-500 flex justify-center items-center gap-2">
                    Developed by <span class="font-semibold text-gray-700">Susara Senarathne</span>
                    <a href="https://www.instagram.com/susar.aa" target="_blank" class="text-pink-600 hover:text-pink-700 transition-colors">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                </p>
            </div>

        </main>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Global JS (for sidebar/session) -->
    <script src="assets/js/main.js"></script>
    <!-- Dashboard Specific JS -->
    <script src="assets/js/dashboard.js"></script>
</body>
</html>