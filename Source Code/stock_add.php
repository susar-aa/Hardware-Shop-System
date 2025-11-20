<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Stock - DMA ELECTRICALS</title>
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
<body class="bg-gray-100 flex layout-shell" data-page="stock_add" data-title="Add Stock">

    <!-- Sidebar will be injected here -->
    <div id="sidebar-placeholder"></div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen">
        
        <!-- Top Bar will be injected here -->
        <div id="topbar-placeholder"></div>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto">
            <div class="w-full max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Stock In Form</h1>
                
                <!-- Form success/error message -->
                <div id="form-message" class="hidden p-4 rounded-md mb-4"></div>

                <form id="stock-in-form">
                    <div class="mb-4">
                        <label for="stockin-product" class="block text-sm font-medium text-gray-700">Product *</label>
                        <select id="stockin-product" name="product_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Loading products...</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="stockin-branch" class="block text-sm font-medium text-gray-700">Branch *</label>
                        <select id="stockin-branch" name="branch_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Loading branches...</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label for="stockin-quantity" class="block text-sm font-medium text-gray-700">Quantity *</label>
                            <input type="number" id="stockin-quantity" name="quantity" min="1" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div class="mb-4">
                            <label for="stockin-cost" class="block text-sm font-medium text-gray-700">Cost per Unit ($)</label>
                            <input type="number" id="stockin-cost" name="cost_per_unit" min="0" step="0.01" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="Optional">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" id="stock-in-submit" class="px-6 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">
                            Add Stock
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