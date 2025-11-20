document.addEventListener('DOMContentLoaded', () => {

    if (document.getElementById('expenses-page-container')) {
        
        // --- Elements ---
        const container = document.getElementById('expenses-page-container');
        let userRole = container.dataset.role;
        
        // Fallback: Check sessionStorage if data attribute is missing or default
        const storedUser = JSON.parse(sessionStorage.getItem('currentUser'));
        if (storedUser && storedUser.role) {
            // If PHP said staff but storage says admin, trust storage
            if (userRole !== 'admin' && storedUser.role === 'admin') {
                userRole = 'admin';
            }
        }

        const tableBody = document.getElementById('expenses-table-body');
        const tableLoader = document.getElementById('table-loader');
        const tableContainer = document.getElementById('expenses-table-container');
        const tableNoItems = document.getElementById('table-no-items');
        const tableError = document.getElementById('table-error');
        
        // Filter Elements (Main Page)
        const listFilterWrapper = document.getElementById('list-filter-wrapper');
        const filterBranch = document.getElementById('filter-branch');

        // Modal Elements
        const addBtn = document.getElementById('add-expense-btn');
        const modal = document.getElementById('expense-modal');
        const modalTitle = document.getElementById('modal-title');
        const form = document.getElementById('expense-form');
        const idInput = document.getElementById('expense-id');
        const dateInput = document.getElementById('expense-date');
        const categoryInput = document.getElementById('expense-category');
        const amountInput = document.getElementById('expense-amount');
        const descInput = document.getElementById('expense-description');
        
        const branchWrapper = document.getElementById('expense-branch-wrapper');
        const branchSelect = document.getElementById('expense-branch-select');
        
        const cancelBtn = document.getElementById('cancel-expense-btn');
        const saveBtn = document.getElementById('save-expense-btn');
        const formError = document.getElementById('form-error');

        const deleteModal = document.getElementById('delete-modal');
        const deleteError = document.getElementById('delete-error');
        const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');

        let expensesData = [];
        let deleteId = null;
        let branchesLoaded = false;

        // --- Initialization ---
        
        if (userRole === 'admin') {
            // 1. Show List Filter immediately
            if (listFilterWrapper) {
                listFilterWrapper.classList.remove('hidden');
                listFilterWrapper.style.display = 'block';
            }
            
            // 2. Start loading branches
            loadBranches();
        }
        
        loadExpenses();

        // --- Functions ---

        async function loadBranches() {
            if (branchesLoaded) return; // Avoid double loading

            try {
                const res = await fetch('api/manage/branches_crud.php');
                const branches = await res.json();
                
                // Reset dropdowns
                if(filterBranch) filterBranch.innerHTML = '<option value="">All Branches</option>';
                if(branchSelect) branchSelect.innerHTML = '<option value="">Select Branch</option>';

                branches.forEach(b => {
                    // Populate Main Filter
                    if(filterBranch) filterBranch.appendChild(new Option(b.branch_name, b.branch_id));
                    // Populate Modal Selector
                    if(branchSelect) branchSelect.appendChild(new Option(b.branch_name, b.branch_id));
                });
                branchesLoaded = true;
            } catch (e) { console.error('Branch load error', e); }
        }

        async function loadExpenses() {
            tableLoader.classList.remove('hidden');
            tableContainer.classList.add('hidden');
            tableNoItems.classList.add('hidden');
            tableError.classList.add('hidden');

            // Prepare URL with filter if applicable
            let url = 'api/manage/expenses_crud.php';
            if (userRole === 'admin' && filterBranch && filterBranch.value) {
                url += `?branch_id=${filterBranch.value}`;
            }

            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error('Failed to fetch expenses');
                
                expensesData = await response.json();

                if (expensesData.length === 0) {
                    tableNoItems.classList.remove('hidden');
                } else {
                    renderTable(expensesData);
                    tableContainer.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error:', error);
                tableError.textContent = error.message;
                tableError.classList.remove('hidden');
            } finally {
                tableLoader.classList.add('hidden');
            }
        }

        function renderTable(data) {
            tableBody.innerHTML = '';
            data.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.expense_date}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.category}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">LKR ${parseFloat(item.amount).toFixed(2)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.branch_name || 'N/A'}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">${item.description || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button class="edit-btn text-blue-600 hover:text-blue-900 mr-3" data-id="${item.expense_id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${userRole === 'admin' ? `
                        <button class="delete-btn text-red-600 hover:text-red-900" data-id="${item.expense_id}">
                            <i class="fas fa-trash"></i>
                        </button>` : ''}
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }

        // --- Event Listeners ---

        // Filter Change Listener
        if (filterBranch) {
            filterBranch.addEventListener('change', () => {
                loadExpenses();
            });
        }

        addBtn.addEventListener('click', () => {
            form.reset();
            idInput.value = '';
            dateInput.valueAsDate = new Date();
            modalTitle.textContent = 'Record Expense';
            formError.classList.add('hidden');

            // FIX: Force check role and reveal branch selector if Admin
            if (userRole === 'admin') {
                if (branchWrapper) branchWrapper.classList.remove('hidden');
                if (branchSelect) branchSelect.required = true;
                // Ensure branches are loaded if initial load failed
                if (!branchesLoaded) loadBranches();
            } else {
                if (branchWrapper) branchWrapper.classList.add('hidden');
                if (branchSelect) branchSelect.required = false;
            }

            openModal(modal);
        });

        tableBody.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-btn');
            const delBtn = e.target.closest('.delete-btn');

            if (editBtn) {
                const id = editBtn.dataset.id;
                const item = expensesData.find(x => x.expense_id == id);
                if (item) {
                    idInput.value = item.expense_id;
                    dateInput.value = item.expense_date;
                    categoryInput.value = item.category;
                    amountInput.value = item.amount;
                    descInput.value = item.description;
                    
                    modalTitle.textContent = 'Edit Expense';
                    formError.classList.add('hidden');

                    // Handle Branch Selector logic for Edit as well
                    if (userRole === 'admin') {
                        if (branchWrapper) branchWrapper.classList.remove('hidden');
                        if (branchSelect) {
                            branchSelect.required = true;
                            branchSelect.value = item.branch_id;
                        }
                        if (!branchesLoaded) loadBranches();
                    }

                    openModal(modal);
                }
            }

            if (delBtn) {
                deleteId = delBtn.dataset.id;
                deleteError.classList.add('hidden');
                openModal(deleteModal);
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            const isEdit = !!data.expense_id;
            const method = isEdit ? 'PUT' : 'POST';

            try {
                const res = await fetch('api/manage/expenses_crud.php', {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (!res.ok) throw new Error(result.error || 'Failed to save');

                closeModal(modal);
                loadExpenses();
            } catch (error) {
                formError.textContent = error.message;
                formError.classList.remove('hidden');
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';
            }
        });

        confirmDeleteBtn.addEventListener('click', async () => {
            if (!deleteId) return;
            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.textContent = 'Deleting...';

            try {
                const res = await fetch(`api/manage/expenses_crud.php?id=${deleteId}`, {
                    method: 'DELETE'
                });
                const result = await res.json();
                if (!res.ok) throw new Error(result.error || 'Failed to delete');

                closeModal(deleteModal);
                loadExpenses();
            } catch (error) {
                deleteError.textContent = error.message;
                deleteError.classList.remove('hidden');
            } finally {
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.textContent = 'Delete';
            }
        });

        cancelBtn.addEventListener('click', () => closeModal(modal));
        cancelDeleteBtn.addEventListener('click', () => closeModal(deleteModal));
        
        // Helper functions for modals
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