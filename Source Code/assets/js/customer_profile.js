function openCustomerProfile(customerId) {
    if (!customerId) return;
    
    const modal = document.getElementById('global-customer-modal');
    const content = document.getElementById('customer-profile-content');
    const loader = document.getElementById('customer-profile-loader');
    
    // Show Modal
    modal.classList.remove('hidden');
    // Trigger animation frame
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.modal-content').classList.remove('scale-95', 'opacity-0');
    }, 10);

    // Reset View
    content.classList.add('hidden');
    loader.classList.remove('hidden');

    fetch(`api/manage/get_customer_profile.php?id=${customerId}`)
        .then(res => res.json())
        .then(result => {
            if (!result.success) throw new Error(result.error);
            
            // Populate Basic Info
            document.getElementById('cp-name').textContent = result.customer.name || '-';
            document.getElementById('cp-nic').textContent = result.customer.nic || 'N/A';
            document.getElementById('cp-phone').textContent = result.customer.phone || 'N/A';
            document.getElementById('cp-address').textContent = result.customer.address || 'No Address Provided';

            // Populate Cheques
            const chequesTbody = document.getElementById('cp-cheques-tbody');
            const chequesEmpty = document.getElementById('cp-cheques-empty');
            chequesTbody.innerHTML = '';
            
            if (result.cheque_records.length === 0) {
                chequesEmpty.classList.remove('hidden');
            } else {
                chequesEmpty.classList.add('hidden');
                result.cheque_records.forEach(chq => {
                    chequesTbody.innerHTML += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-gray-700">${chq.bank_name} <span class="text-xs text-gray-400 block">${chq.cheque_number}</span></td>
                            <td class="px-4 py-2 text-gray-600">${chq.cheque_date}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    ${chq.status === 'cleared' ? 'bg-green-100 text-green-700' : 
                                      chq.status === 'bounced' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'}">
                                    ${chq.status}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right font-bold text-blue-600">LKR ${parseFloat(chq.amount).toFixed(2)}</td>
                        </tr>
                    `;
                });
            }

            // Populate Credits
            const creditsTbody = document.getElementById('cp-credits-tbody');
            const creditsEmpty = document.getElementById('cp-credits-empty');
            creditsTbody.innerHTML = '';
            
            if (result.credit_records.length === 0) {
                creditsEmpty.classList.remove('hidden');
            } else {
                creditsEmpty.classList.add('hidden');
                result.credit_records.forEach(cred => {
                    creditsTbody.innerHTML += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-700 font-medium">#SALE-${cred.sale_id}</td>
                            <td class="px-4 py-2 text-gray-600">${cred.sale_date.split(' ')[0]}</td>
                            <td class="px-4 py-2 text-right font-bold text-red-600">LKR ${parseFloat(cred.total_amount).toFixed(2)}</td>
                        </tr>
                    `;
                });
            }

            // Reveal
            loader.classList.add('hidden');
            content.classList.remove('hidden');
        })
        .catch(err => {
            console.error(err);
            alert("Error loading customer profile: " + err.message);
            closeCustomerProfile();
        });
}

function closeCustomerProfile() {
    const modal = document.getElementById('global-customer-modal');
    modal.classList.add('opacity-0');
    modal.querySelector('.modal-content').classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
