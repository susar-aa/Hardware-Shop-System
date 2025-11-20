<?php
// Set a default active page if one isn't provided
if (!isset($active_page)) {
    $active_page = '';
}

// Ensure we have the user role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_role = $_SESSION['role'] ?? 'staff';
?>
<!-- Sidebar -->
<aside class="sidebar w-64 bg-gray-800 text-white min-h-screen p-4 fixed md:relative transform -translate-x-full md:translate-x-0 z-20">
    <h2 class="text-2xl font-bold mb-6">DMA ELECTRICALS</h2>
    <nav>
        <a href="dashboard.php" class="flex items-center p-2 <?php echo ($active_page == 'dashboard') ? 'bg-gray-700' : ''; ?> hover:bg-gray-700 rounded-md">
            <i class="fas fa-tachometer-alt w-6"></i>
            <span class="ml-2">Dashboard</span>
        </a>
        <a href="products.php" class="flex items-center p-2 <?php echo ($active_page == 'products') ? 'bg-gray-700' : ''; ?> hover:bg-gray-700 rounded-md mt-2">
            <i class="fas fa-box w-6"></i>
            <span class="ml-2">Products</span>
        </a>
        
        <!-- REMOVED: Stock Levels Button -->
        
        <a href="stock_adjust.php" class="flex items-center p-2 <?php echo ($active_page == 'stock_adjust') ? 'bg-gray-700' : ''; ?> hover:bg-gray-700 rounded-md mt-2">
            <i class="fas fa-exchange-alt w-6"></i>
            <span class="ml-2">Stock</span>
        </a>
        
        <?php if ($user_role !== 'admin'): ?>
        <!-- Sales Tracker Link (Hidden for Admin) -->
        <a href="sales.php" class="flex items-center p-2 <?php echo ($active_page == 'sales') ? 'bg-gray-700' : ''; ?> hover:bg-gray-700 rounded-md mt-2">
            <i class="fas fa-money-check-alt w-6"></i>
            <span class="ml-2">Sales Tracker</span>
        </a>
        <?php endif; ?>

        <!-- Sale Return Link -->
        <a href="sale_return.php" class="flex items-center p-2 <?php echo ($active_page == 'sale_return') ? 'bg-gray-700' : ''; ?> hover:bg-gray-700 rounded-md mt-2">
            <i class="fas fa-undo w-6"></i>
            <span class="ml-2">Sale Return</span>
        </a>

        <?php if ($user_role === 'admin'): ?>
        <!-- Reports (Admin Only) -->
        <a href="reports.php" class="flex items-center p-2 <?php echo ($active_page == 'reports') ? 'bg-gray-700' : ''; ?> hover:bg-gray-700 rounded-md mt-2">
            <i class="fas fa-chart-bar w-6"></i>
            <span class="ml-2">Reports</span>
        </a>
        <?php endif; ?>

        <a href="media.html" target="_blank" class="flex items-center p-2 hover:bg-gray-700 rounded-md mt-2">
            <i class="fas fa-images w-6"></i>
            <span class="ml-2">Media</span>
        </a>

        <?php if ($user_role === 'admin'): ?>
        <!-- Admin-Only Management Section -->
        <div id="admin-links">
            <hr class="border-gray-600 my-4">
            <h3 class="text-xs uppercase text-gray-400 font-bold mb-2">Management</h3>
            
            <a href="users.php" class="flex items-center p-2 <?php echo ($active_page == 'users') ? 'bg-gray-700' : ''; ?> hover:bg-gray-700 rounded-md mt-2">
                <i class="fas fa-users w-6"></i>
                <span class="ml-2">Users</span>
            </a>
            
            <a href="branches.php" class="flex items-center p-2 <?php echo ($active_page == 'branches') ? 'bg-gray-700' : ''; ?> hover:bg-gray-700 rounded-md mt-2">
                <i class="fas fa-store w-6"></i>
                <span class="ml-2">Branches</span>
            </a>
            
            <a href="expenses.php" class="flex items-center p-2 <?php echo ($active_page == 'expenses') ? 'bg-gray-700' : ''; ?> hover:bg-gray-700 rounded-md mt-2">
                <i class="fas fa-file-invoice-dollar w-6"></i>
                <span class="ml-2">Expenses</span>
            </a>
        </div>
        <?php endif; ?>

    </nav>
    
    <!-- Separated Billing Button at the bottom (New Sale) -->
    <div class="mt-8 pt-4 border-t border-gray-700">
        <a href="billing.php" class="w-full flex items-center justify-center p-3 bg-green-500 text-white font-bold rounded-lg shadow-lg hover:bg-green-600 transition-colors <?php echo ($active_page == 'billing') ? 'ring-2 ring-green-300' : ''; ?>">
            <i class="fas fa-cash-register mr-3 text-lg"></i>
            NEW SALE / BILLING
        </a>
    </div>
</aside>