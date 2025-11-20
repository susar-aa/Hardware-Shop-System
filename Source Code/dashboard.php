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

            <!-- 1. Stats Cards Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Today -->
                <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Today's Sales</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1" id="card-today">LKR 0.00</h3>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-md text-blue-600">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
                <!-- Yesterday -->
                <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-indigo-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Yesterday</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1" id="card-yesterday">LKR 0.00</h3>
                        </div>
                        <div class="p-2 bg-indigo-50 rounded-md text-indigo-600">
                            <i class="fas fa-history"></i>
                        </div>
                    </div>
                </div>
                <!-- Weekly -->
                <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">This Week</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1" id="card-week">LKR 0.00</h3>
                        </div>
                        <div class="p-2 bg-green-50 rounded-md text-green-600">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                    </div>
                </div>
                <!-- Monthly -->
                <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-purple-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">This Month</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1" id="card-month">LKR 0.00</h3>
                        </div>
                        <div class="p-2 bg-purple-50 rounded-md text-purple-600">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Sales Trend Chart (2/3 width) -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Sales Trend (Last 7 Days)</h3>
                    <div class="relative h-72 w-full">
                        <canvas id="dashboardSalesChart"></canvas>
                    </div>
                </div>

                <!-- Alerts & Recent Activity (1/3 width) -->
                <div class="lg:col-span-1 flex flex-col gap-6">
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

                    <!-- Recent Transactions List -->
                    <div class="bg-white rounded-lg shadow-md flex-1 p-4">
                        <h3 class="text-md font-bold text-gray-900 mb-3">Recent Sales</h3>
                        <div class="overflow-y-auto max-h-64">
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