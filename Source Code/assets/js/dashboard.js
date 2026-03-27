document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('dashboard-container')) return;

    const container = document.getElementById('dashboard-container');
    const userRole = container.dataset.role;

    // Elements
    const branchWrapper = document.getElementById('dashboard-branch-wrapper');
    const branchSelect = document.getElementById('dashboard-branch-select');

    const cardToday = document.getElementById('card-today');
    const cardYesterday = document.getElementById('card-yesterday');
    const cardWeek = document.getElementById('card-week');
    const cardMonth = document.getElementById('card-month');
    const cardLowStock = document.getElementById('card-low-stock');
    const recentBody = document.getElementById('recent-sales-body');

    let chartInstance = null;
    let pmChartInstance = null;

    // --- Initialization ---
    if (userRole === 'admin') {
        branchWrapper.classList.remove('hidden');
        loadBranches();
    }

    loadDashboardData();

    // --- Functions ---

    async function loadBranches() {
        try {
            const res = await fetch('api/manage/branches_crud.php');
            const branches = await res.json();
            branches.forEach(b => {
                branchSelect.appendChild(new Option(b.branch_name, b.branch_id));
            });
            
            // Add listener after loading
            branchSelect.addEventListener('change', loadDashboardData);

        } catch (e) { console.error('Branch load error', e); }
    }

    async function loadDashboardData() {
        // Build URL with filter
        let url = 'api/dashboard/stats.php';
        if (userRole === 'admin' && branchSelect.value) {
            url += `?branch_id=${branchSelect.value}`;
        }

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Failed to fetch stats');

            const data = await response.json();
            updateUI(data);

        } catch (error) {
            console.error('Dashboard Error:', error);
            if (recentBody) recentBody.innerHTML = '<tr><td class="py-4 text-center text-red-400">Error loading data</td></tr>';
        }
    }

    function updateUI(data) {
        const fmt = (num) => `LKR ${parseFloat(num || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}`;

        // --- STAFF VIEW ---
        if (userRole !== 'admin') {
            const staffToday = document.getElementById('staff-today');
            const staffYesterday = document.getElementById('staff-yesterday');
            if (staffToday) staffToday.textContent = fmt(data.cards.sales_today);
            if (staffYesterday) staffYesterday.textContent = fmt(data.cards.sales_yesterday);
            return; // Staff don't get charts or tables
        }

        // --- ADMIN VIEW ---
        // 1. Update Cards
        if(cardToday) cardToday.textContent = fmt(data.cards.sales_today);
        if(cardYesterday) cardYesterday.textContent = fmt(data.cards.sales_yesterday);
        if(cardWeek) cardWeek.textContent = fmt(data.cards.sales_week);
        if(cardMonth) cardMonth.textContent = fmt(data.cards.sales_month);
        if(cardLowStock) cardLowStock.textContent = data.low_stock || 0;

        // 2. Render Charts
        if (data.chart) renderChart(data.chart);
        if (data.payment_methods) renderPaymentMethodsChart(data.payment_methods);

        // 3. Render Cheque Reminders
        if (data.cheque_reminders) renderChequeReminders(data.cheque_reminders);

        // 4. Recent Sales
        if (recentBody) {
            recentBody.innerHTML = '';
            if (data.recent && data.recent.length > 0) {
                data.recent.forEach(sale => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b last:border-0';
                    tr.innerHTML = `
                        <td class="py-3">
                            <div class="font-medium text-gray-900">#${sale.sale_id}</div>
                            <div class="text-xs text-gray-500">${new Date(sale.sale_date).toLocaleTimeString()}</div>
                        </td>
                        <td class="py-3 text-right">
                            <div class="font-bold text-green-600">${fmt(sale.total_amount)}</div>
                            <div class="text-xs text-gray-400">${sale.user_name || 'User'}</div>
                        </td>
                    `;
                    recentBody.appendChild(tr);
                });
            } else {
                recentBody.innerHTML = '<tr><td class="py-4 text-center text-gray-400">No recent sales</td></tr>';
            }
        }
    }

    function renderChart(chartData) {
        const ctx = document.getElementById('dashboardSalesChart').getContext('2d');
        if (chartInstance) chartInstance.destroy();

        const labels = chartData.map(d => d.date);
        const values = chartData.map(d => d.total);

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Sales (LKR)',
                    data: values,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function renderPaymentMethodsChart(pmData) {
        const ctx = document.getElementById('paymentMethodsChart').getContext('2d');
        if (pmChartInstance) pmChartInstance.destroy();

        const labels = Object.keys(pmData);
        const values = Object.values(pmData);

        // Colors mapping: Cash = Green, Credit = Orange, Cheque = Blue
        const colors = [];
        labels.forEach(label => {
            if (label.toLowerCase() === 'cash') colors.push('rgba(34, 197, 94, 0.8)');
            else if (label.toLowerCase() === 'credit') colors.push('rgba(249, 115, 22, 0.8)');
            else if (label.toLowerCase() === 'cheque') colors.push('rgba(59, 130, 246, 0.8)');
            else colors.push('rgba(156, 163, 175, 0.8)');
        });

        pmChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12 }
                    }
                }
            }
        });
    }

    function renderChequeReminders(cheques) {
        const listContainer = document.getElementById('cheque-reminders-list');
        if (!listContainer) return;

        listContainer.innerHTML = '';
        
        if (!cheques || cheques.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-check-circle text-3xl text-green-300 mb-2"></i>
                    <p class="text-sm">No cheques to bank soon.</p>
                </div>`;
            return;
        }

        const fmt = (num) => parseFloat(num || 0).toLocaleString(undefined, {minimumFractionDigits: 2});

        cheques.forEach(cq => {
            let colorClass = 'text-blue-600 bg-blue-50 border-blue-200'; // Upcoming
            let icon = '<i class="far fa-clock"></i>';
            let labelText = `In ${cq.days_until} days`;
            
            if (cq.urgency === 'overdue') {
                colorClass = 'text-red-700 bg-red-50 border-red-300';
                icon = '<i class="fas fa-exclamation-circle text-red-500"></i>';
                labelText = 'Overdue';
            } else if (cq.urgency === 'today') {
                colorClass = 'text-red-600 bg-red-50 border-red-300 shadow-sm';
                icon = '<i class="fas fa-university"></i>';
                labelText = 'Bank TODAY';
            } else if (cq.urgency === 'tomorrow') {
                colorClass = 'text-orange-600 bg-orange-50 border-orange-200';
                labelText = 'Bank Tomorrow';
            }

            const cleanBankName = cq.bank || 'Unknown Bank';

            const item = document.createElement('div');
            item.className = `p-3 mb-3 rounded-lg border ${colorClass} transition-all hover:opacity-90`;
            item.innerHTML = `
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            ${icon}
                            <span class="font-bold text-sm uppercase tracking-wide">${labelText}</span>
                        </div>
                        <p class="text-sm font-semibold truncate" title="${cq.customer}">${cq.customer}</p>
                        <p class="text-xs opacity-80 mt-1">${cleanBankName} - #${cq.cheque_number}</p>
                    </div>
                    <div class="text-right">
                        <div class="font-bold whitespace-nowrap">LKR ${fmt(cq.amount)}</div>
                        <div class="text-xs opacity-75 mt-1">${cq.date}</div>
                    </div>
                </div>
            `;
            listContainer.appendChild(item);
        });
    }
});