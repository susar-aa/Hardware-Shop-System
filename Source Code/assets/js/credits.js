document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('credits-page-container')) return;

    const debtorBody = document.getElementById('debtor-table-body');
    const debtorLoader = document.getElementById('debtor-loader');
    const tableContainer = document.getElementById('debtor-table-container');
    const searchInput = document.getElementById('debtor-search');
    const dateFilter = document.getElementById('credit-month-filter');
    const tabBtns = document.querySelectorAll('.credit-tab-btn');

    const detailModal = document.getElementById('customer-detail-modal');
    const paymentModal = document.getElementById('payment-modal');
    const paymentForm = document.getElementById('payment-form');

    let allDebtors = [];
    let activeCustomer = null;
    let currentTab = 'ongoing';
    let currentMonthFilter = '';

    // Initialize Event Listeners for Filters
    if (dateFilter) {
        dateFilter.addEventListener('change', (e) => {
            currentMonthFilter = e.target.value;
            loadDebtors();
        });
    }

    if (tabBtns.length > 0) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Update active tab styling
                tabBtns.forEach(b => {
                    b.classList.remove('active-tab', 'border-blue-600', 'text-blue-600');
                    b.classList.add('border-transparent', 'text-gray-500');
                });

                const clickedBtn = e.target.closest('button');
                clickedBtn.classList.remove('border-transparent', 'text-gray-500');
                clickedBtn.classList.add('active-tab', 'border-blue-600', 'text-blue-600');

                currentTab = clickedBtn.dataset.tab;
                loadDebtors();
            });
        });
    }

    loadDebtors();

    async function loadDebtors() {
        debtorLoader.classList.remove('hidden');
        tableContainer.classList.add('hidden');
        try {
            const res = await fetch(`api/manage/customers_crud.php?credit_tab=${currentTab}&credit_month=${currentMonthFilter}`);
            allDebtors = await res.json();

            // Apply search filter if there's any text
            const query = searchInput ? searchInput.value.toLowerCase() : '';
            if (query) {
                const filtered = allDebtors.filter(d => d.name.toLowerCase().includes(query) || (d.phone && d.phone.includes(query)));
                renderDebtors(filtered);
            } else {
                renderDebtors(allDebtors);
            }
        } catch (e) { console.error(e); }
        finally { debtorLoader.classList.add('hidden'); tableContainer.classList.remove('hidden'); }
    }

    function renderDebtors(data) {
        debtorBody.innerHTML = '';
        
        let totalOutstanding = 0;
        let totalRecovered = 0;

        if (data.length === 0) {
            debtorBody.innerHTML = `<tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No customers found for this selection.</td></tr>`;
            document.getElementById('metric-outstanding').textContent = 'LKR 0.00';
            document.getElementById('metric-recovered').textContent = 'LKR 0.00';
            return;
        }

        data.forEach(cust => {
            const balance = parseFloat(cust.current_balance || 0);
            const paid = parseFloat(cust.total_paid || 0);
            
            if (balance > 0) totalOutstanding += balance;
            totalRecovered += paid;

            const balanceColor = balance > 0 ? 'text-red-600' : 'text-green-600';
            const row = `
                <tr>
                    <td class="px-6 py-4 font-medium">${cust.name}</td>
                    <td class="px-6 py-4">${cust.phone || '-'}</td>
                    <td class="px-6 py-4 font-bold ${balanceColor}">LKR ${balance.toFixed(2)}</td>
                    <td class="px-6 py-4">
                        <button onclick='if(typeof openCustomerProfile === "function") openCustomerProfile(${cust.customer_id});' class="text-indigo-600 hover:text-indigo-900 mr-3" title="View Profile">
                            <i class="fas fa-user-circle"></i> Profile
                        </button>
                        <button class="view-btn text-blue-600 hover:underline border-l pl-3 ml-2 border-gray-300" data-id="${cust.customer_id}">Settle / View</button>
                    </td>
                </tr>
            `;
            debtorBody.innerHTML += row;
        });

        document.getElementById('metric-outstanding').textContent = `LKR ${totalOutstanding.toFixed(2)}`;
        document.getElementById('metric-recovered').textContent = `LKR ${totalRecovered.toFixed(2)}`;
    }

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        const filtered = allDebtors.filter(d => d.name.toLowerCase().includes(query) || (d.phone && d.phone.includes(query)));
        renderDebtors(filtered);
    });

    debtorBody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('view-btn')) {
            const id = e.target.dataset.id;
            loadCustomerDetails(id);
        }
    });

    async function loadCustomerDetails(id) {
        try {
            const res = await fetch(`api/manage/customers_crud.php?id=${id}`);
            const cust = await res.json();
            activeCustomer = cust;

            document.getElementById('detail-cust-name').textContent = cust.name;
            document.getElementById('detail-cust-nic').textContent = cust.nic || '-';
            document.getElementById('detail-cust-phone').textContent = cust.phone || '-';
            document.getElementById('detail-cust-address').textContent = cust.address || '-';
            document.getElementById('detail-balance').textContent = `LKR ${parseFloat(cust.current_balance).toFixed(2)}`;

            // Build URL parameters for dates
            let payUrl = `api/manage/credit_payments.php?customer_id=${id}`;
            let billsUrl = `api/sales/read_sales.php?customer_id=${id}&payment_method=credit`;

            if (currentMonthFilter) {
                const [yyyy, mm] = currentMonthFilter.split('-');
                const startDate = `${yyyy}-${mm}-01`;
                const lastDay = new Date(yyyy, mm, 0).getDate();
                const endDate = `${yyyy}-${mm}-${lastDay}`;

                payUrl += `&start_date=${startDate}&end_date=${endDate}`;
                billsUrl += `&start_date=${startDate}&end_date=${endDate}`;
            }

            // Load Payment History
            const payRes = await fetch(payUrl);
            const payments = await payRes.json();
            const payDiv = document.getElementById('payment-history');
            payDiv.innerHTML = payments.length ? '' : '<div class="p-3 text-center text-gray-400">No payments found</div>';

            let totalPaid = 0;
            payments.forEach(p => {
                totalPaid += parseFloat(p.amount);
                payDiv.innerHTML += `
                    <div class="p-2 border-b bg-white mb-1 rounded flex justify-between items-center">
                        <div>
                            <div class="font-bold text-green-700">LKR ${parseFloat(p.amount).toFixed(2)}</div>
                            <div class="text-xs text-gray-500">${new Date(p.payment_date).toLocaleString()}</div>
                            <div class="text-xs italic text-gray-400">${p.notes || ''}</div>
                        </div>
                        <div class="text-xs text-gray-400">${p.branch_name || ''}</div>
                    </div>`;
            });

            document.getElementById('detail-total-paid').textContent = `LKR ${totalPaid.toFixed(2)}`;

            // UPDATED: Load Credit Bills History
            const billsRes = await fetch(billsUrl);
            const bills = await billsRes.json();
            const billsDiv = document.getElementById('bill-history');

            billsDiv.innerHTML = bills.length ? '' : '<div class="p-3 text-center text-gray-400">No credit bills found</div>';

            let totalDebtGenerated = 0;
            bills.forEach(bill => {
                const amount = parseFloat(bill.total_amount);
                const isReversed = bill.is_reversed == 1;
                if (!isReversed) totalDebtGenerated += amount;

                billsDiv.innerHTML += `
                    <div class="p-2 border-b bg-white mb-1 rounded flex justify-between items-center ${isReversed ? 'opacity-50' : ''}">
                        <div>
                            <div class="font-bold ${isReversed ? 'text-gray-400 line-through' : 'text-blue-700'}">
                                LKR ${amount.toFixed(2)}
                                ${isReversed ? '<span class="text-[10px] ml-2 text-red-500 font-bold">REVERSED</span>' : ''}
                            </div>
                            <div class="text-xs text-gray-500">ID: #${bill.sale_id} | ${new Date(bill.sale_date).toLocaleString()}</div>
                        </div>
                        <a href="invoice.php?sale_id=${bill.sale_id}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>`;
            });

            document.getElementById('detail-total-debt').textContent = `LKR ${totalDebtGenerated.toFixed(2)}`;

            openModal(detailModal);
        } catch (e) { console.error(e); }
    }

    document.getElementById('add-payment-btn').onclick = () => {
        document.getElementById('pay-cust-id').value = activeCustomer.customer_id;
        // Pre-fill with current balance
        document.getElementById('pay-amount').value = parseFloat(activeCustomer.current_balance).toFixed(2);
        openModal(paymentModal);
    };

    paymentForm.onsubmit = async (e) => {
        e.preventDefault();
        const saveBtn = paymentForm.querySelector('button[type="submit"]');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Processing...';

        const data = {
            customer_id: document.getElementById('pay-cust-id').value,
            amount: document.getElementById('pay-amount').value,
            notes: document.getElementById('pay-notes').value
        };

        try {
            const res = await fetch('api/manage/credit_payments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (res.ok) {
                closeModal(paymentModal);
                loadCustomerDetails(activeCustomer.customer_id);
                loadDebtors();
            } else {
                const err = await res.json();
                alert(err.error || 'Failed to record payment');
            }
        } catch (e) {
            console.error(e);
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Submit Payment';
        }
    };

    function openModal(el) {
        el.classList.remove('hidden', 'opacity-0', 'visibility-hidden');
        el.querySelector('.modal-content').classList.remove('-translate-y-10');
    }
});

function closeModal(el) {
    el.classList.add('opacity-0', 'visibility-hidden');
    setTimeout(() => el.classList.add('hidden'), 250);
}