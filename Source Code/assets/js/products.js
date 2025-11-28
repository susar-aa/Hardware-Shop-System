document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('products-page-container')) return;

    const container = document.getElementById('products-page-container');
    const userRole = container.dataset.role;

    // Elements
    const exportBtn = document.getElementById('export-csv-btn');
    const importBtn = document.getElementById('import-csv-btn');
    const csvFileInput = document.getElementById('csv-file-input');
    
    const tabProducts = document.getElementById('tab-products');
    const tabCategories = document.getElementById('tab-categories');
    const viewProducts = document.getElementById('view-products');
    const viewCategories = document.getElementById('view-categories');

    const addProductBtn = document.getElementById('add-product-btn');
    const productModal = document.getElementById('product-modal');
    const productForm = document.getElementById('product-form');
    const productTableBody = document.getElementById('products-table-body');
    const productLoader = document.getElementById('table-loader');
    const productContainer = document.getElementById('products-table-container');
    const productNoItems = document.getElementById('table-no-items');
    const browseMediaBtn = document.getElementById('browse-media-btn');
    const productVisible = document.getElementById('product-visible');
    
    // Get ID input reference (will also re-fetch in submit to be safe)
    const productIdInput = document.getElementById('product-id'); 

    const addCategoryBtn = document.getElementById('add-category-btn');
    const catModal = document.getElementById('category-modal');
    const catForm = document.getElementById('category-form');
    const catTableBody = document.getElementById('cat-table-body');
    const catLoader = document.getElementById('cat-loader');
    const catNoItems = document.getElementById('cat-no-items');
    const catIdInput = document.getElementById('cat-id');
    const catNameInput = document.getElementById('cat-name');
    
    const deleteModal = document.getElementById('delete-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const deleteError = document.getElementById('delete-error');
    const formError = document.getElementById('form-error');

    let allProducts = [];
    let allCategories = [];
    let deleteTarget = null;

    // --- Initialization ---
    loadProductCategories(); 
    loadProductsTable();     

    // --- EXPORT LOGIC (Updated to use Category Name) ---
    if(exportBtn) {
        exportBtn.addEventListener('click', () => {
            if (!allProducts || allProducts.length === 0) {
                alert("No products data available to export yet.");
                return;
            }
            // Header now explicitly says "Category Name"
            const headers = ["Name", "Code", "Category Name", "Price", "Cost", "Reorder Level", "Description", "Image URL", "Is Visible (1=Yes, 0=No)"];
            const rows = [headers.join(",")];
            
            allProducts.forEach(p => {
                const row = [
                    `"${p.name.replace(/"/g, '""')}"`,
                    `"${p.product_code || ''}"`,
                    // FIX: Export category_name instead of category_id
                    `"${(p.category_name || '').replace(/"/g, '""')}"`,
                    p.price || 0,
                    p.cost || 0,
                    p.reorder_level || 0,
                    `"${(p.description || '').replace(/"/g, '""').replace(/(\r\n|\n|\r)/gm, ' ')}"`,
                    p.image || '',
                    p.is_visible || 1
                ];
                rows.push(row.join(","));
            });

            const csvString = rows.join("\n");
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", "products_export_" + new Date().toISOString().slice(0,10) + ".csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }

    // --- IMPORT LOGIC ---
    if(importBtn && csvFileInput) {
        importBtn.addEventListener('click', () => {
            csvFileInput.value = ''; 
            csvFileInput.click();
        });

        csvFileInput.addEventListener('change', async () => {
            const file = csvFileInput.files[0];
            if(!file) return;

            const formData = new FormData();
            formData.append('csv_file', file);

            const originalText = importBtn.innerHTML;
            importBtn.disabled = true;
            importBtn.textContent = 'Importing...';

            try {
                const res = await fetch('api/products/import.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (!res.ok) throw new Error(result.error || 'Import failed');
                alert(result.message);
                loadProductsTable(); 
            } catch(err) {
                console.error(err);
                alert("Import Error: " + err.message);
            } finally {
                importBtn.disabled = false;
                importBtn.innerHTML = originalText;
            }
        });
    }

    // --- Tab Switching ---
    tabProducts.addEventListener('click', () => switchTab('products'));
    tabCategories.addEventListener('click', () => switchTab('categories'));

    function switchTab(tab) {
        if (tab === 'products') {
            tabProducts.classList.add('text-blue-600', 'border-blue-500');
            tabProducts.classList.remove('text-gray-500', 'border-transparent');
            tabCategories.classList.remove('text-blue-600', 'border-blue-500');
            tabCategories.classList.add('text-gray-500', 'border-transparent');
            viewProducts.classList.remove('hidden');
            viewCategories.classList.add('hidden');
            if (userRole === 'admin') {
                if(addProductBtn) addProductBtn.classList.remove('hidden');
                if(addCategoryBtn) addCategoryBtn.classList.add('hidden');
            }
        } else {
            tabCategories.classList.add('text-blue-600', 'border-blue-500');
            tabCategories.classList.remove('text-gray-500', 'border-transparent');
            tabProducts.classList.remove('text-blue-600', 'border-blue-500');
            tabProducts.classList.add('text-gray-500', 'border-transparent');
            viewCategories.classList.remove('hidden');
            viewProducts.classList.add('hidden');
            loadCategoriesTable();
            if (userRole === 'admin') {
                if(addCategoryBtn) addCategoryBtn.classList.remove('hidden');
                if(addProductBtn) addProductBtn.classList.add('hidden');
            }
        }
    }

    // ==========================================
    // PRODUCTS LOGIC
    // ==========================================
    
    async function loadProductsTable() {
        if(productLoader) productLoader.classList.remove('hidden');
        if(productContainer) productContainer.classList.add('hidden');
        if(productNoItems) productNoItems.classList.add('hidden');

        try {
            const response = await fetch('api/products/crud.php');
            if (!response.ok) throw new Error('Failed to load products');
            allProducts = await response.json();
            
            if(productTableBody) productTableBody.innerHTML = '';
            if (allProducts.length === 0) {
                if(productNoItems) productNoItems.classList.remove('hidden');
            } else {
                allProducts.forEach(product => {
                    const isVisible = product.is_visible == 1;
                    const statusBadge = isVisible 
                        ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Visible</span>' 
                        : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Hidden</span>';

                    const row = `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${product.name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${product.product_code || '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${product.category_name || 'None'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">LKR ${parseFloat(product.price||0).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">LKR ${parseFloat(product.cost||0).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${statusBadge}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium admin-only ${userRole !== 'admin' ? 'hidden' : ''}">
                                <button class="edit-prod-btn text-blue-600 hover:text-blue-900" data-id="${product.product_id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="delete-prod-btn text-red-600 hover:text-red-900 ml-3" data-id="${product.product_id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                    if(productTableBody) productTableBody.innerHTML += row;
                });
                if(productContainer) productContainer.classList.remove('hidden');
            }
        } catch (error) {
            console.error(error);
            if(productTableBody) productTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-red-500 p-4">Error loading products</td></tr>`;
            if(productContainer) productContainer.classList.remove('hidden');
        } finally {
            if(productLoader) productLoader.classList.add('hidden');
        }
    }

    // Add Product Button
    if(addProductBtn) addProductBtn.addEventListener('click', () => {
        productForm.reset();
        if(productIdInput) productIdInput.value = ''; // Clear hidden ID explicitly
        document.getElementById('modal-title').textContent = 'Add New Product';
        if(productVisible) productVisible.checked = true;
        loadProductCategories();
        openModal(productModal);
    });

    // Edit/Delete Product Delegation
    if(productTableBody) productTableBody.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-prod-btn');
        const delBtn = e.target.closest('.delete-prod-btn');

        if (editBtn) {
            const id = editBtn.dataset.id;
            const product = allProducts.find(p => p.product_id == id);
            if (product) {
                productForm.reset();
                
                // FIX: Set hidden ID
                if(productIdInput) productIdInput.value = product.product_id;
                
                document.getElementById('modal-title').textContent = 'Edit Product';
                document.getElementById('product-name').value = product.name;
                document.getElementById('product-code').value = product.product_code;
                document.getElementById('product-category').value = product.category_id || '';
                document.getElementById('product-description').value = product.description;
                document.getElementById('product-price').value = product.price;
                document.getElementById('product-cost').value = product.cost;
                document.getElementById('product-reorder-level').value = product.reorder_level;
                document.getElementById('product-image').value = product.image;
                if(productVisible) productVisible.checked = (product.is_visible == 1);
                
                openModal(productModal);
            }
        }

        if (delBtn) {
            deleteTarget = { type: 'product', id: delBtn.dataset.id };
            document.getElementById('delete-type-text').textContent = 'product';
            document.getElementById('delete-warn-text').classList.add('hidden');
            deleteError.classList.add('hidden');
            openModal(deleteModal);
        }
    });

    // Submit Product
    if(productForm) productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if(formError) formError.classList.add('hidden');

        const formData = new FormData(productForm);
        const data = Object.fromEntries(formData.entries());
        if(!data.category_id) data.category_id = null;
        
        data.is_visible = productVisible.checked ? 1 : 0;

        // FIX: Robust Check for Product ID to determine Edit vs Create
        let pid = data.product_id;
        if(!pid) {
            const currentIdInput = document.getElementById('product-id');
            if(currentIdInput && currentIdInput.value) {
                pid = currentIdInput.value;
                data.product_id = pid; // Ensure it's in the payload
            }
        }

        const isEdit = (pid !== "" && pid !== null && pid !== undefined);
        const method = isEdit ? 'PUT' : 'POST';
        
        console.log("Submitting Product:", method, data); // Debug log

        try {
            const res = await fetch('api/products/crud.php', {
                method: method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await res.json();
            
            if(!res.ok) throw new Error(result.error || 'Save failed');
            
            closeModal(productModal);
            loadProductsTable();
        } catch(err) {
            console.error("Save error:", err);
            if(formError) {
                formError.textContent = err.message;
                formError.classList.remove('hidden');
            } else {
                alert("Error: " + err.message);
            }
        }
    });

    // ==========================================
    // CATEGORIES LOGIC
    // ==========================================

    async function loadCategoriesTable() {
        if(catLoader) catLoader.classList.remove('hidden');
        if(catTableBody) catTableBody.innerHTML = '';
        
        try {
            const response = await fetch('api/manage/categories_crud.php');
            if (!response.ok) throw new Error('Failed');
            allCategories = await response.json();

            if (allCategories.length === 0) {
                if(catNoItems) catNoItems.classList.remove('hidden');
            } else {
                if(catNoItems) catNoItems.classList.add('hidden');
                allCategories.forEach(cat => {
                    const row = `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${cat.category_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium admin-only ${userRole !== 'admin' ? 'hidden' : ''}">
                                <button class="edit-cat-btn text-blue-600 hover:text-blue-900" data-id="${cat.category_id}" data-name="${cat.category_name}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="delete-cat-btn text-red-600 hover:text-red-900 ml-3" data-id="${cat.category_id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                    if(catTableBody) catTableBody.innerHTML += row;
                });
            }
        } catch (e) { console.error(e); } finally { if(catLoader) catLoader.classList.add('hidden'); }
    }

    if(addCategoryBtn) addCategoryBtn.addEventListener('click', () => {
        catForm.reset();
        catIdInput.value = '';
        document.getElementById('cat-modal-title').textContent = 'Add New Category';
        openModal(catModal);
    });

    if(catTableBody) catTableBody.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-cat-btn');
        const delBtn = e.target.closest('.delete-cat-btn');

        if (editBtn) {
            catIdInput.value = editBtn.dataset.id;
            catNameInput.value = editBtn.dataset.name;
            document.getElementById('cat-modal-title').textContent = 'Edit Category';
            openModal(catModal);
        }

        if (delBtn) {
            deleteTarget = { type: 'category', id: delBtn.dataset.id };
            document.getElementById('delete-type-text').textContent = 'category';
            document.getElementById('delete-warn-text').classList.remove('hidden');
            deleteError.classList.add('hidden');
            openModal(deleteModal);
        }
    });

    if(catForm) catForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(catForm);
        const data = Object.fromEntries(formData.entries());
        const isEdit = !!data.category_id;
        
        try {
            const res = await fetch('api/manage/categories_crud.php', {
                method: isEdit ? 'PUT' : 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if(!res.ok) throw new Error(result.error);

            closeModal(catModal);
            loadCategoriesTable();
            loadProductCategories(); 
        } catch (err) {
            const errDiv = document.getElementById('cat-form-error');
            errDiv.textContent = err.message;
            errDiv.classList.remove('hidden');
        }
    });

    async function loadProductCategories() {
        try {
            const response = await fetch('api/manage/categories_crud.php');
            if (!response.ok) return;
            const cats = await response.json();
            const select = document.getElementById('product-category');
            if(select) {
                select.innerHTML = '<option value="">Select Category</option>';
                cats.forEach(c => select.appendChild(new Option(c.category_name, c.category_id)));
            }
        } catch (e) {}
    }

    // Confirm Delete
    if(confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', async () => {
        if (!deleteTarget) return;
        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.textContent = 'Deleting...';

        let url = deleteTarget.type === 'product' ? 'api/products/crud.php' : 'api/manage/categories_crud.php';
        
        try {
            const res = await fetch(`${url}?id=${deleteTarget.id}`, { method: 'DELETE' });
            const result = await res.json();
            if (!res.ok) throw new Error(result.error);

            closeModal(deleteModal);
            if (deleteTarget.type === 'product') loadProductsTable();
            else {
                loadCategoriesTable();
                loadProductCategories();
            }
        } catch (err) {
            deleteError.textContent = err.message;
            deleteError.classList.remove('hidden');
        } finally {
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.textContent = 'Delete';
        }
    });

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
            if(document.getElementById('cat-form-error')) document.getElementById('cat-form-error').classList.add('hidden');
        }, 250);
    }
    
    if(document.getElementById('cancel-product-btn')) document.getElementById('cancel-product-btn').onclick = () => closeModal(productModal);
    if(document.getElementById('cancel-cat-btn')) document.getElementById('cancel-cat-btn').onclick = () => closeModal(catModal);
    if(document.getElementById('cancel-delete-btn')) document.getElementById('cancel-delete-btn').onclick = () => closeModal(deleteModal);
    
    if (browseMediaBtn) {
        browseMediaBtn.addEventListener('click', () => {
            window.open('media.html', 'MediaLibrary', 'width=900,height=700,scrollbars=yes,resizable=yes');
        });
    }
});

// Helper for Media Window
function selectImageFromMedia(imageUrl) {
    const field = document.getElementById('product-image');
    if (field) field.value = imageUrl;
}