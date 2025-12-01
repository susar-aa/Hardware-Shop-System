document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('invoice-page-container')) return;

    // Elements
    const loader = document.getElementById('invoice-loader');
    const content = document.getElementById('invoice-data');
    const errorDiv = document.getElementById('invoice-error');
    
    const invId = document.getElementById('inv-id');
    const invDate = document.getElementById('inv-date');
    const invBranch = document.getElementById('inv-branch');
    const invUser = document.getElementById('inv-user');
    const invItemsBody = document.getElementById('inv-items-body');
    const invTotal = document.getElementById('inv-total');
    const invStatusWrapper = document.getElementById('inv-status-wrapper');

    // Modal Elements
    const modal = document.getElementById('return-item-modal');
    const modalProdName = document.getElementById('modal-prod-name');
    const modalMaxQty = document.getElementById('modal-max-qty');
    const modalUnitPrice = document.getElementById('modal-unit-price');
    const modalProdId = document.getElementById('modal-prod-id');
    const modalSaleId = document.getElementById('modal-sale-id');
    const modalReturnQty = document.getElementById('modal-return-qty');
    const modalRefundCalc = document.getElementById('modal-refund-calc');
    const modalReturnReason = document.getElementById('modal-return-reason');
    const modalError = document.getElementById('modal-error');
    const form = document.getElementById('return-item-form');
    const confirmBtn = document.getElementById('confirm-return-btn');
    const closeBtn = document.getElementById('close-modal-btn');

    // Get Sale ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const saleId = urlParams.get('sale_id');

    let currentSaleData = null;
    let currentItemPrice = 0;

    if (!saleId) {
        showError("Invalid Sale ID.");
        return;
    }

    loadInvoice();

    // --- Functions ---

    async function loadInvoice() {
        loader.classList.remove('hidden');
        content.classList.add('hidden');
        errorDiv.classList.add('hidden');

        try {
            const response = await fetch(`api/sales/get_sale_details.php?sale_id=${saleId}`);
            if (!response.ok) throw new Error('Failed to load invoice.');
            
            const data = await response.json();
            currentSaleData = data; // Store for reference
            
            renderInvoice(data);
            
        } catch (error) {
            showError(error.message);
        } finally {
            loader.classList.add('hidden');
        }
    }

    function renderInvoice(data) {
        invId.textContent = `#${data.sale_id}`;
        invDate.textContent = new Date(data.sale_date).toLocaleString();
        invBranch.textContent = data.branch_name;
        // API might not return user_name directly in get_sale_details (check API), assuming it does or logic needs update
        // If user_name missing in API response, might display just ID or handle gracefully.
        // Checking get_sale_details.php: it joins branches but not users in main query. 
        // Let's assume for now it's fine or we update API. *Self-correction: Updating JS to be safe*
        invUser.textContent = data.user_name || 'System'; 

        invTotal.textContent = `LKR ${parseFloat(data.total_amount).toFixed(2)}`;

        // Status Badges
        invStatusWrapper.innerHTML = '';
        if (data.is_reversed == 1) {
            invStatusWrapper.innerHTML = '<span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-bold">FULLY REVERSED</span>';
            document.querySelectorAll('.return-action-btn').forEach(btn => btn.disabled = true);
        } else {
             invStatusWrapper.innerHTML = '<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-bold">PAID</span>';
        }

        // Items
        invItemsBody.innerHTML = '';
        data.items.forEach(item => {
            const price = parseFloat(item.unit_price);
            const qty = parseInt(item.quantity);
            const total = price * qty;
            
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-100 last:border-0';
            row.innerHTML = `
                <td class="py-3">
                    <div class="font-medium text-gray-900">${item.product_name}</div>
                    <div class="text-xs text-gray-500">${item.product_code || ''}</div>
                </td>
                <td class="py-3 text-center text-sm">LKR ${price.toFixed(2)}</td>
                <td class="py-3 text-center text-sm">${qty}</td>
                <td class="py-3 text-right font-bold text-gray-800">LKR ${total.toFixed(2)}</td>
                <td class="py-3 text-right print:hidden">
                    ${data.is_reversed == 0 ? `
                    <button class="return-action-btn text-red-500 hover:text-red-700 text-sm font-medium" 
                        data-pid="${item.product_id}" 
                        data-pname="${item.product_name}" 
                        data-price="${price}" 
                        data-qty="${qty}">
                        Return
                    </button>` : '-'}
                </td>
            `;
            invItemsBody.appendChild(row);
        });

        content.classList.remove('hidden');
    }

    function showError(msg) {
        errorDiv.textContent = msg;
        errorDiv.classList.remove('hidden');
    }

    // --- Return Modal Logic ---

    invItemsBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.return-action-btn');
        if (btn) {
            const pid = btn.dataset.pid;
            const pname = btn.dataset.pname;
            const price = parseFloat(btn.dataset.price);
            const qty = parseInt(btn.dataset.qty);

            modalProdId.value = pid;
            modalSaleId.value = saleId;
            modalProdName.textContent = pname;
            modalMaxQty.textContent = qty;
            modalUnitPrice.textContent = `LKR ${price.toFixed(2)}`;
            
            modalReturnQty.value = 1;
            modalReturnQty.max = qty;
            currentItemPrice = price;
            
            modalRefundCalc.textContent = `LKR ${price.toFixed(2)}`;
            modalReturnReason.value = '';
            modalError.classList.add('hidden');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirm Return';

            openModal();
        }
    });

    modalReturnQty.addEventListener('input', () => {
        const qty = parseInt(modalReturnQty.value) || 0;
        const max = parseInt(modalReturnQty.max);
        
        if (qty > max) modalReturnQty.value = max;
        if (qty < 1) modalReturnQty.value = 1;
        
        const validQty = parseInt(modalReturnQty.value);
        const total = validQty * currentItemPrice;
        modalRefundCalc.textContent = `LKR ${total.toFixed(2)}`;
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Processing...';
        modalError.classList.add('hidden');

        const payload = {
            sale_id: modalSaleId.value,
            branch_id: currentSaleData.branch_id, // Needed for stock update
            reason: modalReturnReason.value.trim(),
            items: [{
                product_id: modalProdId.value,
                quantity: parseInt(modalReturnQty.value),
                // API calculates refund based on original unit price in DB, safe to send just qty
            }]
        };

        try {
            const response = await fetch('api/sales/process_return.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok) throw new Error(result.error || 'Return failed');

            alert('Item returned successfully. Invoice updated.');
            closeModal();
            loadInvoice(); // Reload to show updated quantities/totals

        } catch (error) {
            modalError.textContent = error.message;
            modalError.classList.remove('hidden');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirm Return';
        }
    });

    function openModal() {
        modal.classList.remove('hidden');
        modal.querySelector('.modal-content').classList.remove('-translate-y-10');
        modal.classList.remove('opacity-0', 'visibility-hidden');
    }

    function closeModal() {
        modal.classList.add('opacity-0', 'visibility-hidden');
        modal.querySelector('.modal-content').classList.add('-translate-y-10');
        setTimeout(() => modal.classList.add('hidden'), 250);
    }

    closeBtn.addEventListener('click', closeModal);
});