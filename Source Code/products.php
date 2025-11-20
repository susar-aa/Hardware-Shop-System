<?php
session_start();
$page_title = 'Product Management';
$active_page = 'products';

include 'admin/_header.php';
include 'admin/_sidebar.php';
$user_role = $_SESSION['role'] ?? 'staff';
?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-screen bg-gray-100">
        
        <?php include 'admin/_topbar.php'; ?>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-auto" id="products-page-container" data-role="<?php echo $user_role; ?>">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h1 class="text-2xl font-bold text-gray-900">Inventory Management</h1>
                
                <!-- Action Buttons Container -->
                <div class="flex flex-wrap gap-2">
                    <!-- Export Button -->
                    <button id="export-csv-btn" class="px-4 py-2 bg-green-600 text-white rounded-md font-medium hover:bg-green-700 flex items-center">
                        <i class="fas fa-file-export mr-2"></i>Export
                    </button>
                    
                    <?php if($user_role === 'admin'): ?>
                    <!-- Import Button & Hidden Input -->
                    <button id="import-csv-btn" class="px-4 py-2 bg-orange-600 text-white rounded-md font-medium hover:bg-orange-700 flex items-center">
                        <i class="fas fa-file-import mr-2"></i>Import
                    </button>
                    <input type="file" id="csv-file-input" accept=".csv" class="hidden">

                    <button id="add-product-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Product
                    </button>
                    <button id="add-category-btn" class="px-4 py-2 bg-indigo-600 text-white rounded-md font-medium hover:bg-indigo-700">
                        <i class="fas fa-tags mr-2"></i>Category
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-t-lg shadow-sm border-b border-gray-200 mb-0">
                <nav class="flex -mb-px">
                    <button id="tab-products" class="tab-btn w-1/2 py-4 px-6 text-center border-b-2 font-medium text-sm text-blue-600 border-blue-500">
                        <i class="fas fa-box mr-2"></i> Products
                    </button>
                    <button id="tab-categories" class="tab-btn w-1/2 py-4 px-6 text-center border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fas fa-tags mr-2"></i> Categories
                    </button>
                </nav>
            </div>

            <div class="bg-white rounded-b-lg shadow-md p-6">
                
                <!-- VIEW: PRODUCTS -->
                <div id="view-products">
                    <div id="table-loader" class="flex justify-center items-center py-8">
                        <div class="loader"></div>
                    </div>
                    <div id="products-table-container" class="overflow-x-auto hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase admin-only hidden">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="products-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Product rows injected by JS -->
                            </tbody>
                        </table>
                    </div>
                    <div id="table-no-items" class="hidden text-center text-gray-500 py-8">
                        <i class="fas fa-box-open text-4xl text-gray-400"></i>
                        <p class="mt-2">No products found. Add one to get started!</p>
                    </div>
                </div>

                <!-- VIEW: CATEGORIES -->
                <div id="view-categories" class="hidden">
                     <div id="cat-loader" class="hidden flex justify-center items-center py-8">
                        <div class="loader"></div>
                    </div>
                    <div id="cat-table-container" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-full">Category Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase admin-only hidden">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="cat-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Category rows injected by JS -->
                            </tbody>
                        </table>
                    </div>
                    <div id="cat-no-items" class="hidden text-center text-gray-500 py-8">
                        <p>No categories found.</p>
                    </div>
                </div>

            </div>
        </main>
    </div>
    
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Product Modal -->
    <div id="product-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-lg p-6 transform -translate-y-10">
            <h2 id="modal-title" class="text-2xl font-bold mb-4">Add New Product</h2>
            <form id="product-form">
                <input type="hidden" id="product-id" name="product-id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="product-name" class="block text-sm font-medium text-gray-700">Product Name *</label>
                        <input type="text" id="product-name" name="name" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                    </div>
                    <div>
                        <label for="product-code" class="block text-sm font-medium text-gray-700">Product Code</label>
                        <input type="text" id="product-code" name="product_code" class="mt-1 w-full border border-gray-300 rounded-md p-2">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="product-category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select id="product-category" name="category_id" class="mt-1 w-full border border-gray-300 rounded-md p-2">
                        <!-- Categories loaded by JS -->
                    </select>
                </div>

                <div class="mt-4">
                    <label for="product-description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="product-description" name="description" rows="3" class="mt-1 w-full border border-gray-300 rounded-md p-2"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label for="product-price" class="block text-sm font-medium text-gray-700">Price (LKR)</label>
                        <input type="number" id="product-price" name="price" step="0.01" min="0" class="mt-1 w-full border border-gray-300 rounded-md p-2" value="0">
                    </div>
                    <div>
                        <label for="product-cost" class="block text-sm font-medium text-gray-700">Cost (LKR)</label>
                        <input type="number" id="product-cost" name="cost" step="0.01" min="0" class="mt-1 w-full border border-gray-300 rounded-md p-2" value="0">
                    </div>
                    <div>
                        <label for="product-reorder-level" class="block text-sm font-medium text-gray-700">Reorder Level</label>
                        <input type="number" id="product-reorder-level" name="reorder_level" min="0" class="mt-1 w-full border border-gray-300 rounded-md p-2" value="5">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label for="product-image" class="block text-sm font-medium text-gray-700">Image URL</label>
                    <div class="flex mt-1">
                        <input type="text" id="product-image" name="image" class="w-full border border-gray-300 rounded-l-md p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="uploads/image.jpg or https://...">
                        <button type="button" id="browse-media-btn" class="px-3 py-2 bg-gray-200 text-gray-700 rounded-r-md hover:bg-gray-300">
                            <i class="fas fa-folder-open"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Visibility Checkbox -->
                <div class="mt-4 flex items-center">
                    <input type="checkbox" id="product-visible" name="is_visible" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" checked>
                    <label for="product-visible" class="ml-2 block text-sm text-gray-900 font-medium">Show in Public Catalog</label>
                </div>

                <div id="form-error" class="hidden text-red-600 text-sm mt-4"></div>

                <div class="mt-6 flex justify-end">
                    <button type="button" id="cancel-product-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="save-product-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="category-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 id="cat-modal-title" class="text-xl font-bold mb-4">Add New Category</h2>
            <form id="category-form">
                <input type="hidden" id="cat-id" name="category_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Category Name *</label>
                    <input type="text" id="cat-name" name="category_name" class="mt-1 w-full border border-gray-300 rounded-md p-2" required>
                </div>
                <div id="cat-form-error" class="hidden text-red-600 text-sm mt-4 mb-2"></div>
                <div class="flex justify-end">
                    <button type="button" id="cancel-cat-btn" class="px-4 py-2 bg-gray-200 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="save-cat-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-30 hidden opacity-0 visibility-hidden">
        <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform -translate-y-10">
            <h2 class="text-xl font-bold mb-4">Confirm Deletion</h2>
            <p>Are you sure you want to delete this <strong id="delete-type-text">item</strong>?</p>
            <p class="text-xs text-red-500 mt-1 hidden" id="delete-warn-text">This category cannot be deleted if it has products.</p>
            <div id="delete-error" class="hidden text-red-600 text-sm mt-4"></div>
            <div class="mt-6 flex justify-end">
                <button type="button" id="cancel-delete-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2 hover:bg-gray-300">Cancel</button>
                <button type="button" id="confirm-delete-btn" class="px-4 py-2 bg-red-600 text-white rounded-md font-medium hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>

<!-- Force script reload with timestamp -->
<script src="assets/js/products.js?v=<?php echo time(); ?>"></script>
<?php include 'admin/_footer.php'; ?>