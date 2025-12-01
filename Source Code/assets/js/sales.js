document.addEventListener('DOMContentLoaded', () => {

    // Check if we are on the sales page
    if (document.getElementById('sales-tracker-container')) {
        
        // --- Elements ---
        const pageContainer = document.getElementById('sales-tracker-container');
        const userRole = pageContainer.dataset.role;

        const tableBody = document.getElementById('sales-table-body');
        const tableLoader = document.getElementById('table-loader');
        const tableContainer = document.getElementById('sales-table-container');
        const tableNoItems = document.getElementById('table-no-items');
        const tableError = document.getElementById('table-error');
        
        const filterStartDate = document.getElementById('filter-start-date');
        const filterEndDate = document.getElementById('filter-end-date');
        const adminBranchFilterWrapper = document.getElementById('admin-branch-filter-wrapper');
        const filterBranch = document.getElementById('filter-branch');
        const applyFiltersBtn = document.getElementById('apply-filters-btn');

        // Reversal Modal Elements
        const reversalModal = document.getElementById('reversal-modal');
        const reverseId = document.getElementById('reverse-id');
        const reverseTotal = document.getElementById('reverse-total');
        const reverseItems = document.getElementById('reverse-items');
        const reverseSaleId = document.getElementById('reverse-sale-id');
        const reversalNotes = document.getElementById('reversal-notes');
        const reversalForm = document.getElementById('reversal-form');
        const reversalError = document.getElementById('reversal-error');
        const confirmReversalBtn = document.getElementById('confirm-reversal-btn');
        const cancelReversalBtn = document.getElementById('cancel-reversal-btn');

        let allBranches = [];
        let saleToReverse = null;

        // --- Helper Functions ---

        function showLoading(show) {
            if(tableLoader) tableLoader.classList.toggle('hidden', !show);
            if(tableContainer) tableContainer.classList.toggle('hidden', show);
            if(tableNoItems) tableNoItems.classList.toggle('hidden', show);
            if(tableError) tableError.classList.toggle('hidden', true);
        }

        // --- Load Branches (Admin Only) ---
        async function loadBranchesDropdown() {
            if (userRole !== 'admin') return;

            if(adminBranchFilterWrapper) adminBranchFilterWrapper.classList.remove('hidden');

            try {
                const response = await fetch('api/manage/branches_crud.php');
                if (!response.ok) throw new Error('Failed to load branches');
                
                allBranches = await response.json();

                if (allBranches.length > 0 && filterBranch) {
                    filterBranch.innerHTML = '<option value="">All Branches</option>';
                    allBranches.forEach(branch => {
                        const option = new Option(branch.branch_name, branch.branch_id);
                        filterBranch.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading branches:', error);
                if(filterBranch) filterBranch.innerHTML = '<option value="">Error loading</option>';
            }
        }

        // --- Load Sales Table ---
        async function loadSalesTable() {
            showLoading(true);
            if(tableBody) tableBody.innerHTML = '';
            if(tableError) tableError.classList.add('hidden');

            const startDate = filterStartDate ? filterStartDate.value : '';
            const endDate = filterEndDate ? filterEndDate.value : '';
            let branchId = null;

            if (userRole === 'admin' && filterBranch) {
                branchId = filterBranch.value; 
            }

            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            if (branchId) params.append('branch_id', branchId);

            try {
                const response = await fetch(`api/sales/read_sales.php?${params.toString()}`);
                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.error || 'Failed to fetch sales data');
                }
                const sales = await response.json();

                if (sales.length === 0) {
                    if(tableNoItems) tableNoItems.classList.remove('hidden');
                } else {
                    if(tableNoItems) tableNoItems.classList.add('hidden');
                    
                    sales.forEach(sale => {
                        const totalAmount = parseFloat(sale.total_amount || 0);
                        const itemsSold = parseInt(sale.total_items_sold || 0);
                        const isReversed = sale.is_reversed == 1;
                        
                        const row = document.createElement('tr');
                        row.classList.add('hover:bg-gray-50');
                        if (isReversed) {
                             row.classList.add('bg-red-50', 'text-gray-500');
                        }

                        // Actions Column: Hide View Bill if reversed
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${new Date(sale.sale_date).toLocaleString()}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#${sale.sale_id}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${itemsSold}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold ${isReversed ? 'text-red-700 line-through' : 'text-green-700'}">
                                LKR ${totalAmount.toFixed(2)}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${sale.branch_name || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${sale.user_name || 'System'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex gap-2">
                                ${!isReversed ? 
                                    `<a href="invoice.php?sale_id=${sale.sale_id}" class="text-blue-600 hover:text-blue-900 p-1" title="View Bill">
                                        <i class="fas fa-eye"></i>
                                    </a>` : ''}
                                ${userRole === 'admin' && !isReversed ? 
                                    `<button class="reverse-btn text-red-600 hover:text-red-900 p-1" 
                                             data-id="${sale.sale_id}" 
                                             data-total="${totalAmount.toFixed(2)}"
                                             data-items="${itemsSold}"
                                             title="Reverse Full Sale">
                                        <i class="fas fa-undo"></i>
                                    </button>`
                                    : ''}
                                ${isReversed ? '<span class="text-red-700 text-xs font-bold py-1">REVERSED</span>' : ''}
                            </td>
                        `;
                        if(tableBody) tableBody.appendChild(row);
                    });
                    if(tableContainer) tableContainer.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error fetching sales:', error);
                if(tableError) {
                    tableError.textContent = `Error loading sales: ${error.message}`;
                    tableError.classList.remove('hidden');
                }
            } finally {
                showLoading(false);
            }
        }
        
        // --- Event Listeners ---

        if (applyFiltersBtn) {
             applyFiltersBtn.addEventListener('click', loadSalesTable);
        }

        // Reversal Button Click (Event Delegation)
        if(tableBody) {
            tableBody.addEventListener('click', (e) => {
                const reverseBtn = e.target.closest('.reverse-btn');
                if (reverseBtn && userRole === 'admin') {
                    e.preventDefault(); 
                    e.stopPropagation();
                    
                    saleToReverse = {
                        sale_id: reverseBtn.dataset.id,
                        total_amount: reverseBtn.dataset.total,
                        total_items_sold: reverseBtn.dataset.items
                    };
                    
                    if(reverseId) reverseId.textContent = saleToReverse.sale_id;
                    if(reverseTotal) reverseTotal.textContent = `LKR ${saleToReverse.total_amount}`;
                    if(reverseItems) reverseItems.textContent = saleToReverse.total_items_sold;
                    if(reverseSaleId) reverseSaleId.value = saleToReverse.sale_id;
                    
                    if(reversalNotes) reversalNotes.value = '';
                    if(reversalError) reversalError.classList.add('hidden');
                    
                    openModal(reversalModal);
                }
            });
        }

        // Reversal Form Submission
        if (reversalForm) {
            reversalForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                if (!reversalNotes.value.trim()) {
                    reversalError.textContent = 'Reason for reversal is required.';
                    reversalError.classList.remove('hidden');
                    return;
                }

                confirmReversalBtn.disabled = true;
                confirmReversalBtn.textContent = 'Processing...';
                reversalError.classList.add('hidden');

                try {
                    const response = await fetch('api/sales/reverse_sale.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            sale_id: reverseSaleId.value, 
                            reversal_notes: reversalNotes.value 
                        })
                    });

                    const result = await response.json();
                    
                    if (!response.ok) {
                        throw new Error(result.error || 'Failed to reverse sale.');
                    }
                    
                    closeModal(reversalModal);
                    
                    if(tableError) {
                        tableError.textContent = result.message;
                        tableError.classList.remove('hidden', 'text-red-500');
                        tableError.classList.add('text-green-700', 'bg-green-100', 'p-3', 'rounded');
                    }
                    
                    await loadSalesTable(); 
                    
                    setTimeout(() => {
                        if(tableError) {
                            tableError.classList.add('hidden', 'text-red-500');
                            tableError.classList.remove('text-green-700', 'bg-green-100', 'p-3', 'rounded');
                            tableError.textContent = '';
                        }
                    }, 4000);

                } catch (error) {
                    console.error('Reversal Error:', error);
                    reversalError.textContent = error.message;
                    reversalError.classList.remove('hidden');
                } finally {
                    confirmReversalBtn.disabled = false;
                    confirmReversalBtn.textContent = 'Confirm Reversal';
                }
            });
        }

        if (cancelReversalBtn) {
            cancelReversalBtn.addEventListener('click', () => closeModal(reversalModal));
        }


        // --- Initialization ---
        
        if (userRole === 'admin') {
            loadBranchesDropdown(); 
        }

        loadSalesTable(); 
        
        // Helpers for Modal
        function openModal(el) {
            if(!el) return;
            el.classList.remove('hidden');
            const content = el.querySelector('.modal-content');
            if(content) content.classList.remove('-translate-y-10');
            el.classList.remove('opacity-0', 'visibility-hidden');
        }

        function closeModal(el) {
            if(!el) return;
            el.classList.add('opacity-0', 'visibility-hidden');
            const content = el.querySelector('.modal-content');
            if(content) content.classList.add('-translate-y-10');
            setTimeout(() => el.classList.add('hidden'), 250);
        }
    }
});