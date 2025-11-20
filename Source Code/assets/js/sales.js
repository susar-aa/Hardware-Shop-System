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
            tableLoader.classList.toggle('hidden', !show);
            tableContainer.classList.toggle('hidden', show);
            tableNoItems.classList.toggle('hidden', show);
            tableError.classList.toggle('hidden', true);
        }

        // --- Load Branches (Admin Only) ---
        async function loadBranchesDropdown() {
            if (userRole !== 'admin') return;

            // Show the branch filter wrapper for Admins
            adminBranchFilterWrapper.classList.remove('hidden');

            try {
                const response = await fetch('api/manage/branches_crud.php');
                if (!response.ok) throw new Error('Failed to load branches');
                
                allBranches = await response.json();

                if (allBranches.length > 0) {
                    filterBranch.innerHTML = '<option value="">All Branches</option>';
                    allBranches.forEach(branch => {
                        const option = new Option(branch.branch_name, branch.branch_id);
                        filterBranch.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading branches:', error);
                filterBranch.innerHTML = '<option value="">Error loading</option>';
            }
        }

        // --- Load Sales Table ---
        async function loadSalesTable() {
            showLoading(true);
            tableBody.innerHTML = '';
            tableError.classList.add('hidden');

            const startDate = filterStartDate.value;
            const endDate = filterEndDate.value;
            let branchId = null;

            if (userRole === 'admin') {
                branchId = filterBranch.value; // Admin selects from dropdown
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
                    tableNoItems.classList.remove('hidden');
                } else {
                    
                    sales.forEach(sale => {
                        // Data processing for display
                        const totalAmount = parseFloat(sale.total_amount || 0);
                        const itemsSold = parseInt(sale.total_items_sold || 0);
                        const isReversed = sale.is_reversed == 1;
                        
                        const row = document.createElement('tr');
                        row.classList.add('hover:bg-gray-50');
                        if (isReversed) {
                             row.classList.add('bg-red-50', 'text-gray-500', 'line-through');
                        }

                        // Populate row using correct API field names (sale_date, sale_id, total_amount)
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${new Date(sale.sale_date).toLocaleString()}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${sale.sale_id}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${itemsSold}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold ${isReversed ? 'text-red-700' : 'text-green-700'}">
                                LKR ${totalAmount.toFixed(2)}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${sale.branch_name || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${sale.user_name || 'System'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                ${userRole === 'admin' && !isReversed ? 
                                    `<button class="reverse-btn text-red-600 hover:text-red-900" 
                                             data-id="${sale.sale_id}" 
                                             data-total="${totalAmount.toFixed(2)}"
                                             data-items="${itemsSold}">
                                        Reverse
                                    </button>`
                                    : (isReversed ? '<span class="text-red-700 font-semibold">REVERSED</span>' : '')}
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                    tableContainer.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error fetching sales:', error);
                tableError.textContent = `Error loading sales: ${error.message}`;
                tableError.classList.remove('hidden');
            } finally {
                showLoading(false);
            }
        }
        
        // --- Event Listeners ---

        // Filter Button Click
        applyFiltersBtn.addEventListener('click', loadSalesTable);

        // Reversal Button Click (Event Delegation)
        tableBody.addEventListener('click', (e) => {
            const reverseBtn = e.target.closest('.reverse-btn');
            if (reverseBtn && userRole === 'admin') {
                saleToReverse = {
                    sale_id: reverseBtn.dataset.id,
                    total_amount: reverseBtn.dataset.total,
                    total_items_sold: reverseBtn.dataset.items
                };
                
                // Populate modal with data from button attributes
                reverseId.textContent = saleToReverse.sale_id;
                reverseTotal.textContent = `LKR ${saleToReverse.total_amount}`;
                reverseItems.textContent = saleToReverse.total_items_sold;
                reverseSaleId.value = saleToReverse.sale_id;
                
                reversalNotes.value = '';
                reversalError.classList.add('hidden');
                openModal(reversalModal);
            }
        });

        // Reversal Form Submission
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
                
                // Use the existing error area to show a green success message temporarily
                tableError.textContent = result.message;
                tableError.classList.remove('hidden', 'text-red-500');
                tableError.classList.add('text-green-700', 'bg-green-100', 'p-3', 'rounded');
                
                await loadSalesTable(); // Refresh the table
                
                // Clear success message after 4 seconds
                setTimeout(() => {
                    tableError.classList.add('hidden', 'text-red-500');
                    tableError.classList.remove('text-green-700', 'bg-green-100', 'p-3', 'rounded');
                    tableError.textContent = '';
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

        cancelReversalBtn.addEventListener('click', () => closeModal(reversalModal));


        // --- Initialization ---
        
        if (userRole === 'admin') {
            loadBranchesDropdown(); // Only load filter branches for admin
        }

        loadSalesTable(); // Initial data load
    }
});