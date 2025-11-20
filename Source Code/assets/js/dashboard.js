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

        // 1. Update Cards
        if(cardToday) cardToday.textContent = fmt(data.cards.sales_today);
        if(cardYesterday) cardYesterday.textContent = fmt(data.cards.sales_yesterday);
        if(cardWeek) cardWeek.textContent = fmt(data.cards.sales_week);
        if(cardMonth) cardMonth.textContent = fmt(data.cards.sales_month);
        if(cardLowStock) cardLowStock.textContent = data.low_stock || 0;

        // 2. Render Chart
        renderChart(data.chart);

        // 3. Recent Sales
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
});