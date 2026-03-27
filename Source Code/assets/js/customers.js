document.addEventListener('DOMContentLoaded', () => {

    const searchInput = document.getElementById('cust-search');
    const tableLoader = document.getElementById('table-loader');
    const tableContainer = document.getElementById('table-container');
    const emptyState = document.getElementById('empty-state');
    const customersTbody = document.getElementById('customers-tbody');

    const modal = document.getElementById('customer-modal');
    const form = document.getElementById('customer-form');
    
    let debounceTimer;

    function fetchCustomers(query = '') {
        tableLoader.classList.remove('hidden');
        tableContainer.classList.add('hidden');
        emptyState.classList.add('hidden');

        fetch(`api/manage/customers_crud.php?search=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                customersTbody.innerHTML = '';
                
                if (data.length === 0) {
                    emptyState.classList.remove('hidden');
                } else {
                    data.forEach(cust => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-gray-50 transition';
                        
                        tr.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-bold text-gray-900 block">${cust.name}</span>
                                ${cust.address ? `<span class="text-xs text-gray-500 mt-1 block max-w-xs truncate">${cust.address}</span>` : ''}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 font-medium">${cust.nic || '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">${cust.phone || '-'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button onclick='if(typeof openCustomerProfile === "function") openCustomerProfile(${cust.customer_id});' class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 p-2 rounded-full transition mr-2" title="View Global Profile">
                                    <i class="fas fa-user-circle"></i>
                                </button>
                                <button onclick='editCustomer(${JSON.stringify(cust).replace(/'/g, "&#39;")})' class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-2 rounded-full transition mr-2" title="Edit Customer">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button onclick='deleteCustomer(${cust.customer_id}, "${cust.name.replace(/"/g, '&quot;')}")' class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 p-2 rounded-full transition" title="Delete Customer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        customersTbody.appendChild(tr);
                    });
                    tableContainer.classList.remove('hidden');
                }
            })
            .catch(err => console.error("Error fetching customers:", err))
            .finally(() => {
                tableLoader.classList.add('hidden');
            });
    }

    // Live Search
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchCustomers(e.target.value);
        }, 300);
    });

    // Modal Helpers
    window.openAddCustomerModal = function() {
        document.getElementById('customer-modal-title').textContent = 'Add New Customer';
        document.getElementById('modal-cust-id').value = '';
        form.reset();
        
        showModal();
    };

    window.editCustomer = function(cust) {
        document.getElementById('customer-modal-title').textContent = 'Edit Customer Details';
        document.getElementById('modal-cust-id').value = cust.customer_id;
        document.getElementById('modal-cust-name').value = cust.name;
        document.getElementById('modal-cust-nic').value = cust.nic || '';
        document.getElementById('modal-cust-phone').value = cust.phone || '';
        document.getElementById('modal-cust-address').value = cust.address || '';
        
        showModal();
    };

    function showModal() {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('.modal-content').classList.remove('scale-95', 'opacity-0');
        }, 10);
    }

    window.closeCustomerModal = function() {
        modal.classList.add('opacity-0');
        modal.querySelector('.modal-content').classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    };

    // Save Customer
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('modal-cust-id').value;
        const payload = {
            name: document.getElementById('modal-cust-name').value,
            nic: document.getElementById('modal-cust-nic').value,
            phone: document.getElementById('modal-cust-phone').value,
            address: document.getElementById('modal-cust-address').value
        };

        const method = id ? 'PUT' : 'POST';
        if (id) payload.customer_id = id;

        try {
            const btn = document.getElementById('save-cust-btn');
            btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Saving...`;
            btn.disabled = true;

            const res = await fetch('api/manage/customers_crud.php', {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await res.json();
            if (result.success) {
                closeCustomerModal();
                fetchCustomers(searchInput.value);
            } else {
                alert("Error: " + (result.error || "Failed to save customer."));
            }
        } catch (error) {
            console.error(error);
            alert("Network error occurred.");
        } finally {
            const btn = document.getElementById('save-cust-btn');
            btn.innerHTML = `<i class="fas fa-save mr-2"></i> Save Profile`;
            btn.disabled = false;
        }
    });

    // Delete Customer
    window.deleteCustomer = async function(id, name) {
        if (!confirm(`Are you absolutely sure you want to delete the customer "${name}"?\nThis action cannot be undone.`)) return;

        try {
            const res = await fetch('api/manage/customers_crud.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ customer_id: id })
            });

            const result = await res.json();
            if (result.success) {
                fetchCustomers(searchInput.value); // Reload grid
            } else {
                alert(result.error || "Failed to delete customer.");
            }
        } catch (error) {
            console.error(error);
            alert("Network error occurred while trying to delete.");
        }
    };

    // Initial load
    fetchCustomers();

});
