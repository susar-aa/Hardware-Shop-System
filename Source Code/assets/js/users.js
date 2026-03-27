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
        const userEmailInput = document.getElementById('user-email'); 
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


        // --- Event Listeners (Attached Immediately) ---

        // Add User Button
        if (addUserBtn) {
            addUserBtn.addEventListener('click', () => {
                userForm.reset();
                userIdInput.value = '';
                modalTitle.textContent = 'Add New User';
                passwordHelp.textContent = 'Required when creating a new user.';
                userPasswordInput.required = true;
                
                // Handle branch visibility for new user based on current role selection (default)
                if (userRoleInput.value === 'staff') {
                    if(userBranchWrapper) userBranchWrapper.classList.remove('hidden');
                    if(userBranchInput) userBranchInput.required = true;
                } else {
                    if(userBranchWrapper) userBranchWrapper.classList.add('hidden');
                    if(userBranchInput) userBranchInput.required = false;
                }
                
                openModal(userModal);
            });
        }

        // Show/Hide Branch Dropdown based on Role
        if (userRoleInput) {
            userRoleInput.addEventListener('change', () => {
                if (userRoleInput.value === 'staff') {
                    if(userBranchWrapper) userBranchWrapper.classList.remove('hidden');
                    if(userBranchInput) userBranchInput.required = true;
                } else {
                    if(userBranchWrapper) userBranchWrapper.classList.add('hidden');
                    if(userBranchInput) userBranchInput.required = false;
                    if(userBranchInput) userBranchInput.value = ''; // Clear selection
                }
            });
        }

        // Edit/Delete Button (Event Delegation)
        if (tableBody) {
            tableBody.addEventListener('click', (e) => {
                const editBtn = e.target.closest('.edit-btn');
                const delBtn = e.target.closest('.delete-btn');

                if (editBtn) {
                    const userId = editBtn.dataset.id;
                    const user = allUsers.find(u => u.user_id == userId);
                    if (user) {
                        userForm.reset();
                        modalTitle.textContent = 'Edit User';
                        userIdInput.value = user.user_id;
                        userNameInput.value = user.name;
                        userEmailInput.value = user.email;
                        userRoleInput.value = user.role;
                        passwordHelp.textContent = 'Leave blank to keep password unchanged.';
                        userPasswordInput.required = false;

                        // Handle branch visibility and selection for existing user
                        if (user.role === 'staff') {
                            if(userBranchWrapper) userBranchWrapper.classList.remove('hidden');
                            if(userBranchInput) {
                                userBranchInput.required = true;
                                userBranchInput.value = user.branch_id || '';
                            }
                        } else {
                            if(userBranchWrapper) userBranchWrapper.classList.add('hidden');
                            if(userBranchInput) {
                                userBranchInput.required = false;
                                userBranchInput.value = '';
                            }
                        }
                        
                        openModal(userModal);
                    }
                }

                if (delBtn) {
                    userIdToDelete = delBtn.dataset.id;
                    deleteUserName.textContent = delBtn.dataset.name;
                    deleteError.classList.add('hidden');
                    openModal(deleteModal);
                }
            });
        }

        // Form Submit (Add/Edit)
        if (userForm) {
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

                // Handle branch_id based on role
                if (userData.role === 'admin') {
                    userData.branch_id = null;
                } else {
                    userData.branch_id = userData.branch_id || null;
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
        }

        // Delete Confirmation
        if (confirmDeleteBtn) {
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
        }

        // Modal Cancel Buttons
        if (cancelBtn) cancelBtn.addEventListener('click', () => closeModal(userModal));
        if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', () => closeModal(deleteModal));


        // --- Load Data ---
        loadBranchesDropdown(); 
        loadUsersTable();


        // --- Functions ---

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

        async function loadBranchesDropdown() {
            try {
                const response = await fetch('api/manage/branches_crud.php');
                if (!response.ok) {
                    throw new Error('Failed to load branches');
                }
                const branches = await response.json();

                if (branches.length > 0 && userBranchInput) {
                    userBranchInput.innerHTML = '<option value="">Select a branch</option>';
                    branches.forEach(branch => {
                        const option = new Option(branch.branch_name, branch.branch_id);
                        userBranchInput.appendChild(option);
                    });
                } else if (userBranchInput) {
                    userBranchInput.innerHTML = '<option value="">No branches found</option>';
                }
            } catch (error) {
                console.error('Error loading branches:', error);
                if(userBranchInput) userBranchInput.innerHTML = '<option value="">Error loading</option>';
            }
        }

        // Helper functions for modals
        function openModal(el) {
            el.classList.remove('hidden');
            el.querySelector('.modal-content').classList.remove('-translate-y-10');
            el.classList.remove('opacity-0', 'visibility-hidden');
        }
        function closeModal(el) {
            el.classList.add('opacity-0', 'visibility-hidden');
            el.querySelector('.modal-content').classList.add('-translate-y-10');
            setTimeout(() => {
                el.classList.add('hidden');
                if(formError) formError.classList.add('hidden');
                if(deleteError) deleteError.classList.add('hidden');
            }, 250);
        }
    }
});