document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('reports-container')) return;

    // --- Variables & Elements ---
    const container = document.getElementById('reports-container');
    const userRole = container.dataset.role;

    // Tabs
    const tabSales = document.getElementById('tab-sales');
    const tabInventory = document.getElementById('tab-inventory');
    const tabAnalytics = document.getElementById('tab-analytics');
    let currentTab = 'sales'; 

    // Views
    const viewStandard = document.getElementById('view-standard');
    const viewAnalytics = document.getElementById('view-analytics');

    // Filters
    const filterPeriod = document.getElementById('report-period');
    const dynamicDateContainer = document.getElementById('dynamic-date-container');
    const dynamicDateLabel = document.getElementById('dynamic-date-label');
    const filterBranchWrapper = document.getElementById('report-branch-wrapper');
    const filterBranch = document.getElementById('report-branch');
    const generateBtn = document.getElementById('generate-report-btn');
    const exportBtn = document.getElementById('export-btn');

    // Standard Report Elements
    const tableHeaders = document.getElementById('table-headers');
    const tableBody = document.getElementById('table-body');
    const loader = document.getElementById('report-loader');
    const emptyState = document.getElementById('report-empty');
    const labelMetric1 = document.getElementById('label-metric-1');
    const labelMetric2 = document.getElementById('label-metric-2');
    const labelMetric3 = document.getElementById('label-metric-3');
    const metric1Value = document.getElementById('summary-metric-1');
    const metric2Value = document.getElementById('summary-metric-2');
    const metric3Value = document.getElementById('summary-metric-3');

    // Analytics Elements
    const plRevenue = document.getElementById('pl-revenue');
    const plCogs = document.getElementById('pl-cogs');
    const plExpenses = document.getElementById('pl-expenses');
    const plProfit = document.getElementById('pl-profit');

    let chartInstance = null;

    // --- Init ---
    
    if (userRole === 'admin') {
        filterBranchWrapper.classList.remove('hidden');
        loadBranches();
    }

    updateDateInput('daily');

    // --- Core Functions ---

    function updateDateInput(period) {
        dynamicDateContainer.innerHTML = '';
        let input;
        const today = new Date();

        if (period === 'daily') {
            dynamicDateLabel.textContent = 'Select Date';
            input = document.createElement('input');
            input.type = 'date';
            input.id = 'report-date-input';
            input.className = 'w-full border border-gray-300 rounded-md p-2 text-sm';
            input.valueAsDate = today;
        } else if (period === 'monthly') {
            dynamicDateLabel.textContent = 'Select Month';
            input = document.createElement('input');
            input.type = 'month';
            input.id = 'report-date-input';
            input.className = 'w-full border border-gray-300 rounded-md p-2 text-sm';
            const monthStr = today.toISOString().slice(0, 7);
            input.value = monthStr;
        } else if (period === 'yearly') {
            dynamicDateLabel.textContent = 'Select Year';
            input = document.createElement('select');
            input.id = 'report-date-input';
            input.className = 'w-full border border-gray-300 rounded-md p-2 text-sm';
            const currentYear = today.getFullYear();
            for (let i = 0; i < 5; i++) {
                const year = currentYear - i;
                input.appendChild(new Option(year, year));
            }
        }
        dynamicDateContainer.appendChild(input);
    }

    function getDateRange() {
        const period = filterPeriod.value;
        const input = document.getElementById('report-date-input');
        const value = input.value;

        if (!value) return null;

        let startDate, endDate;
        if (period === 'daily') {
            startDate = value;
            endDate = value;
        } else if (period === 'monthly') {
            const [year, month] = value.split('-');
            startDate = `${value}-01`;
            endDate = `${value}-${new Date(year, month, 0).getDate()}`;
        } else if (period === 'yearly') {
            startDate = `${value}-01-01`;
            endDate = `${value}-12-31`;
        }

        return { startDate, endDate };
    }

    async function loadBranches() {
        try {
            const res = await fetch('api/manage/branches_crud.php');
            const branches = await res.json();
            filterBranch.innerHTML = '<option value="">All Branches</option>';
            branches.forEach(b => {
                filterBranch.appendChild(new Option(b.branch_name, b.branch_id));
            });
        } catch (e) { console.error('Branch load error', e); }
    }

    function switchTab(tab) {
        currentTab = tab;
        
        [tabSales, tabInventory, tabAnalytics].forEach(t => {
            t.classList.remove('text-blue-600', 'border-blue-500');
            t.classList.add('text-gray-500', 'border-transparent');
        });

        if (tab === 'sales') {
            tabSales.classList.add('text-blue-600', 'border-blue-500');
            viewStandard.classList.remove('hidden');
            viewAnalytics.classList.add('hidden');
            document.getElementById('filter-period-wrapper').classList.remove('hidden');
            exportBtn.classList.remove('hidden');
            
            labelMetric1.textContent = 'Total Revenue';
            labelMetric2.textContent = 'Total Items Sold';
            labelMetric3.textContent = 'Transactions';
        } else if (tab === 'inventory') {
            tabInventory.classList.add('text-blue-600', 'border-blue-500');
            viewStandard.classList.remove('hidden');
            viewAnalytics.classList.add('hidden');
            document.getElementById('filter-period-wrapper').classList.add('hidden');
            exportBtn.classList.remove('hidden');

            labelMetric1.textContent = 'Total Stock Value';
            labelMetric2.textContent = 'Items Sold (Period)';
            labelMetric3.textContent = 'Stock On Hand';
        } else {
            tabAnalytics.classList.add('text-blue-600', 'border-blue-500');
            viewStandard.classList.add('hidden');
            viewAnalytics.classList.remove('hidden');
            document.getElementById('filter-period-wrapper').classList.remove('hidden');
            exportBtn.classList.add('hidden'); 
        }
        
        tableBody.innerHTML = '';
        tableHeaders.innerHTML = '';
        metric1Value.textContent = '-';
        metric2Value.textContent = '-';
        metric3Value.textContent = '-';
        emptyState.classList.remove('hidden');
    }

    async function generateReport() {
        const dates = getDateRange();
        if (!dates) {
            alert('Please select a valid date.');
            return;
        }

        const params = new URLSearchParams({
            start_date: dates.startDate,
            end_date: dates.endDate,
            branch_id: filterBranch.value, 
            group_by: filterPeriod.value
        });

        if (currentTab === 'analytics') {
            loadAnalytics(params);
            return;
        }

        loader.classList.remove('hidden');
        emptyState.classList.add('hidden');
        tableBody.innerHTML = '';
        
        let apiUrl = currentTab === 'sales' 
            ? 'api/reports/sales_metrics.php' 
            : 'api/reports/inventory_metrics.php';

        try {
            const response = await fetch(`${apiUrl}?${params.toString()}`);
            const data = await response.json();

            if (data.error) throw new Error(data.error);

            updateStandardSummary(data.summary);
            renderTable(data.rows);

        } catch (error) {
            console.error('Report Error:', error);
            tableBody.innerHTML = `<tr><td colspan="100%" class="text-center p-4 text-red-500">Error: ${error.message}</td></tr>`;
        } finally {
            loader.classList.add('hidden');
        }
    }

    function updateStandardSummary(summary) {
        const fmt = (num) => parseFloat(num || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
        if (currentTab === 'sales') {
            metric1Value.textContent = `LKR ${fmt(summary.total_revenue)}`;
            metric2Value.textContent = summary.total_items || 0;
            metric3Value.textContent = summary.total_transactions || 0;
        } else {
            metric1Value.textContent = `LKR ${fmt(summary.total_value)}`;
            metric2Value.textContent = summary.period_items_sold || 0;
            metric3Value.textContent = summary.total_qty || 0;
        }
    }

    function renderTable(rows) {
        tableHeaders.innerHTML = '';
        
        if (!rows || rows.length === 0) {
            emptyState.classList.remove('hidden');
            return;
        }

        let columns = [];
        if (currentTab === 'sales') {
            const period = filterPeriod.value;
            const timeLabel = period === 'daily' ? 'Date' : (period === 'monthly' ? 'Month' : 'Year');
            
            columns = [
                { header: timeLabel, key: 'period_label' },
                { header: 'Transactions', key: 'transaction_count' },
                { header: 'Items Sold', key: 'items_sold' },
                { header: 'Revenue (LKR)', key: 'revenue', format: 'money' }
            ];
        } else {
            columns = [
                { header: 'Product Name', key: 'name' },
                { header: 'Code', key: 'product_code' },
                { header: 'Stock', key: 'stock' },
                { header: 'Sold', key: 'period_sold' },
                { header: 'Cost', key: 'cost', format: 'money' },
                { header: 'Value', key: 'stock_value', format: 'money' },
                { header: 'Status', key: 'status_html' }
            ];
        }

        if (userRole === 'admin' && !filterBranch.value) {
            columns.splice(1, 0, { header: 'Branch', key: 'branch_name' });
        }

        columns.forEach(col => {
            const th = document.createElement('th');
            th.className = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
            th.textContent = col.header;
            tableHeaders.appendChild(th);
        });

        rows.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            
            columns.forEach(col => {
                const td = document.createElement('td');
                td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
                
                let val = row[col.key];
                
                if (col.format === 'money') {
                    val = `LKR ${parseFloat(val || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
                }
                
                if (col.key === 'status_html') {
                    td.innerHTML = val;
                } else {
                    td.textContent = val || (val === 0 ? '0' : '-'); 
                }
                
                tr.appendChild(td);
            });
            tableBody.appendChild(tr);
        });
    }

    async function loadAnalytics(params) {
        try {
            const response = await fetch(`api/reports/analytics.php?${params.toString()}`);
            const data = await response.json();
            if (data.error) throw new Error(data.error);

            const fmt = (num) => `LKR ${parseFloat(num || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
            plRevenue.textContent = fmt(data.totals.revenue);
            plCogs.textContent = fmt(data.totals.cogs);
            plExpenses.textContent = fmt(data.totals.expenses);
            plProfit.textContent = fmt(data.totals.net_profit);

            renderChart(data.chart);

        } catch (error) {
            console.error('Analytics Error:', error);
        }
    }

    function renderChart(chartData) {
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        if (chartInstance) chartInstance.destroy();

        const labels = chartData.map(d => d.date);
        const revenueData = chartData.map(d => d.revenue);
        const expenseData = chartData.map(d => d.expense);

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenueData,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: 'Expenses',
                        data: expenseData,
                        backgroundColor: 'rgba(239, 68, 68, 0.5)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    function exportTableToCSV() {
        const rows = [];
        const headers = [];
        tableHeaders.querySelectorAll('th').forEach(th => headers.push(th.innerText));
        rows.push(headers.join(','));

        tableBody.querySelectorAll('tr').forEach(tr => {
            const rowData = [];
            tr.querySelectorAll('td').forEach(td => {
                let text = td.innerText.replace(/(\r\n|\n|\r)/gm, "").trim();
                if (text.includes(',')) text = `"${text}"`;
                rowData.push(text);
            });
            rows.push(rowData.join(','));
        });

        if (rows.length < 2) {
            alert('No data to export');
            return;
        }

        const csvContent = "data:text/csv;charset=utf-8," + rows.join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    tabSales.addEventListener('click', () => switchTab('sales'));
    tabInventory.addEventListener('click', () => switchTab('inventory'));
    tabAnalytics.addEventListener('click', () => switchTab('analytics'));
    generateBtn.addEventListener('click', generateReport);
    exportBtn.addEventListener('click', exportTableToCSV);
    filterPeriod.addEventListener('change', () => updateDateInput(filterPeriod.value));
});