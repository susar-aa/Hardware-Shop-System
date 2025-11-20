document.addEventListener('DOMContentLoaded', () => {

    const searchInput = document.getElementById('sale-id-input');
    const searchBtn = document.getElementById('search-sale-btn');
    const searchError = document.getElementById('search-error');
    
    const returnSection = document.getElementById('return-section');
    const displayId = document.getElementById('display-sale-id');
    const displayDate = document.getElementById('display-sale-date');
    const displayTotal = document.getElementById('display-sale-total');
    const displayBranch = document.getElementById('display-branch-name');
    
    const tableBody = document.getElementById('return-items-body');
    const refundDisplay = document.getElementById('total-refund-display');
    const reasonInput = document.getElementById('return-reason');
    const submitBtn = document.getElementById('submit-return-btn');
    const messageBox = document.getElementById('process-message');

    let currentSaleData = null;
    let itemsToReturn = []; // Stores { product_id, quantity, refund }

    // --- 1. Search Sale ---
    searchBtn.addEventListener('click', async () => {
        const saleId = searchInput.value.trim();
        if (!saleId) return;

        // UI Reset
        searchError.classList.add('hidden');
        searchBtn.disabled = true;
        searchBtn.textContent = 'Searching...';
        returnSection.classList.add('hidden');
        messageBox.classList.add('hidden');

        try {
            const response = await fetch(`api/sales/get_sale_details.php?sale_id=${saleId}`);
            const data = await response.json();

            if (!response.ok) throw new Error(data.error || 'Sale not found');

            currentSaleData = data;
            renderSaleDetails(data);
            returnSection.classList.remove('hidden');

        } catch (error) {
            searchError.textContent = error.message;
            searchError.classList.remove('hidden');
        } finally {
            searchBtn.disabled = false;
            searchBtn.textContent = 'Search Sale';
        }
    });

    // --- 2. Render Table & Inputs ---
    function renderSaleDetails(data) {
        displayId.textContent = `#${data.sale_id}`;
        displayDate.textContent = new Date(data.sale_date).toLocaleString();
        displayTotal.textContent = `LKR ${parseFloat(data.total_amount).toFixed(2)}`;
        displayBranch.textContent = data.branch_name;

        tableBody.innerHTML = '';
        itemsToReturn = []; // Clear state
        refundDisplay.textContent = 'LKR 0.00';
        reasonInput.value = '';
        submitBtn.disabled = true;

        data.items.forEach(item => {
            const row = document.createElement('tr');
            
            const unitPrice = parseFloat(item.unit_price);
            const maxQty = parseInt(item.quantity); // Sold quantity

            row.innerHTML = `
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    ${item.product_name} 
                    <div class="text-xs text-gray-500">${item.product_code || ''}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">${maxQty}</td>
                <td class="px-6 py-4 text-sm text-gray-500">LKR ${unitPrice.toFixed(2)}</td>
                <td class="px-6 py-4">
                    <input type="number" min="0" max="${maxQty}" class="return-qty-input w-20 border border-gray-300 rounded p-1 text-sm" data-id="${item.product_id}" data-price="${unitPrice}">
                </td>
                <td class="px-6 py-4 text-sm font-bold text-gray-700 item-refund-total">LKR 0.00</td>
            `;
            tableBody.appendChild(row);
        });

        // Attach listeners to new inputs
        document.querySelectorAll('.return-qty-input').forEach(input => {
            input.addEventListener('input', updateCalculations);
        });
        
        reasonInput.addEventListener('input', updateCalculations);
    }

    // --- 3. Calculate Totals ---
    function updateCalculations() {
        let totalRefund = 0;
        itemsToReturn = [];
        let hasItems = false;

        document.querySelectorAll('.return-qty-input').forEach(input => {
            const qty = parseInt(input.value) || 0;
            const max = parseInt(input.max);
            const price = parseFloat(input.dataset.price);
            const productId = input.dataset.id;
            const refundCell = input.closest('tr').querySelector('.item-refund-total');

            // Validate input
            if (qty > max) {
                input.value = max; // Cap at max
                return updateCalculations(); // Recalculate
            }

            const lineRefund = qty * price;
            refundCell.textContent = `LKR ${lineRefund.toFixed(2)}`;
            
            if (qty > 0) {
                totalRefund += lineRefund;
                itemsToReturn.push({
                    product_id: productId,
                    quantity: qty,
                    refund_amount: lineRefund
                });
                hasItems = true;
            }
        });

        refundDisplay.textContent = `LKR ${totalRefund.toFixed(2)}`;

        // Enable submit if we have items AND a reason
        const hasReason = reasonInput.value.trim().length > 0;
        submitBtn.disabled = !(hasItems && hasReason);
    }

    // --- 4. Submit Return ---
    submitBtn.addEventListener('click', async () => {
        if (!confirm('Are you sure you want to process this return? Stock will be added back.')) return;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
        messageBox.className = 'hidden mt-4 p-3 rounded-md text-center';

        const payload = {
            sale_id: currentSaleData.sale_id,
            branch_id: currentSaleData.branch_id, // Return to same branch
            reason: reasonInput.value.trim(),
            items: itemsToReturn
        };

        try {
            const response = await fetch('api/sales/process_return.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok) throw new Error(result.error || 'Failed to process return');

            // Success
            messageBox.textContent = result.message;
            messageBox.classList.remove('hidden');
            messageBox.classList.add('bg-green-100', 'text-green-800');
            
            // Disable form to prevent double submit
            document.querySelectorAll('.return-qty-input, #return-reason').forEach(el => el.disabled = true);
            
        } catch (error) {
            messageBox.textContent = error.message;
            messageBox.classList.remove('hidden');
            messageBox.classList.add('bg-red-100', 'text-red-800');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Process Return';
        }
    });

});