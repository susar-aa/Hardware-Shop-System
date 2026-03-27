document.addEventListener('DOMContentLoaded', () => {

    const monthFilter = document.getElementById('cheque-month-filter');
    const tableLoader = document.getElementById('table-loader');
    const tableContainer = document.getElementById('table-container');
    const emptyState = document.getElementById('empty-state');
    const chequesTbody = document.getElementById('cheques-tbody');

    const metricTotal = document.getElementById('metric-total');
    const metricToBank = document.getElementById('metric-to-bank');
    const metricNearestDate = document.getElementById('metric-nearest-date');

    // Edit Modal Elements
    const editModal = document.getElementById('edit-cheque-modal');
    const editForm = document.getElementById('edit-cheque-form');
    const closeEditBtn = document.getElementById('close-edit-cheque');
    const cancelEditBtn = document.getElementById('cancel-edit-cheque');
    
    // Default to current month
    const today = new Date();
    const currentMonth = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
    monthFilter.value = currentMonth;

    monthFilter.addEventListener('change', () => {
        loadCheques();
    });

    async function loadCheques() {
        tableLoader.classList.remove('hidden');
        tableContainer.classList.add('hidden');
        emptyState.classList.add('hidden');

        try {
            const selectedMonth = monthFilter.value; // Format: YYYY-MM
            const response = await fetch(`api/manage/cheques.php?month=${selectedMonth}`);
            
            if (!response.ok) throw new Error("Failed to fetch data.");
            const result = await response.json();
            
            if (result.error) throw new Error(result.error);

            // Update Metrics
            metricTotal.textContent = `LKR ${parseFloat(result.metrics.total_payments).toFixed(2)}`;
            metricToBank.textContent = `LKR ${parseFloat(result.metrics.cheques_to_bank).toFixed(2)}`;
            metricNearestDate.textContent = result.metrics.nearest_date;

            // Update Table
            if (result.data.length === 0) {
                emptyState.classList.remove('hidden');
            } else {
                renderChequesTable(result.data);
                tableContainer.classList.remove('hidden');
            }

        } catch (error) {
            console.error("Error loading cheques:", error);
            alert("Error loading cheques: " + error.message);
        } finally {
            tableLoader.classList.add('hidden');
        }
    }

    function renderChequesTable(data) {
        chequesTbody.innerHTML = '';
        let currentDateGroup = null;

        data.forEach(cheque => {
            const rowDate = cheque.parsed_date || 'N/A';

            // Group by Date visually
            if (rowDate !== currentDateGroup) {
                currentDateGroup = rowDate;
                const dateRow = document.createElement('tr');
                dateRow.className = 'bg-gray-100';
                dateRow.innerHTML = `
                    <td colspan="8" class="px-6 py-3 text-sm font-bold text-gray-700">
                        <i class="fas fa-calendar-day mr-2 text-blue-500"></i> ${currentDateGroup === 'N/A' ? 'No Date Provided' : new Date(currentDateGroup).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                    </td>
                `;
                chequesTbody.appendChild(dateRow);
            }

            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition-colors';
            
            // Name with Global Profile Link (To be implemented)
            let custInfo = `<a href="#" onclick="if(typeof openCustomerProfile === 'function') openCustomerProfile(${cheque.customer_id}); return false;" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">${cheque.customer_name || 'Walk-in'}</a>`;
            if (cheque.customer_nic) {
                custInfo += `<div class="text-xs text-gray-500">NIC: ${cheque.customer_nic}</div>`;
            }

            // Status Dropdown
            const statusOptions = ['pending', 'banked', 'cleared', 'bounced'].map(val => 
                `<option value="${val}" ${cheque.status === val ? 'selected' : ''}>${val.charAt(0).toUpperCase() + val.slice(1)}</option>`
            ).join('');

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${cheque.sale_date.split(' ')[0]}</td>
                <td class="px-6 py-4 whitespace-nowrap">${custInfo}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${cheque.parsed_bank}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${cheque.parsed_number}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">${cheque.parsed_date || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-bold">LKR ${parseFloat(cheque.total_amount).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <select onchange="updateChequeStatus(${cheque.payment_id}, this.value)" class="text-xs font-semibold rounded-full px-2 py-1 border border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500
                        ${cheque.status === 'cleared' ? 'bg-green-100 text-green-800' : 
                          cheque.status === 'bounced' ? 'bg-red-100 text-red-800' : 
                          cheque.status === 'banked' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'}">
                        ${statusOptions}
                    </select>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick='if(typeof openCustomerProfile === "function") openCustomerProfile(${cheque.customer_id});' class="text-indigo-600 hover:text-indigo-900 mr-3" title="View Profile">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <button onclick='openEditModal(${JSON.stringify(cheque).replace(/'/g, "&#39;")})' class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick='deleteCheque(${cheque.payment_id})' class="text-red-600 hover:text-red-900" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            chequesTbody.appendChild(row);
        });
    }

    // Expose actions to global scope for onclick handlers
    window.updateChequeStatus = async function(paymentId, newStatus) {
        try {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('payment_id', paymentId);
            formData.append('status', newStatus);

            const response = await fetch('api/manage/update_cheque.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                // Optionally show a mini toast here
                // Reload to refresh colors
                loadCheques(); 
            } else {
                alert("Failed to update status: " + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error(error);
            alert("Network error updating status.");
        }
    };

    window.deleteCheque = async function(paymentId) {
        if (!confirm("Are you sure you want to permanently delete this cheque record?")) return;

        try {
            const formData = new FormData();
            formData.append('payment_id', paymentId);

            const response = await fetch('api/manage/delete_cheque.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                loadCheques(); 
            } else {
                alert("Failed to delete cheque: " + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error(error);
            alert("Network error deleting cheque.");
        }
    };

    window.openEditModal = function(cheque) {
        document.getElementById('edit-cheque-id').value = cheque.payment_id;
        document.getElementById('edit-cheque-bank').value = cheque.parsed_bank;
        document.getElementById('edit-cheque-number').value = cheque.parsed_number;
        document.getElementById('edit-cheque-date').value = cheque.parsed_date;
        document.getElementById('edit-cheque-amount').value = parseFloat(cheque.total_amount).toFixed(2);
        
        editModal.classList.remove('hidden');
        setTimeout(() => {
            editModal.querySelector('.modal-content').classList.remove('scale-95', 'opacity-0');
        }, 10);
    };

    function closeEdit() {
        editModal.querySelector('.modal-content').classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            editModal.classList.add('hidden');
        }, 250);
    }

    closeEditBtn.addEventListener('click', closeEdit);
    cancelEditBtn.addEventListener('click', closeEdit);

    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_details');
            formData.append('payment_id', document.getElementById('edit-cheque-id').value);
            formData.append('bank_name', document.getElementById('edit-cheque-bank').value);
            formData.append('cheque_number', document.getElementById('edit-cheque-number').value);
            formData.append('cheque_date', document.getElementById('edit-cheque-date').value);
            formData.append('amount', document.getElementById('edit-cheque-amount').value);

            const response = await fetch('api/manage/update_cheque.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                closeEdit();
                loadCheques(); 
            } else {
                alert("Failed to update cheque: " + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error(error);
            alert("Network error saving cheque.");
        }
    });

    // Initial Load
    loadCheques();

});
