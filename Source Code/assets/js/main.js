// This script runs on every internal page
// Handles Global: Session, Header, Sidebar
// Handles Page: Stock Adjustments (Tabs, Logic)
// Other pages (products, billing, sales, expenses, dashboard) have their own dedicated scripts.

// --- 1. SESSION CHECK (IMMEDIATE) ---
(async function checkLoginStatus() {
    let userData = null;
    try {
        const response = await fetch('api/auth/check_session.php', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        });

        if (!response.ok) throw new Error('Not authenticated');

        const data = await response.json();
        userData = data.user;

        if (userData) {
            sessionStorage.setItem('currentUser', JSON.stringify(userData));
            // Dispatch Event to notify other scripts (billing.js, sales.js, etc.) that user data is ready
            const event = new CustomEvent('user-ready', { detail: userData });
            document.dispatchEvent(event);
        }

    } catch (error) {
        console.warn('Session check failed, redirecting to login.');
        sessionStorage.removeItem('currentUser'); // Clear on failure
        window.location.href = 'admin.html';
        return; // Stop further execution
    }

    if (userData) {
        initializePage(userData);
    }
})();

async function initializePage(user) {
    
    // --- Global Elements ---
    const userNameEl = document.getElementById('user-name');
    const userBranchEl = document.getElementById('user-branch-display'); // Header Branch Display
    const logoutButton = document.getElementById('logout-button');
    const adminLinks = document.getElementById('admin-links');
    const menuButton = document.getElementById('menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');
    
    // Populate user info
    if (userNameEl) {
        userNameEl.textContent = user.name || 'User';
    }

    // --- Load Branch Name for Header ---
    if (userBranchEl) {
        if (user.role === 'admin') {
            userBranchEl.textContent = '(Admin Access)';
        } else if (user.branch_id) {
            try {
                // Fetch branch details based on user.branch_id to get the actual name
                // We reuse the branches_crud API which supports filtering by ID
                const branchResponse = await fetch(`api/manage/branches_crud.php?id=${user.branch_id}`);
                const branches = await branchResponse.json();
                
                if (branches && branches.length > 0 && branches[0].branch_name) {
                    userBranchEl.textContent = `(${branches[0].branch_name})`;
                } else {
                    userBranchEl.textContent = '';
                }
            } catch (error) {
                console.error("Error fetching user's branch:", error);
                userBranchEl.textContent = '';
            }
        } else {
            userBranchEl.textContent = '';
        }
    }
    
    // Show admin-only elements in sidebar
    if (user.role === 'admin') {
        if (adminLinks) {
            adminLinks.classList.remove('hidden');
        }
        // Show any other elements marked 'admin-only'
        document.querySelectorAll('.admin-only').forEach(el => {
            el.classList.remove('hidden');
        });
    }

    // --- Global Event Listeners ---
    
    // Logout button
    if (logoutButton) {
        logoutButton.addEventListener('click', async () => {
            try {
                await fetch('api/auth/logout.php');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                sessionStorage.removeItem('currentUser');
                window.location.href = 'admin.html';
            }
        });
    }

    // Mobile menu toggle
    if (menuButton && sidebar && overlay) {
        menuButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });
        overlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    }

    // --- PAGE LOGIC ROUTING ---
    // Dashboard logic is in dashboard.js
    // Products logic is in products.js
    // Billing logic is in billing.js
    // Sales logic is in sales.js
    // Expenses logic is in expenses.js
    
    // Only Stock Adjustments logic remains here (or could be moved to stock.js in future)
    if (document.getElementById('stock-adjust-tabs')) {
        loadStockAdjustPage(user);
    }
}


/**
 * =================================================================
 * STOCK ADJUSTMENTS PAGE LOGIC
 * =================================================================
 */
async function loadStockAdjustPage(user) {
    
    // --- Get Elements ---
    const tabStockIn = document.getElementById('tab-stock-in');
    const tabStockOut = document.getElementById('tab-stock-out');
    const tabStockLevels = document.getElementById('tab-stock-levels');
    const panelStockIn = document.getElementById('panel-stock-in');
    const panelStockOut = document.getElementById('panel-stock-out');
    const panelStockLevels = document.getElementById('panel-stock-levels');

    const inForm = document.getElementById('stock-in-form');
    const outForm = document.getElementById('stock-out-form');

    // Product Search Inputs
    const inProductSearch = document.getElementById('stock-in-product-search');
    const inProductHidden = document.getElementById('stock-in-product-id');
    const inSearchResults = document.getElementById('stock-in-results');

    const outProductSearch = document.getElementById('stock-out-product-search');
    const outProductHidden = document.getElementById('stock-out-product-id');
    const outSearchResults = document.getElementById('stock-out-results');

    const inBranchSelect = document.getElementById('stock-in-branch');
    const outBranchSelect = document.getElementById('stock-out-branch');

    const inError = document.getElementById('stock-in-error');
    const inSuccess = document.getElementById('stock-in-success');
    const outError = document.getElementById('stock-out-error');
    const outSuccess = document.getElementById('stock-out-success');
    
    const inSubmit = document.getElementById('stock-in-submit');
    const outSubmit = document.getElementById('stock-out-submit');

    // Stock Level Elements
    const levelBranchFilter = document.getElementById('stock-level-branch-filter');
    const levelLoader = document.getElementById('stock-level-loader');
    const levelTableContainer = document.getElementById('stock-level-table-container');
    const levelTableBody = document.getElementById('stock-level-table-body');
    const levelNoItems = document.getElementById('stock-level-no-items');
    const levelError = document.getElementById('stock-level-error');

    // Current Stock Display Elements
    const stockInCurrentStock = document.getElementById('stock-in-current-stock');
    const stockInCurrentValue = document.getElementById('stock-in-current-value');
    const stockOutCurrentStock = document.getElementById('stock-out-current-stock');
    const stockOutCurrentValue = document.getElementById('stock-out-current-value');

    let allProducts = []; // Store all products for search

    // --- Tab Switching Logic ---
    const allTabs = [tabStockIn, tabStockOut, tabStockLevels];
    const allPanels = [panelStockIn, panelStockOut, panelStockLevels];

    function switchTab(selectedTab, selectedPanel) {
        allTabs.forEach(tab => {
            if (tab === selectedTab) {
                tab.classList.add('text-blue-600', 'border-blue-500');
                tab.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                tab.setAttribute('aria-current', 'page');
            } else {
                tab.classList.remove('text-blue-600', 'border-blue-500');
                tab.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                tab.removeAttribute('aria-current');
            }
        });
        
        allPanels.forEach(panel => {
            if (panel === selectedPanel) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });
    }

    tabStockIn.addEventListener('click', () => switchTab(tabStockIn, panelStockIn));
    tabStockOut.addEventListener('click', () => switchTab(tabStockOut, panelStockOut));
    tabStockLevels.addEventListener('click', () => switchTab(tabStockLevels, panelStockLevels));

    // --- Load Stock Levels Table ---
    async function loadStockLevelsTable(branch_id) {
        if (!branch_id) {
            levelTableContainer.classList.add('hidden');
            levelNoItems.classList.add('hidden');
            levelError.classList.add('hidden');
            levelLoader.classList.add('hidden');
            return;
        }

        levelLoader.classList.remove('hidden');
        levelTableContainer.classList.add('hidden');
        levelNoItems.classList.add('hidden');
        levelError.classList.add('hidden');
        levelTableBody.innerHTML = '';

        try {
            const response = await fetch(`api/stock/get_levels.php?branch_id=${branch_id}`);
            if (!response.ok) {
                const errData = await response.json();
                throw new Error(errData.error || 'Failed to fetch stock levels');
            }
            
            const stockData = await response.json();

            if (stockData.length === 0) {
                levelNoItems.classList.remove('hidden');
            } else {
                stockData.forEach(item => {
                    const row = document.createElement('tr');
                    const stockClass = (item.stock > 0 && item.stock <= item.reorder_level) ? 'text-red-600 font-bold' : 'text-gray-500';
                    
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.product_name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.product_code || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${stockClass}">${item.stock}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.reorder_level}</td>
                    `;
                    levelTableBody.appendChild(row);
                });
                levelTableContainer.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error loading stock levels:', error);
            levelError.textContent = error.message;
            levelError.classList.remove('hidden');
        } finally {
            levelLoader.classList.add('hidden');
        }
    }

    // --- Fetch Current Stock ---
    async function fetchCurrentStock(productId, branchId, containerEl, valueEl) {
        if (!productId || !branchId) {
            containerEl.classList.add('hidden');
            return;
        }
        valueEl.textContent = 'Loading...';
        containerEl.classList.remove('hidden');
        try {
            const response = await fetch(`api/stock/get_current.php?product_id=${productId}&branch_id=${branchId}`);
            const data = await response.json();
            if (response.ok) {
                valueEl.textContent = data.stock;
            } else {
                valueEl.textContent = 'Error';
            }
        } catch (error) {
            console.error('Fetch error:', error);
            valueEl.textContent = 'Error';
        }
    }

    // --- Product Search Logic ---
    function setupProductSearch(searchInput, hiddenInput, resultsContainer, branchSelect, currentStockContainer, currentStockValue) {
        let searchTimeout = null;

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = searchInput.value.trim().toLowerCase();

            if (query.length === 0) {
                resultsContainer.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(() => {
                const filtered = allProducts.filter(p => 
                    p.name.toLowerCase().includes(query) || 
                    (p.product_code && p.product_code.toLowerCase().includes(query))
                );

                renderSearchResults(filtered, resultsContainer, searchInput, hiddenInput, branchSelect, currentStockContainer, currentStockValue);
            }, 300);
        });

        // Hide results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.classList.add('hidden');
            }
        });
    }

    function renderSearchResults(results, container, searchInput, hiddenInput, branchSelect, currentStockContainer, currentStockValue) {
        container.innerHTML = '';
        
        if (results.length === 0) {
            container.innerHTML = '<div class="p-2 text-gray-500 text-sm">No products found</div>';
        } else {
            results.forEach(product => {
                const div = document.createElement('div');
                div.className = 'p-2 hover:bg-gray-100 cursor-pointer text-sm border-b last:border-0';
                div.innerHTML = `<div class="font-medium">${product.name}</div><div class="text-xs text-gray-500">${product.product_code || ''}</div>`;
                
                div.addEventListener('click', () => {
                    searchInput.value = product.name;
                    hiddenInput.value = product.product_id;
                    container.classList.add('hidden');
                    // Fetch stock immediately upon selection
                    fetchCurrentStock(product.product_id, branchSelect.value, currentStockContainer, currentStockValue);
                });
                
                container.appendChild(div);
            });
        }
        container.classList.remove('hidden');
    }

    // --- Populate Data & Init Search ---
    async function populateDropdowns() {
        try {
            const response = await fetch('api/stock/get_form_data.php');
            if (!response.ok) {
                throw new Error('Failed to load form data');
            }
            const data = await response.json();

            // Store products for search
            allProducts = data.products || [];

            // Populate Branches
            if (!data.branches || data.branches.length === 0) {
                inError.textContent = 'Error: No branches found.';
                inError.classList.remove('hidden');
                return;
            } else {
                inBranchSelect.innerHTML = '<option value="">Select a branch</option>';
                outBranchSelect.innerHTML = '<option value="">Select a branch</option>';
                levelBranchFilter.innerHTML = '<option value="">Select a branch</option>';
                
                data.branches.forEach(branch => {
                    const optionIn = new Option(branch.branch_name, branch.branch_id);
                    const optionOut = new Option(branch.branch_name, branch.branch_id);
                    const optionLevel = new Option(branch.branch_name, branch.branch_id);
                    
                    inBranchSelect.appendChild(optionIn);
                    outBranchSelect.appendChild(optionOut);
                    levelBranchFilter.appendChild(optionLevel);
                });
            }

            // --- ROLE-BASED LOGIC ---
            if (user.role === 'staff' && user.branch_id) {
                // Lock In Form Branch
                inBranchSelect.value = user.branch_id;
                inBranchSelect.disabled = true;
                
                // Lock Out Form Branch
                outBranchSelect.value = user.branch_id;
                outBranchSelect.disabled = true;
                
                // Lock Level Filter Branch & Load Data
                levelBranchFilter.value = user.branch_id;
                levelBranchFilter.disabled = true;
                loadStockLevelsTable(user.branch_id);
            } else if (user.role === 'admin') {
                // Default: Load first branch for admin convenience
                if (data.branches.length > 0) {
                    levelBranchFilter.value = data.branches[0].branch_id;
                    loadStockLevelsTable(data.branches[0].branch_id);
                }
            } else {
                // Error case: Staff without branch
                if (user.role === 'staff') {
                    inError.textContent = 'Error: You are not assigned to a branch.';
                    inError.classList.remove('hidden');
                    inSubmit.disabled = true;
                    outSubmit.disabled = true;
                }
            }

            // Setup Search listeners now that products are loaded
            setupProductSearch(inProductSearch, inProductHidden, inSearchResults, inBranchSelect, stockInCurrentStock, stockInCurrentValue);
            setupProductSearch(outProductSearch, outProductHidden, outSearchResults, outBranchSelect, stockOutCurrentStock, stockOutCurrentValue);


        } catch (error) {
            console.error('Error populating dropdowns:', error);
            inError.textContent = 'A network error occurred.';
            inError.classList.remove('hidden');
        }
    }

    // --- Form Submission Logic ---
    async function handleStockAdjustment(e, type) {
        e.preventDefault();
        
        const form = e.target;
        const submitButton = (type === 'in') ? inSubmit : outSubmit;
        const errorDisplay = (type === 'in') ? inError : outError;
        const successDisplay = (type === 'in') ? inSuccess : outSuccess;

        submitButton.disabled = true;
        submitButton.textContent = (type === 'in') ? 'Adding...' : 'Removing...';
        errorDisplay.classList.add('hidden');
        successDisplay.classList.add('hidden');

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.type = type; 

        // If branch selection is disabled (Staff), we must ensure the value is included
        if (user.role === 'staff' && user.branch_id) {
            data.branch_id = user.branch_id;
        }

        // Basic check for product selection
        if (!data.product_id) {
             errorDisplay.textContent = 'Please search and select a product from the list.';
             errorDisplay.classList.remove('hidden');
             submitButton.disabled = false;
             submitButton.textContent = (type === 'in') ? 'Add Stock' : 'Remove Stock';
             return;
        }

        try {
            const response = await fetch('api/stock/adjust.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'An unknown error occurred');
            }

            successDisplay.textContent = result.message || 'Stock updated successfully!';
            successDisplay.classList.remove('hidden');
            form.reset(); 
            
            // Clear hidden inputs and search texts
            if(type === 'in') {
                inProductHidden.value = '';
                inProductSearch.value = '';
                stockInCurrentStock.classList.add('hidden');
            } else {
                outProductHidden.value = '';
                outProductSearch.value = '';
                stockOutCurrentStock.classList.add('hidden');
            }

            // Restore locked branch selection for staff after reset
            if (user.role === 'staff' && user.branch_id) {
                if (type === 'in') inBranchSelect.value = user.branch_id;
                if (type === 'out') outBranchSelect.value = user.branch_id;
            }

            setTimeout(() => {
                successDisplay.classList.add('hidden');
            }, 4000);

            if (!panelStockLevels.classList.contains('hidden')) {
                 const currentBranch = levelBranchFilter.value;
                 if (currentBranch) loadStockLevelsTable(currentBranch);
            }

        } catch (error) {
            console.error(`Error (Stock ${type}):`, error);
            errorDisplay.textContent = error.message;
            errorDisplay.classList.remove('hidden');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = (type === 'in') ? 'Add Stock' : 'Remove Stock';
        }
    }

    // Listeners
    levelBranchFilter.addEventListener('change', () => {
        loadStockLevelsTable(levelBranchFilter.value);
    });
    
    // Branch change listeners to update stock if product is already selected
    inBranchSelect.addEventListener('change', () => {
         if(inProductHidden.value) fetchCurrentStock(inProductHidden.value, inBranchSelect.value, stockInCurrentStock, stockInCurrentValue);
    });
    outBranchSelect.addEventListener('change', () => {
         if(outProductHidden.value) fetchCurrentStock(outProductHidden.value, outBranchSelect.value, stockOutCurrentStock, stockOutCurrentValue);
    });

    inForm.addEventListener('submit', (e) => handleStockAdjustment(e, 'in'));
    outForm.addEventListener('submit', (e) => handleStockAdjustment(e, 'out'));

    // Initial Load
    populateDropdowns();
}