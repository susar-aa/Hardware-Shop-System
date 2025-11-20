document.addEventListener('DOMContentLoaded', () => {

    // Check if we are on the billing page
    if (document.getElementById('billing-page-container')) {
        
        // --- State Variables ---
        let allProducts = [];
        let allBranches = [];
        let currentBill = []; // This will hold items added to the bill
        let selectedBranchId = null;
        let selectedProductForModal = null;
        let selectedProduct = null; // Added missing variable
        let currentStock = 0;

        // --- Get User (Use 'let' so we can update it later) ---
        let currentUser = JSON.parse(sessionStorage.getItem('currentUser'));
        
        // --- Get Elements ---
        const branchSelectWrapper = document.getElementById('branch-selector-wrapper');
        const branchDisplayStatic = document.getElementById('branch-display-static');
        const branchSelect = document.getElementById('billing-branch-select');
        const currentBranchName = document.getElementById('current-branch-name');

        const searchInput = document.getElementById('product-search-input');
        const searchResultsList = document.getElementById('search-results-list');
        const searchLoader = document.getElementById('search-loader');
        const searchStatus = document.getElementById('search-status');

        const cartBody = document.getElementById('billing-cart-body');
        const cartNoItems = document.getElementById('bill-no-items');
        const cartTableContainer = document.getElementById('bill-table-container');
        const totalDisplay = document.getElementById('billing-total-display');
        const submitSaleBtn = document.getElementById('submit-sale-btn');
        
        const errorDisplay = document.getElementById('billing-error');
        const successDisplay = document.getElementById('billing-success');

        // Modal Elements
        const quantityModal = document.getElementById('quantity-modal');
        const modalProductName = document.getElementById('modal-product-name');
        const modalPriceDisplay = document.getElementById('modal-price-display');
        const modalStockDisplay = document.getElementById('modal-stock-display');
        const modalQuantityInput = document.getElementById('modal-quantity-input');
        const modalProductId = document.getElementById('modal-product-id');
        const modalProductPrice = document.getElementById('modal-product-price');
        const modalProductStock = document.getElementById('modal-product-stock');
        const modalQtyError = document.getElementById('modal-qty-error');
        const cancelQuantityBtn = document.getElementById('cancel-quantity-btn');
        const quantityForm = document.getElementById('quantity-form');

        // --- Helper: Show Message ---
        function showMessage(type, message) {
            const display = (type === 'error') ? errorDisplay : successDisplay;
            const otherDisplay = (type ==='error') ? successDisplay : errorDisplay;
            
            display.textContent = message;
            display.classList.remove('hidden');
            otherDisplay.classList.add('hidden');

            if (type === 'success') {
                setTimeout(() => display.classList.add('hidden'), 5000);
            }
        }

        // --- Helper: Render Bill ---
        function renderBill() {
            cartBody.innerHTML = '';
            let total = 0;

            if (currentBill.length === 0) {
                cartNoItems.classList.remove('hidden');
                cartTableContainer.classList.add('hidden');
                submitSaleBtn.disabled = true;
                if (currentUser && currentUser.role === 'admin') {
                    branchSelect.disabled = false; 
                }
                
                // Clear selectedBranchId if admin just cleared the cart
                if (currentUser && currentUser.role === 'admin' && branchSelect.value === '') {
                     selectedBranchId = null;
                     searchInput.disabled = true;
                     searchStatus.classList.remove('hidden');
                     searchStatus.textContent = 'Please select a branch first.';
                }
            } else {
                cartNoItems.classList.add('hidden');
                cartTableContainer.classList.remove('hidden');
                submitSaleBtn.disabled = false;
                branchSelect.disabled = true; 
            }
            
            currentBill.forEach((item, index) => {
                const itemTotal = item.quantity * item.price;
                total += itemTotal;
                const row = `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.quantity}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">LKR ${item.price.toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">LKR ${itemTotal.toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="remove-item-btn text-red-600 hover:text-red-900" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                cartBody.innerHTML += row;
            });

            totalDisplay.textContent = `LKR ${total.toFixed(2)}`;
        }

        // --- Helper: Fetch Current Stock ---
        async function fetchProductDetails(productId, branchId) {
            if (!productId || !branchId) {
                console.error("Cannot fetch details: Missing Product ID or Branch ID");
                return null;
            }

            try {
                const stockResponse = await fetch(`api/stock/get_current.php?product_id=${productId}&branch_id=${branchId}`);
                const stockData = await stockResponse.json();

                const product = allProducts.find(p => p.product_id == productId);

                if (!product || !stockResponse.ok) {
                    throw new Error('Product or stock data not found.');
                }
                
                return {
                    product_id: productId,
                    name: product.name,
                    product_code: product.product_code || 'N/A',
                    price: parseFloat(product.price || 0),
                    stock: stockData.stock || 0
                };

            } catch (error) {
                console.error('Error fetching product details:', error);
                showMessage('error', `Failed to load product details. ${error.message}`);
                return null;
            }
        }

        // --- Initialize Page ---
        async function initializeBillingPage() {
            // First, load all products and branches
            try {
                const response = await fetch('api/stock/get_form_data.php');
                if (!response.ok) {
                    throw new Error('Failed to load initial data');
                }
                const data = await response.json();

                allBranches = data.branches || [];
                allProducts = data.products || [];

            } catch (error) {
                showMessage('error', `Error loading core inventory data: ${error.message}. Check network connection.`);
                return;
            }
            
            // --- Determine Branch Access ---
            if (currentUser.role === 'staff' && currentUser.branch_id) {
                // 1. STAFF LOGIC
                const staffBranch = allBranches.find(b => b.branch_id == currentUser.branch_id);

                if (staffBranch) {
                    selectedBranchId = currentUser.branch_id; // CRITICAL: Set the branch ID!
                    currentBranchName.textContent = staffBranch.branch_name;
                    branchDisplayStatic.classList.remove('hidden');
                    branchSelectWrapper.classList.add('hidden');
                    
                    searchInput.disabled = false; // Enable search
                    searchStatus.classList.add('hidden');
                } else {
                    showMessage('error', 'Error: Your assigned branch was not found. Contact administration.');
                }
            } else if (currentUser.role === 'admin') {
                // 2. ADMIN LOGIC
                branchDisplayStatic.classList.add('hidden');
                branchSelectWrapper.classList.remove('hidden');
                
                // Populate Branch Dropdown
                if (allBranches.length > 0) {
                    branchSelect.innerHTML = '<option value="">Select a branch</option>';
                    allBranches.forEach(branch => {
                        branchSelect.appendChild(new Option(branch.branch_name, branch.branch_id));
                    });
                } else {
                    branchSelect.innerHTML = '<option value="">No branches found</option>';
                }
                
                searchInput.disabled = true; // Disable search until branch selected
                searchStatus.classList.remove('hidden');
                searchStatus.textContent = 'Please select a branch first.';
            } else {
                showMessage('error', 'User role or branch is not configured correctly. Billing disabled.');
            }

            renderBill(); 
        }

        // --- LIVE SEARCH LOGIC ---
        let searchTimeout = null;

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = searchInput.value.trim().toLowerCase();
            
            if (query.length < 2) {
                searchResultsList.classList.add('hidden');
                return;
            }

            searchResultsList.classList.add('hidden');
            searchLoader.classList.remove('hidden');

            searchTimeout = setTimeout(() => {
                const filteredProducts = allProducts.filter(p => 
                    p.name.toLowerCase().includes(query) || 
                    (p.product_code && p.product_code.toLowerCase().includes(query))
                );

                renderSearchResults(filteredProducts);
                searchLoader.classList.add('hidden');

            }, 300); 
        });

        function renderSearchResults(results) {
            searchResultsList.innerHTML = '';

            if (results.length === 0) {
                searchResultsList.innerHTML = '<div class="p-3 text-sm text-gray-500">No products found.</div>';
            } else {
                results.slice(0, 10).forEach(product => { 
                    const resultItem = document.createElement('div');
                    resultItem.className = 'p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 flex justify-between items-center';
                    resultItem.innerHTML = `
                        <div>
                            <div class="font-semibold text-gray-800">${product.name}</div>
                            <div class="text-xs text-gray-500">Code: ${product.product_code || 'N/A'}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-blue-600">LKR ${parseFloat(product.price || 0).toFixed(2)}</div>
                        </div>
                    `;
                    
                    resultItem.addEventListener('click', async () => {
                        searchResultsList.classList.add('hidden');
                        searchInput.value = product.name; 
                        
                        // Ensure we have a branch selected before fetching
                        if(!selectedBranchId) {
                            alert("No branch selected. Cannot fetch stock.");
                            return;
                        }

                        const details = await fetchProductDetails(product.product_id, selectedBranchId);

                        if (details) {
                            openQuantityModal(details);
                        }
                    });

                    searchResultsList.appendChild(resultItem);
                });
            }
            searchResultsList.classList.remove('hidden');
        }

        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResultsList.contains(e.target)) {
                searchResultsList.classList.add('hidden');
            }
        });

        // --- MODAL LOGIC ---

        function openQuantityModal(productDetails) {
            selectedProductForModal = productDetails;
            
            modalProductName.textContent = `${productDetails.name} (Code: ${productDetails.product_code})`;
            modalPriceDisplay.textContent = `LKR ${productDetails.price.toFixed(2)}`;
            modalStockDisplay.textContent = `${productDetails.stock}`;
            modalProductId.value = productDetails.product_id;
            modalProductPrice.value = productDetails.price;
            modalProductStock.value = productDetails.stock;

            modalQuantityInput.value = 1;
            modalQuantityInput.max = productDetails.stock;
            modalQtyError.classList.add('hidden');
            document.getElementById('modal-error').classList.add('hidden');

            const addButton = document.getElementById('add-item-to-bill-btn');

            if (productDetails.stock === 0) {
                modalQtyError.textContent = 'No stock available at this branch.';
                modalQtyError.classList.remove('hidden');
                addButton.disabled = true;
                modalQuantityInput.disabled = true;
            } else {
                addButton.disabled = false;
                modalQuantityInput.disabled = false;
            }

            openModal(quantityModal);
        }

        modalQuantityInput.addEventListener('input', () => {
            const qty = parseInt(modalQuantityInput.value);
            const stock = parseInt(modalProductStock.value);
            const addButton = document.getElementById('add-item-to-bill-btn');
            
            if (qty > stock) {
                modalQtyError.textContent = `Quantity exceeds stock (Max ${stock})`;
                modalQtyError.classList.remove('hidden');
                addButton.disabled = true;
            } else if (qty <= 0 || isNaN(qty)) {
                 modalQtyError.textContent = `Quantity must be 1 or more`;
                modalQtyError.classList.remove('hidden');
                addButton.disabled = true;
            } else {
                modalQtyError.classList.add('hidden');
                addButton.disabled = false;
            }
        });

        quantityForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const quantity = parseInt(modalQuantityInput.value);
            const productId = modalProductId.value;
            const price = parseFloat(modalProductPrice.value);
            const stock = parseInt(modalProductStock.value);

            if (quantity <= 0 || quantity > stock || isNaN(quantity)) {
                 showMessage('error', 'Invalid quantity entered.');
                 return;
            }

            if (currentBill.some(item => item.product_id == productId)) {
                showMessage('error', 'Item already in bill. Remove it first to adjust quantity.');
                closeModal(quantityModal);
                return;
            }

            currentBill.push({
                product_id: productId,
                name: selectedProductForModal.name,
                price: price,
                quantity: quantity
            });

            renderBill();
            searchInput.value = ''; 
            closeModal(quantityModal);
        });

        cancelQuantityBtn.addEventListener('click', () => closeModal(quantityModal));


        // --- Other Event Listeners ---

        branchSelect.addEventListener('change', () => {
            selectedBranchId = branchSelect.value;
            searchInput.value = '';
            searchResultsList.classList.add('hidden');

            if (selectedBranchId) {
                searchInput.disabled = false;
                searchStatus.classList.add('hidden');
            } else {
                searchInput.disabled = true;
                searchStatus.classList.remove('hidden');
                searchStatus.textContent = 'Please select a branch first.';
            }
            
            renderBill();
        });


        cartBody.addEventListener('click', (e) => {
            const removeButton = e.target.closest('.remove-item-btn');
            if (removeButton) {
                const indexToRemove = parseInt(removeButton.dataset.index);
                currentBill.splice(indexToRemove, 1);
                renderBill();
            }
        });

        submitSaleBtn.addEventListener('click', async () => {
            if (currentBill.length === 0) {
                showMessage('error', 'Cannot submit an empty bill.');
                return;
            }

            submitSaleBtn.disabled = true;
            submitSaleBtn.textContent = 'Submitting...';
            showMessage('success', 'Processing sale, please wait...');

            try {
                const saleData = {
                    branch_id: selectedBranchId,
                    items: currentBill.map(item => ({
                        product_id: item.product_id,
                        quantity: item.quantity,
                        price: item.price 
                    }))
                };

                const response = await fetch('api/sales/create_sale.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                });

                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.error || 'Failed to submit sale');
                }

                showMessage('success', 'Sale recorded successfully! Stock has been updated.');
                currentBill = [];
                renderBill(); 
                
            } catch (error) {
                console.error('Sale submission error:', error);
                showMessage('error', `Error: ${error.message}`);
            } finally {
                submitSaleBtn.disabled = false;
                submitSaleBtn.textContent = 'Submit Sale';
                if (currentUser.role === 'admin' && selectedBranchId) {
                     searchInput.disabled = false;
                }
            }
        });

        // --- STARTUP LOGIC ---
        // If user is already loaded, run init immediately
        if (currentUser) {
            initializeBillingPage();
        } 
        
        // Listen for the event just in case (or for re-logins/updates)
        document.addEventListener('user-ready', (e) => {
            currentUser = e.detail; // CRITICAL: Update the variable!
            initializeBillingPage();
        });
        
        // Initial UI set for empty cart
        renderBill(); 

        // Modal Helpers
        function openModal(el) {
            el.classList.remove('hidden');
            el.querySelector('.modal-content').classList.remove('-translate-y-10');
            el.classList.remove('opacity-0', 'visibility-hidden');
        }
        function closeModal(el) {
            el.classList.add('opacity-0', 'visibility-hidden');
            el.querySelector('.modal-content').classList.add('-translate-y-10');
            setTimeout(() => el.classList.add('hidden'), 250);
        }
    }
});