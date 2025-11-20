document.addEventListener('DOMContentLoaded', () => {

    // Check if we are on the branches page
    if (document.getElementById('branches-table-body')) {
        
        // --- Elements ---
        const tableBody = document.getElementById('branches-table-body');
        const tableLoader = document.getElementById('table-loader');
        const tableContainer = document.getElementById('branches-table-container');
        const tableNoItems = document.getElementById('table-no-items');
        
        const addBranchBtn = document.getElementById('add-branch-btn');
        const branchModal = document.getElementById('branch-modal');
        const branchForm = document.getElementById('branch-form');
        const modalTitle = document.getElementById('modal-title');
        const branchIdInput = document.getElementById('branch-id');
        const branchNameInput = document.getElementById('branch-name');
        const formError = document.getElementById('form-error');
        const cancelBtn = document.getElementById('cancel-btn');
        const saveBtn = document.getElementById('save-btn');

        const deleteModal = document.getElementById('delete-modal');
        const deleteBranchName = document.getElementById('delete-branch-name');
        const deleteError = document.getElementById('delete-error');
        const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');

        let allBranches = [];
        let branchIdToDelete = null;

        // --- Load Table ---
        async function loadBranchesTable() {
            tableLoader.classList.remove('hidden');
            tableContainer.classList.add('hidden');
            tableNoItems.classList.add('hidden');

            try {
                const response = await fetch('api/manage/branches_crud.php');
                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.error || 'Failed to fetch branches');
                }
                allBranches = await response.json();

                tableBody.innerHTML = '';
                if (allBranches.length === 0) {
                    tableNoItems.classList.remove('hidden');
                } else {
                    allBranches.forEach(branch => {
                        const row = `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${branch.branch_name}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="edit-btn text-blue-600 hover:text-blue-900" data-id="${branch.branch_id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-btn text-red-600 hover:text-red-900 ml-3" data-id="${branch.branch_id}" data-name="${branch.branch_name}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
                    });
                    tableContainer.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error loading branches:', error);
                tableBody.innerHTML = `<tr><td colspan="2" class="text-center text-red-500 p-4">Error: ${error.message}</td></tr>`;
                tableContainer.classList.remove('hidden');
            } finally {
                tableLoader.classList.add('hidden');
            }
        }

        // --- Event Listeners ---

        // Add Branch Button
        addBranchBtn.addEventListener('click', () => {
            branchForm.reset();
            branchIdInput.value = '';
            modalTitle.textContent = 'Add New Branch';
            openModal(branchModal);
        });

        // Edit Button (Event Delegation)
        tableBody.addEventListener('click', (e) => {
            const editButton = e.target.closest('.edit-btn');
            if (editButton) {
                const branchId = editButton.dataset.id;
                const branch = allBranches.find(b => b.branch_id == branchId);
                if (branch) {
                    branchForm.reset();
                    modalTitle.textContent = 'Edit Branch';
                    branchIdInput.value = branch.branch_id;
                    branchNameInput.value = branch.branch_name;
                    openModal(branchModal);
                }
            }
        });

        // Delete Button (Event Delegation)
        tableBody.addEventListener('click', (e) => {
            const deleteButton = e.target.closest('.delete-btn');
            if (deleteButton) {
                branchIdToDelete = deleteButton.dataset.id;
                deleteBranchName.textContent = deleteButton.dataset.name;
                deleteError.classList.add('hidden');
                openModal(deleteModal);
            }
        });

        // Form Submit (Add/Edit)
        branchForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
            formError.classList.add('hidden');

            const formData = new FormData(branchForm);
            const branchData = Object.fromEntries(formData.entries());
            const isEditing = branchData.branch_id;

            const url = 'api/manage/branches_crud.php';
            const method = isEditing ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(branchData)
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.error || 'Failed to save branch');
                }
                closeModal(branchModal);
                await loadBranchesTable();
            } catch (error) {
                formError.textContent = error.message;
                formError.classList.remove('hidden');
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Branch';
            }
        });

        // Delete Confirmation
        confirmDeleteBtn.addEventListener('click', async () => {
            if (!branchIdToDelete) return;

            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.textContent = 'Deleting...';
            deleteError.classList.add('hidden');

            try {
                const response = await fetch(`api/manage/branches_crud.php?id=${branchIdToDelete}`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.error || 'Failed to delete branch');
                }
                closeModal(deleteModal);
                await loadBranchesTable();
            } catch (error) {
                deleteError.textContent = error.message;
                deleteError.classList.remove('hidden');
            } finally {
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.textContent = 'Delete';
                branchIdToDelete = null;
            }
        });

        // Modal Cancel Buttons
        cancelBtn.addEventListener('click', () => closeModal(branchModal));
        cancelDeleteBtn.addEventListener('click', () => closeModal(deleteModal));

        // --- Initial Load ---
        loadBranchesTable();
    }
});