document.addEventListener('DOMContentLoaded', () => {

    // Check if we are on the billing page
    if (document.getElementById('billing-page-container')) {
        
        // --- State Variables ---
        let allProducts = [];
        let allBranches = [];
        let currentBill = []; 
        let selectedBranchId = null;
        let selectedProductForModal = null;
        let selectedCustomer = null; // { id, name }

        // --- Get User ---
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
        const submitSaleBtn = document.getElementById('submit-sale-btn'); // Cash Sale
        const creditSaleBtn = document.getElementById('credit-sale-btn'); // Credit Sale
        const chequeSaleBtn = document.getElementById('cheque-sale-btn'); // Cheque Sale
        
        const errorDisplay = document.getElementById('billing-error');
        const successDisplay = document.getElementById('billing-success');
        
        const custDisplay = document.getElementById('selected-customer-display');
        const custNameDisplay = document.getElementById('cust-name-display');
        const custNicDisplay = document.getElementById('cust-nic-display');
        const removeCustBtn = document.getElementById('remove-customer-btn');

        // Modal Elements (Product Quantity & Price)
        const quantityModal = document.getElementById('quantity-modal');
        const modalProductName = document.getElementById('modal-product-name');
        const modalPriceInput = document.getElementById('modal-price-input'); // UPDATED: Editable Price Input
        const modalStockDisplay = document.getElementById('modal-stock-display');
        const modalQuantityInput = document.getElementById('modal-quantity-input');
        const modalProductId = document.getElementById('modal-product-id');
        const modalProductStock = document.getElementById('modal-product-stock');
        const modalQtyError = document.getElementById('modal-qty-error');
        const modalRowTotal = document.getElementById('modal-refund-calc'); // NEW: Calculation display
        const cancelQuantityBtn = document.getElementById('cancel-quantity-btn');
        const quantityForm = document.getElementById('quantity-form');

        // Modal Elements (Customer Selection)
        const custModal = document.getElementById('customer-modal');
        const closeCustModal = document.getElementById('close-cust-modal');
        const tabSelectCust = document.getElementById('tab-select-cust');
        const tabAddCust = document.getElementById('tab-add-cust');
        const viewSelectCust = document.getElementById('view-select-cust');
        const viewAddCust = document.getElementById('view-add-cust');
        const custSearchInput = document.getElementById('customer-search-input');
        const custList = document.getElementById('customer-list');
        const newCustForm = document.getElementById('new-customer-form');

        // Modal Elements (Cheque Details)
        const chequeModal = document.getElementById('cheque-modal');
        const closeChequeModal = document.getElementById('close-cheque-modal');
        const cancelChequeBtn = document.getElementById('cancel-cheque-btn');
        const chequeForm = document.getElementById('cheque-details-form');

        // --- Helpers ---
        function showMessage(type, message) {
            const display = (type === 'error') ? errorDisplay : successDisplay;
            const otherDisplay = (type ==='error') ? successDisplay : errorDisplay;
            display.textContent = message;
            display.classList.remove('hidden');
            otherDisplay.classList.add('hidden');
            if (type === 'success') setTimeout(() => display.classList.add('hidden'), 5000);
        }

        function renderBill() {
            cartBody.innerHTML = '';
            let total = 0;
            const hasItems = currentBill.length > 0;

            if (!hasItems) {
                cartNoItems.classList.remove('hidden');
                cartTableContainer.classList.add('hidden');
                if (currentUser && currentUser.role === 'admin') branchSelect.disabled = false; 
            } else {
                cartNoItems.classList.add('hidden');
                cartTableContainer.classList.remove('hidden');
                branchSelect.disabled = true; 
            }

            submitSaleBtn.disabled = !hasItems;
            creditSaleBtn.disabled = !hasItems;
            chequeSaleBtn.disabled = !hasItems;
            
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

        async function fetchProductDetails(productId, branchId) {
            try {
                const stockResponse = await fetch(`api/stock/get_current.php?product_id=${productId}&branch_id=${branchId}`);
                const stockData = await stockResponse.json();
                const product = allProducts.find(p => p.product_id == productId);
                if (!product || !stockResponse.ok) throw new Error('Product or stock data not found.');
                return {
                    product_id: productId,
                    name: product.name,
                    product_code: product.product_code || 'N/A',
                    price: parseFloat(product.price || 0),
                    stock: stockData.stock || 0
                };
            } catch (error) {
                showMessage('error', `Failed to load details: ${error.message}`);
                return null;
            }
        }

        function updateCustomerDisplay() {
            if (selectedCustomer) {
                custNameDisplay.textContent = selectedCustomer.name;
                if (selectedCustomer.nic) {
                    custNicDisplay.textContent = `(NIC: ${selectedCustomer.nic})`;
                    custNicDisplay.classList.remove('hidden');
                } else {
                    custNicDisplay.classList.add('hidden');
                }
                custDisplay.classList.remove('hidden');
            } else {
                custDisplay.classList.add('hidden');
            }
        }

        async function initializeBillingPage() {
            try {
                const response = await fetch('api/stock/get_form_data.php');
                const data = await response.json();
                allBranches = data.branches || [];
                allProducts = data.products || [];
            } catch (error) {
                showMessage('error', `Error loading core inventory data.`);
                return;
            }
            
            if (currentUser.role === 'staff' && currentUser.branch_id) {
                const staffBranch = allBranches.find(b => b.branch_id == currentUser.branch_id);
                if (staffBranch) {
                    selectedBranchId = currentUser.branch_id; 
                    currentBranchName.textContent = staffBranch.branch_name;
                    branchDisplayStatic.classList.remove('hidden');
                    searchInput.disabled = false;
                    searchStatus.classList.add('hidden');
                }
            } else if (currentUser.role === 'admin') {
                branchSelectWrapper.classList.remove('hidden');
                if (allBranches.length > 0) {
                    branchSelect.innerHTML = '<option value="">Select a branch</option>';
                    allBranches.forEach(branch => branchSelect.appendChild(new Option(branch.branch_name, branch.branch_id)));
                }
                searchInput.disabled = true;
                searchStatus.classList.remove('hidden');
            }
            renderBill(); 
        }

        // --- REAL-TIME SEARCH LOGIC (Updated with Debouncing) ---
        let searchTimeout = null;

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = searchInput.value.trim().toLowerCase();
            
            if (query.length < 2) {
                searchResultsList.classList.add('hidden');
                return;
            }

            searchLoader.classList.remove('hidden');
            searchResultsList.classList.add('hidden');

            searchTimeout = setTimeout(() => {
                const results = allProducts.filter(p => 
                    p.name.toLowerCase().includes(query) || 
                    (p.product_code && p.product_code.toLowerCase().includes(query))
                );
                renderSearchResults(results);
                searchLoader.classList.add('hidden');
            }, 300); // 300ms debounce
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
                        <div class="text-right"><div class="font-bold text-blue-600">LKR ${parseFloat(product.price || 0).toFixed(2)}</div></div>
                    `;
                    resultItem.addEventListener('click', async () => {
                        searchResultsList.classList.add('hidden');
                        searchInput.value = product.name;
                        const details = await fetchProductDetails(product.product_id, selectedBranchId);
                        if (details) openQuantityModal(details);
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

        // --- PRODUCT QUANTITY & PRICE MODAL ---
        function openQuantityModal(productDetails) {
            selectedProductForModal = productDetails;
            modalProductName.textContent = `${productDetails.name}`;
            
            // NEW: Initialize editable price with standard price
            if (modalPriceInput) {
                modalPriceInput.value = productDetails.price.toFixed(2);
            }
            
            modalStockDisplay.textContent = `${productDetails.stock}`;
            modalProductId.value = productDetails.product_id;
            modalProductStock.value = productDetails.stock;
            modalQuantityInput.value = 1;
            modalQuantityInput.max = productDetails.stock;
            modalQtyError.classList.add('hidden');
            
            const addButton = document.getElementById('add-item-to-bill-btn');
            const isOutOfStock = productDetails.stock === 0;
            
            addButton.disabled = isOutOfStock;
            modalQuantityInput.disabled = isOutOfStock;
            if (modalPriceInput) modalPriceInput.disabled = isOutOfStock;

            updateModalCalculations(); // Initial calc

            quantityModal.classList.remove('hidden');
            quantityModal.querySelector('.modal-content').classList.remove('-translate-y-10');
            quantityModal.classList.remove('opacity-0', 'visibility-hidden');
            
            // Auto focus the quantity input
            setTimeout(() => modalQuantityInput.focus(), 100);
        }

        // NEW: Helper to update total in modal
        function updateModalCalculations() {
            const qty = parseInt(modalQuantityInput.value) || 0;
            const price = parseFloat(modalPriceInput.value) || 0;
            const stock = parseInt(modalProductStock.value) || 0;
            const addButton = document.getElementById('add-item-to-bill-btn');

            if (qty > stock) {
                modalQtyError.textContent = `Exceeds stock (Max ${stock})`;
                modalQtyError.classList.remove('hidden');
                addButton.disabled = true;
            } else if (qty <= 0) {
                modalQtyError.textContent = `Quantity must be 1 or more`;
                modalQtyError.classList.remove('hidden');
                addButton.disabled = true;
            } else {
                modalQtyError.classList.add('hidden');
                addButton.disabled = false;
            }

            if (modalRowTotal) {
                modalRowTotal.textContent = `LKR ${(qty * price).toFixed(2)}`;
            }
        }

        modalQuantityInput.addEventListener('input', updateModalCalculations);
        if (modalPriceInput) modalPriceInput.addEventListener('input', updateModalCalculations);

        quantityForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const quantity = parseInt(modalQuantityInput.value);
            const customPrice = parseFloat(modalPriceInput.value);

            if (isNaN(quantity) || quantity <= 0) {
                alert("Please enter a valid quantity.");
                return;
            }

            if (currentBill.some(item => item.product_id == modalProductId.value)) {
                showMessage('error', 'Item already in bill. Remove it first to change details.');
                closeQuantityModal();
                return;
            }

            currentBill.push({
                product_id: modalProductId.value,
                name: selectedProductForModal.name,
                price: customPrice, // Using the (potentially modified) price
                quantity: quantity
            });

            renderBill();
            searchInput.value = ''; 
            closeQuantityModal();
        });

        cancelQuantityBtn.addEventListener('click', closeQuantityModal);
        function closeQuantityModal() {
            quantityModal.classList.add('opacity-0', 'visibility-hidden');
            setTimeout(() => quantityModal.classList.add('hidden'), 250);
        }

        // --- CUSTOMER MODAL ---
        creditSaleBtn.addEventListener('click', () => {
            if (selectedCustomer) processSale('credit');
            else openCustModal();
        });

        function openCustModal() {
            custModal.classList.remove('hidden');
            custModal.classList.remove('opacity-0', 'visibility-hidden');
            switchCustTab('select');
        }

        function closeCustModalFn() {
            custModal.classList.add('opacity-0', 'visibility-hidden');
            setTimeout(() => custModal.classList.add('hidden'), 250);
        }

        closeCustModal.addEventListener('click', closeCustModalFn);
        tabSelectCust.addEventListener('click', () => switchCustTab('select'));
        tabAddCust.addEventListener('click', () => switchCustTab('add'));

        function switchCustTab(tab) {
            const isSel = tab === 'select';
            tabSelectCust.classList.toggle('text-blue-600', isSel);
            tabSelectCust.classList.toggle('border-blue-500', isSel);
            tabAddCust.classList.toggle('text-blue-600', !isSel);
            tabAddCust.classList.toggle('border-blue-500', !isSel);
            viewSelectCust.classList.toggle('hidden', !isSel);
            viewAddCust.classList.toggle('hidden', isSel);
        }

        let custSearchTimeout = null;
        custSearchInput.addEventListener('input', () => {
            clearTimeout(custSearchTimeout);
            const query = custSearchInput.value.trim();
            if (query.length === 0) {
                custList.innerHTML = '<div class="p-3 text-center text-gray-400 text-sm">Type to search...</div>';
                return;
            }
            custSearchTimeout = setTimeout(async () => {
                try {
                    const res = await fetch(`api/manage/customers_crud.php?search=${encodeURIComponent(query)}`);
                    const customers = await res.json();
                    custList.innerHTML = '';
                    if (!Array.isArray(customers) || customers.length === 0) {
                        custList.innerHTML = '<div class="p-3 text-center text-gray-500 text-sm">No customers found.</div>';
                    } else {
                        customers.forEach(cust => {
                            const div = document.createElement('div');
                            div.className = 'p-2 hover:bg-blue-50 cursor-pointer border-b last:border-0';
                            
                            let custDetails = cust.phone || '';
                            if (cust.nic) custDetails += (custDetails ? ' | ' : '') + `NIC: ${cust.nic}`;
                            
                            div.innerHTML = `<div class="font-medium">${cust.name}</div><div class="text-xs text-gray-500">${custDetails}</div>`;
                            div.onclick = () => { 
                                selectedCustomer = cust; 
                                updateCustomerDisplay(); 
                                closeCustModalFn(); 
                            };
                            custList.appendChild(div);
                        });
                    }
                } catch (e) { console.error("Customer Search Error:", e); }
            }, 300);
        });

        newCustForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(newCustForm);
            const data = Object.fromEntries(formData.entries());
            try {
                const res = await fetch('api/manage/customers_crud.php', { method: 'POST', body: JSON.stringify(data) });
                const result = await res.json();
                if (res.ok) { 
                    selectedCustomer = { customer_id: result.customer_id, name: data.name, nic: data.nic }; 
                    updateCustomerDisplay(); 
                    closeCustModalFn(); 
                    newCustForm.reset();
                }
            } catch (err) { console.error(err); }
        });

        removeCustBtn.onclick = () => { selectedCustomer = null; updateCustomerDisplay(); };

        branchSelect.addEventListener('change', () => {
            selectedBranchId = branchSelect.value;
            searchInput.disabled = !selectedBranchId;
            searchStatus.classList.toggle('hidden', !!selectedBranchId);
            renderBill();
        });

        // --- SUBMIT SALE LOGIC ---
        async function processSale(type, extraData = null) {
            if (currentBill.length === 0) return;
            if (type === 'credit' && !selectedCustomer) { openCustModal(); return; }

            let btn;
            if (type === 'credit') btn = creditSaleBtn;
            else if (type === 'cheque') btn = chequeSaleBtn;
            else btn = submitSaleBtn;
            
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Processing...';

            try {
                const saleData = {
                    branch_id: selectedBranchId,
                    payment_method: type, 
                    customer_id: selectedCustomer ? selectedCustomer.customer_id : null,
                    items: currentBill.map(item => ({ product_id: item.product_id, quantity: item.quantity, price: item.price }))
                };
                
                if (type === 'cheque' && extraData) {
                    saleData.cheque_details = extraData;
                }
                
                const res = await fetch('api/sales/create_sale.php', { method: 'POST', body: JSON.stringify(saleData) });
                if (!res.ok) {
                    const errorJson = await res.json();
                    throw new Error(errorJson.error || 'Server error');
                }
                showMessage('success', `${type.charAt(0).toUpperCase() + type.slice(1)} Sale recorded!`);
                currentBill = []; selectedCustomer = null; updateCustomerDisplay(); renderBill(); 
            } catch (error) { showMessage('error', `Error: ${error.message}`); }
            finally { btn.disabled = false; btn.innerHTML = originalText; }
        }

        submitSaleBtn.onclick = () => processSale('cash');

        // --- CHEQUE MODAL LOGIC ---
        chequeSaleBtn.onclick = () => {
            if (currentBill.length === 0) return;
            // Enforce customer selection before opening cheque modal
            if (!selectedCustomer) {
                openCustModal();
                return;
            }

            chequeModal.classList.remove('hidden');
            chequeModal.classList.remove('opacity-0', 'visibility-hidden');
            chequeModal.querySelector('.modal-content').classList.remove('-translate-y-10');
        };

        function closeChequeModalFn() {
            chequeModal.classList.add('opacity-0', 'visibility-hidden');
            setTimeout(() => chequeModal.classList.add('hidden'), 250);
            chequeForm.reset();
        }

        closeChequeModal.onclick = closeChequeModalFn;
        cancelChequeBtn.onclick = closeChequeModalFn;

        chequeForm.onsubmit = (e) => {
            e.preventDefault();
            const formData = new FormData(chequeForm);
            const data = Object.fromEntries(formData.entries());
            closeChequeModalFn();
            processSale('cheque', data);
        };

        if (currentUser) initializeBillingPage();
        else document.addEventListener('user-ready', (e) => { currentUser = e.detail; initializeBillingPage(); }, { once: true });
        
        cartBody.onclick = (e) => {
            const btn = e.target.closest('.remove-item-btn');
            if (btn) {
                currentBill.splice(btn.dataset.index, 1);
                renderBill();
            }
        };
        
        renderBill(); 
    }
});