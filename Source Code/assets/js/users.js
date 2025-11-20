document.addEventListener('DOMContentLoaded', () => {

    // Check if we are on the users page
    if (document.getElementById('users-table-body')) {
        
        // --- Elements ---
        const tableBody = document.getElementById('users-table-body');
        const tableLoader = document.getElementById('table-loader');
        const tableContainer = document.getElementById('users-table-container');
        const tableNoItems = document.getElementById('table-no-items');
        
        const addUserBtn = document.getElementById('add-user-btn');
        const userModal = document.getElementById('user-modal');
        const userForm = document.getElementById('user-form');
        const modalTitle = document.getElementById('modal-title');
        const userIdInput = document.getElementById('user-id');
        const userNameInput = document.getElementById('user-name');
        const userEmailInput = document.getElementById('user-email'); // Changed from userUsernameInput
        const userRoleInput = document.getElementById('user-role');
        const userPasswordInput = document.getElementById('user-password');
        const passwordHelp = document.getElementById('password-help');
        const formError = document.getElementById('form-error');
        const cancelBtn = document.getElementById('cancel-btn');
        const saveBtn = document.getElementById('save-btn');

        // NEW: Branch elements
        const userBranchWrapper = document.getElementById('user-branch-wrapper');
        const userBranchInput = document.getElementById('user-branch');

        const deleteModal = document.getElementById('delete-modal');
        const deleteUserName = document.getElementById('delete-user-name');
        const deleteError = document.getElementById('delete-error');
        const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');

        let allUsers = [];
        let userIdToDelete = null;

        // --- Load Table ---
        async function loadUsersTable() {
            tableLoader.classList.remove('hidden');
            tableContainer.classList.add('hidden');
            tableNoItems.classList.add('hidden');

            try {
                const response = await fetch('api/manage/users_crud.php');
                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.error || 'Failed to fetch users');
                }
                allUsers = await response.json();

                tableBody.innerHTML = '';
                if (allUsers.length === 0) {
                    tableNoItems.classList.remove('hidden');
                } else {
                    allUsers.forEach(user => {
                        const roleClass = user.role === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
                        const row = `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${user.name}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.email}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${roleClass}">
                                        ${user.role}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="edit-btn text-blue-600 hover:text-blue-900" data-id="${user.user_id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-btn text-red-600 hover:text-red-900 ml-3" data-id="${user.user_id}" data-name="${user.name}">
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
                console.error('Error loading users:', error);
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500 p-4">Error: ${error.message}</td></tr>`;
                tableContainer.classList.remove('hidden');
            } finally {
                tableLoader.classList.add('hidden');
            }
        }

        // --- NEW: Load Branches into Dropdown ---
        async function loadBranchesDropdown() {
            try {
                // We can reuse the branches CRUD API to get all branches
                const response = await fetch('api/manage/branches_crud.php');
                if (!response.ok) {
                    throw new Error('Failed to load branches');
                }
                const branches = await response.json();

                if (branches.length > 0) {
                    userBranchInput.innerHTML = '<option value="">Select a branch</option>';
                    branches.forEach(branch => {
                        const option = new Option(branch.branch_name, branch.branch_id);
                        userBranchInput.appendChild(option);
                    });
                } else {
                    userBranchInput.innerHTML = '<option value="">No branches found</option>';
                }
            } catch (error) {
                console.error('Error loading branches:', error);
                userBranchInput.innerHTML = '<option value="">Error loading</option>';
            }
        }

        // --- Event Listeners ---

        // NEW: Show/Hide Branch Dropdown based on Role
        userRoleInput.addEventListener('change', () => {
            if (userRoleInput.value === 'staff') {
                userBranchWrapper.classList.remove('hidden');
                userBranchInput.required = true;
            } else {
                userBranchWrapper.classList.add('hidden');
                userBranchInput.required = false;
                userBranchInput.value = ''; // Clear selection
            }
        });

        // Add User Button
        addUserBtn.addEventListener('click', () => {
            userForm.reset();
            userIdInput.value = '';
            modalTitle.textContent = 'Add New User';
            passwordHelp.textContent = 'Required when creating a new user.';
            userPasswordInput.required = true;
            // NEW: Handle branch visibility for new user
            if (userRoleInput.value === 'staff') {
                userBranchWrapper.classList.remove('hidden');
                userBranchInput.required = true;
            } else {
                userBranchWrapper.classList.add('hidden');
                userBranchInput.required = false;
            }
            openModal(userModal);
        });

        // Edit Button (Event Delegation)
        tableBody.addEventListener('click', (e) => {
            const editButton = e.target.closest('.edit-btn');
            if (editButton) {
                const userId = editButton.dataset.id;
                const user = allUsers.find(u => u.user_id == userId);
                if (user) {
                    userForm.reset();
                    modalTitle.textContent = 'Edit User';
                    userIdInput.value = user.user_id;
                    userNameInput.value = user.name;
                    userEmailInput.value = user.email; // Changed from userUsernameInput
                    userRoleInput.value = user.role;
                    passwordHelp.textContent = 'Leave blank to keep password unchanged.';
                    userPasswordInput.required = false;

                    // NEW: Handle branch visibility and selection for existing user
                    if (user.role === 'staff') {
                        userBranchWrapper.classList.remove('hidden');
                        userBranchInput.required = true;
                        userBranchInput.value = user.branch_id || ''; // Set branch
                    } else {
                        userBranchWrapper.classList.add('hidden');
                        userBranchInput.required = false;
                        userBranchInput.value = ''; // Clear selection
                    }
                    
                    openModal(userModal);
                }
            }
        });

        // Delete Button (Event Delegation)
        tableBody.addEventListener('click', (e) => {
            const deleteButton = e.target.closest('.delete-btn');
            if (deleteButton) {
                userIdToDelete = deleteButton.dataset.id;
                deleteUserName.textContent = deleteButton.dataset.name;
                deleteError.classList.add('hidden');
                openModal(deleteModal);
            }
        });

        // Form Submit (Add/Edit)
        userForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
            formError.classList.add('hidden');

            const formData = new FormData(userForm);
            const userData = Object.fromEntries(formData.entries());
            const isEditing = userData.user_id;

            // Handle password: if it's empty, remove it so we don't send an empty string
            if (!userData.password) {
                delete userData.password;
            }

            // NEW: Handle branch_id based on role
            if (userData.role === 'admin') {
                userData.branch_id = null; // Admins are not assigned to a branch
            } else {
                userData.branch_id = userData.branch_id || null; // Set to null if 'Select a branch' is chosen
            }

            const url = 'api/manage/users_crud.php';
            const method = isEditing ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(userData)
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.error || 'Failed to save user');
                }
                closeModal(userModal);
                await loadUsersTable();
            } catch (error) {
                formError.textContent = error.message;
                formError.classList.remove('hidden');
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save User';
            }
        });

        // Delete Confirmation
        confirmDeleteBtn.addEventListener('click', async () => {
            if (!userIdToDelete) return;

            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.textContent = 'Deleting...';
            deleteError.classList.add('hidden');

            try {
                const response = await fetch(`api/manage/users_crud.php?id=${userIdToDelete}`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.error || 'Failed to delete user');
                }
                closeModal(deleteModal);
                await loadUsersTable();
            } catch (error) {
                deleteError.textContent = error.message;
                deleteError.classList.remove('hidden');
            } finally {
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.textContent = 'Delete';
                userIdToDelete = null;
            }
        });

        // Modal Cancel Buttons
        cancelBtn.addEventListener('click', () => closeModal(userModal));
        cancelDeleteBtn.addEventListener('click', () => closeModal(deleteModal));

        // --- Initial Load ---
        loadBranchesDropdown(); // NEW: Load branches on page load
        loadUsersTable();
    }
});