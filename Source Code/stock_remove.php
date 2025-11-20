<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remove Stock - DMA ELECTRICALS</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex layout-shell" data-page="stock_remove" data-title="Remove Stock">

    <!-- Sidebar will be injected here -->
    <div id="sidebar-placeholder"></div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen">
        
        <!-- Top Bar will be injected here -->
        <div id="topbar-placeholder"></div>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto">
            <div class="w-full max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Stock Out Form</h1>

                <!-- Form success/error message -->
                <div id="form-message" class="hidden p-4 rounded-md mb-4"></div>

                <form id="stock-out-form">
                    <div class="mb-4">
                        <label for="stockout-product" class="block text-sm font-medium text-gray-700">Product *</label>
                        <select id="stockout-product" name="product_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Loading products...</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="stockout-branch" class="block text-sm font-medium text-gray-700">Branch *</label>
                        <select id="stockout-branch" name="branch_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Loading branches...</option>
                        </select>
                    </div>
                    
                    <!-- Current Stock Info -->
                    <div id="current-stock-info" class="hidden p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-md mb-4">
                        Current stock at this branch: <strong id="current-stock-level">--</strong>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label for="stockout-quantity" class="block text-sm font-medium text-gray-700">Quantity *</label>
                            <input type="number" id="stockout-quantity" name="quantity" min="1" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div class="mb-4">
                            <label for="stockout-type" class="block text-sm font-medium text-gray-700">Reason *</label>
                            <select id="stockout-type" name="type" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                                <option value="sale">Sale</option>
                                <option value="damage">Damage</option>
                                <option value="return">Return to Supplier</option>
                                <option value="manual_adjust">Manual Adjustment</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="stockout-reference" class="block text-sm font-medium text-gray-700">Reference No.</label>
                        <input type="text" id="stockout-reference" name="reference_no" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="e.g., Sale Invoice ID, Damage Report ID">
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" id="stock-out-submit" class="px-6 py-2 bg-red-600 text-white rounded-md font-medium hover:bg-red-700">
                            Remove Stock
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <!-- Overlay for mobile menu -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Global JS for the internal system -->
    <script src="assets/js/main.js"></script>
</body>
</html>