<?php
// 1. Start Session & Define Page Variables
session_start();
$page_title = 'Reports & Analytics';
$active_page = 'reports';

include 'admin/_header.php';
include 'admin/_sidebar.php';

$user_role = $_SESSION['role'] ?? 'staff';
?>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen bg-gray-100">
        
        <?php include 'admin/_topbar.php'; ?>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto" id="reports-container" data-role="<?php echo $user_role; ?>">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Reports & Analytics</h1>
                <div class="mt-4 md:mt-0 flex gap-2">
                    <button id="export-btn" class="flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 shadow-sm transition-colors">
                        <i class="fas fa-file-csv mr-2"></i> Export Data
                    </button>
                </div>
            </div>

            <!-- Report Type Tabs -->
            <div class="bg-white rounded-t-lg shadow-sm border-b border-gray-200 mb-0">
                <nav class="flex -mb-px">
                    <button id="tab-sales" class="tab-btn w-1/3 py-4 px-6 text-center border-b-2 font-medium text-sm text-blue-600 border-blue-500">
                        <i class="fas fa-chart-line mr-2"></i> Sales
                    </button>
                    <button id="tab-inventory" class="tab-btn w-1/3 py-4 px-6 text-center border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fas fa-boxes mr-2"></i> Inventory
                    </button>
                    <button id="tab-analytics" class="tab-btn w-1/3 py-4 px-6 text-center border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fas fa-chart-pie mr-2"></i> P&L Analytics
                    </button>
                </nav>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white rounded-b-lg shadow-md p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    <div id="filter-period-wrapper">
                        <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Report Mode</label>
                        <select id="report-period" class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="daily">Daily</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>

                    <div id="dynamic-date-container">
                        <label id="dynamic-date-label" class="block text-xs font-medium text-gray-500 uppercase mb-1">Date</label>
                        <input type="date" id="report-date-input" class="w-full border border-gray-300 rounded-md p-2 text-sm">
                    </div>

                    <div id="report-branch-wrapper" class="hidden">
                        <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Branch</label>
                        <select id="report-branch" class="w-full border border-gray-300 rounded-md p-2 text-sm">
                            <option value="">All Branches</option>
                        </select>
                    </div>

                    <div>
                        <button id="generate-report-btn" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">
                            Generate
                        </button>
                    </div>
                </div>
            </div>

            <!-- ANALYTICS VIEW (Hidden by default) -->
            <div id="view-analytics" class="hidden">
                <!-- P&L Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
                        <div class="text-gray-500 text-xs font-bold uppercase">Total Revenue</div>
                        <div class="text-xl font-bold text-gray-900 mt-1" id="pl-revenue">LKR 0.00</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-orange-500">
                        <div class="text-gray-500 text-xs font-bold uppercase">COGS (Cost)</div>
                        <div class="text-xl font-bold text-gray-900 mt-1" id="pl-cogs">LKR 0.00</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-red-500">
                        <div class="text-gray-500 text-xs font-bold uppercase">Expenses</div>
                        <div class="text-xl font-bold text-gray-900 mt-1" id="pl-expenses">LKR 0.00</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-600">
                        <div class="text-gray-500 text-xs font-bold uppercase">Net Profit</div>
                        <div class="text-xl font-bold text-green-700 mt-1" id="pl-profit">LKR 0.00</div>
                    </div>
                </div>

                <!-- Chart Section -->
                <div class="bg-white p-4 rounded-lg shadow-md mb-6 h-80">
                    <canvas id="analyticsChart"></canvas>
                </div>
            </div>

            <!-- STANDARD REPORT VIEW (Sales/Inventory) -->
            <div id="view-standard">
                <!-- Summary Cards -->
                <div id="summary-cards" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
                        <div class="text-gray-500 text-sm font-medium uppercase" id="label-metric-1">Total Revenue</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1" id="summary-metric-1">0</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">
                        <div class="text-gray-500 text-sm font-medium uppercase" id="label-metric-2">Total Items</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1" id="summary-metric-2">0</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-purple-500">
                        <div class="text-gray-500 text-sm font-medium uppercase" id="label-metric-3">Count</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1" id="summary-metric-3">0</div>
                    </div>
                </div>

                <!-- Report Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="reports-table">
                            <thead class="bg-gray-50">
                                <tr id="table-headers"></tr>
                            </thead>
                            <tbody id="table-body" class="bg-white divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                    <div id="report-loader" class="hidden py-12 flex justify-center"><div class="loader"></div></div>
                    <div id="report-empty" class="hidden py-12 text-center text-gray-500">
                        <i class="fas fa-chart-bar text-4xl text-gray-300 mb-3"></i>
                        <p>No data found.</p>
                    </div>
                </div>
            </div>

        </main>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

<script src="assets/js/reports.js"></script>
<?php include 'admin/_footer.php'; ?>