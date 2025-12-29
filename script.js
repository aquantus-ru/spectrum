// script.js
document.addEventListener('DOMContentLoaded', () => {
    let spectrumChart;
    let currentPage = 1;
    let currentLimit = 15;
    let currentSort = 'val';
    let currentOrder = 'asc';
    let searchActive = false;
    let searchQuery = '';

    // Initialize Chart
    function initChart() {
        const ctx = document.getElementById('spectrumChart').getContext('2d');
        // Define plugin for custom drawing if needed, but standard config first

        spectrumChart = new Chart(ctx, {
            // Using a mixed chart, base type 'scatter' implies linear/log axes
            type: 'scatter',
            data: {
                datasets: [
                    {
                        type: 'bar',
                        label: 'Allocations',
                        data: [],
                        backgroundColor: 'rgba(50, 50, 255, 0.2)', // Translucent blue blocks
                        borderColor: 'rgba(50, 50, 255, 0.8)',
                        borderWidth: 1,
                        // Floating bars: data struct is [start, end] on axis
                        // But since we want Horizontal bars on a time/linear scale,
                        // ChartJS 3/4 supports indexAxis: 'y' for horizontal bar.
                        // However, combining with Scatter on Log X is tricky.
                        // Let's stick to scatter for points and use floating bars on the same X axis.
                        // Floating bars format: [start, end] for the value axis.
                        // Since X is our Log Value axis, we want bars that span X1 to X2 at a certain Y.
                        // Chart.js requires 'indexAxis: y' to make the "Value" axis X.
                        // This applies to the whole chart or dataset.
                        indexAxis: 'y',
                        barThickness: 20,
                    },
                    {
                        type: 'scatter',
                        label: 'Channels',
                        data: [],
                        backgroundColor: 'rgba(50, 255, 50, 1)',
                        borderColor: '#fff',
                        borderWidth: 1,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    },
                    {
                        type: 'scatter',
                        label: 'Notes',
                        data: [],
                        backgroundColor: 'rgba(255, 50, 50, 1)',
                        borderColor: '#fff',
                        borderWidth: 1,
                        pointStyle: 'triangle',
                        pointRadius: 8,
                        pointHoverRadius: 10
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Horizontal orientation for Bars. X is the value axis.
                scales: {
                    x: {
                        type: 'logarithmic',
                        title: { display: true, text: 'Frequency (Hz)', color: '#aaa' },
                        grid: { color: '#222' },
                        ticks: { color: '#888', callback: function(value) { return formatFreq(value); } },
                        min: 1, // Log scale can't go to 0
                    },
                    y: {
                        // This axis is just for "stacking" our visualization layers
                        // We will map Categories or arbitrary indices to it.
                        type: 'category',
                        labels: ['Allocations', 'Channels', 'Notes'],
                        grid: { color: '#333' },
                        ticks: { color: '#fff', font: { size: 14, weight: 'bold'} }
                    }
                },
                plugins: {
                    legend: { labels: { color: '#ccc' } },
                    tooltip: {
                        backgroundColor: 'rgba(10,10,10,0.9)',
                        titleColor: '#fff',
                        bodyColor: '#ddd',
                        borderColor: '#555',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                let item = context.raw;
                                let dsLabel = context.dataset.label;

                                if (dsLabel === 'Allocations') {
                                    // For bar, item is [start, end] usually, or x object
                                    // In indexAxis:'y', x is [start, end]
                                    // But we passed objects {x: [start, end], y: 'Allocations'}
                                    // context.raw might be the object.
                                    // Wait, for Log scale bars, Chart.js 3+ handles floating bars better.
                                    let start = item.x[0];
                                    let end = item.x[1];
                                    return `${item.desc}: ${formatFreq(start)} - ${formatFreq(end)}`;
                                } else {
                                    return `${item.title || item.name}: ${formatFreq(item.x)}`;
                                }
                            }
                        }
                    },
                    zoom: {
                        zoom: {
                            wheel: { enabled: true },
                            pinch: { enabled: true },
                            mode: 'x',
                        },
                        pan: {
                            enabled: true,
                            mode: 'x',
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length > 0) {
                        const idx = elements[0].datasetIndex;
                        const dataIdx = elements[0].index;
                        const item = spectrumChart.data.datasets[idx].data[dataIdx];
                        let info = '';
                        if (idx === 0) { // Allocation
                            info = `${item.desc}\nRange: ${formatFreq(item.x[0])} - ${formatFreq(item.x[1])}`;
                        } else {
                            info = `${item.name || item.title}\nFrequency: ${formatFreq(item.x)}\n${item.desc || item.obj.content || ''}`;
                        }
                        alert(info);
                    }
                }
            }
        });
    }

    function formatFreq(hz) {
        if (hz >= 1e9) return (hz / 1e9).toFixed(4) + ' GHz';
        if (hz >= 1e6) return (hz / 1e6).toFixed(4) + ' MHz';
        if (hz >= 1e3) return (hz / 1e3).toFixed(4) + ' kHz';
        return hz + ' Hz';
    }

    // Load Chart Data (Full Range)
    async function loadChartData() {
        const min = document.getElementById('minFreq').value;
        const max = document.getElementById('maxFreq').value;

        const res = await fetch(`api.php?action=chart_data&min=${min}&max=${max}`);
        const data = await res.json();

        // Process Channels (Scatter)
        // Map to Y='Channels'
        const chanPoints = data.channels.map(c => ({
            x: c.frequency, y: 'Channels', name: c.name, desc: c.description, obj: c
        }));

        // Process Notes (Scatter)
        // Map to Y='Notes'
        const notePoints = data.notes.map(n => ({
            x: n.frequency_start, y: 'Notes', title: n.title, desc: n.content, obj: n
        }));

        // Process Allocations (Floating Bar)
        // Map to Y='Allocations' with X as [start, end]
        // Note: For Log scale, we must ensure values > 0. 1 Hz min.
        const allocBars = data.allocations.map(a => ({
            x: [Math.max(1, a.start_freq), Math.max(1, a.end_freq)],
            y: 'Allocations',
            desc: a.description,
            obj: a
        }));

        spectrumChart.data.datasets[0].data = allocBars;
        spectrumChart.data.datasets[1].data = chanPoints;
        spectrumChart.data.datasets[2].data = notePoints;
        spectrumChart.update();
    }

    // Load Table Data (Paginated)
    async function loadTableData() {
        let url = `api.php?action=${searchActive ? 'search' : 'list_all'}&page=${currentPage}&limit=${currentLimit}`;
        if (searchActive) {
            url += `&q=${encodeURIComponent(searchQuery)}`;
        } else {
            url += `&sort=${currentSort}&order=${currentOrder}`;
        }

        const res = await fetch(url);
        const json = await res.json();

        // Search endpoint returns {data: [...]}, list_all returns {data: [...], total: ...}
        // Unify for rendering
        const items = json.data;
        const total = json.total || items.length; // Approximate for search if not provided

        renderTable(items);
        renderPagination(total);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function renderTable(items) {
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = '';

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No data found</td></tr>';
            return;
        }

        items.forEach(item => {
            const tr = document.createElement('tr');

            let freq = item.val ? formatFreq(item.val) : '';
            if (item.type === 'allocation' && item.description && item.description.startsWith('Range:')) {
                // If it's a range description, keep it, or format it nicely?
                // The API sends description as "Range: start - end"
            }

            let typeBadge = '';
            switch(item.type) {
                case 'channel': typeBadge = '<span class="badge bg-success">Channel</span>'; break;
                case 'allocation': typeBadge = '<span class="badge bg-primary">Allocation</span>'; break;
                case 'note': typeBadge = '<span class="badge bg-danger">Note</span>'; break;
            }

            let loc = '';
            if (item.lat || item.lon) {
                loc = `<small><i class="bi bi-geo-alt"></i> ${escapeHtml(item.lat || '?')}, ${escapeHtml(item.lon || '?')}</small>`;
                if (item.az) loc += `<br><small><i class="bi bi-compass"></i> ${escapeHtml(item.az)}°</small>`;
            } else {
                loc = '<span class="text-muted">-</span>';
            }

            tr.innerHTML = `
                <td>${escapeHtml(freq)}</td>
                <td>${escapeHtml(item.title || '-')}</td>
                <td>${escapeHtml(item.category || '-')}</td>
                <td>${typeBadge}</td>
                <td class="text-wrap" style="max-width: 300px;">${escapeHtml(item.description || '')}</td>
                <td>${loc}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function renderPagination(totalItems) {
        const totalPages = Math.ceil(totalItems / currentLimit);
        const pagination = document.getElementById('pagination');
        const pageInfo = document.getElementById('pageInfo');

        // Info text
        const start = (currentPage - 1) * currentLimit + 1;
        const end = Math.min(currentPage * currentLimit, totalItems);
        pageInfo.textContent = `Showing ${totalItems > 0 ? start : 0}-${end} of ${totalItems}`;

        // Pagination buttons
        let html = '';

        // Prev
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a>
                 </li>`;

        // Simple window: First, Last, Current +/- 1
        // For simplicity, just show current and neighbors or simple range
        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                     </li>`;
        }

        // Next
        html += `<li class="page-item ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a>
                 </li>`;

        pagination.innerHTML = html;

        // Attach events
        pagination.querySelectorAll('a.page-link').forEach(a => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                const p = parseInt(e.target.dataset.page);
                if (!isNaN(p) && p > 0 && p <= totalPages) {
                    currentPage = p;
                    loadTableData();
                }
            });
        });
    }

    // Sort Handlers
    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const sort = th.dataset.sort;
            if (currentSort === sort) {
                currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort = sort;
                currentOrder = 'asc';
            }
            currentPage = 1; // Reset to page 1 on sort
            searchActive = false; // Sorting usually applies to the full list, if we are in search mode we might want to stay there but sort search results.
            // The API handles sort for 'list_all', but 'search' results are usually relevance or just frequency.
            // Let's assume sorting resets to list_all for simplicity unless we enhance search API.
            // Actually, let's keep it simple: Sorting only for list_all.
            if (!searchActive) loadTableData();
        });
    });

    // Search
    document.getElementById('btnSearch').addEventListener('click', performSearch);
    document.getElementById('searchInput').addEventListener('keyup', (e) => {
        if (e.key === 'Enter') performSearch();
    });

    function performSearch() {
        const q = document.getElementById('searchInput').value.trim();
        if (q) {
            searchActive = true;
            searchQuery = q;
        } else {
            searchActive = false;
        }
        currentPage = 1;
        loadTableData();
    }

    // Update Chart
    document.getElementById('updateView').addEventListener('click', loadChartData);

    // Handle Entry Type Change
    document.getElementById('entryType').addEventListener('change', (e) => {
        const type = e.target.value;
        const noteFields = document.getElementById('noteFields');
        const channelFields = document.getElementById('channelFields');
        const freqEnd = document.getElementById('entryFreqEnd');

        if (type === 'note') {
            noteFields.classList.remove('d-none');
            channelFields.classList.add('d-none');
            freqEnd.disabled = true;
            freqEnd.value = '';
        } else if (type === 'channel') {
            noteFields.classList.add('d-none');
            channelFields.classList.remove('d-none');
            freqEnd.disabled = true;
            freqEnd.value = '';
        } else if (type === 'allocation') {
            noteFields.classList.add('d-none');
            channelFields.classList.add('d-none');
            freqEnd.disabled = false;
        }
    });

    // Add Entry
    document.getElementById('addEntryForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const type = document.getElementById('entryType').value;
        const data = {
            type: type,
            freq_start: document.getElementById('entryFreqStart').value,
            freq_end: document.getElementById('entryFreqEnd').value,
            title: document.getElementById('entryTitle').value,
            category: document.getElementById('entryCategory').value,
            content: document.getElementById('entryContent').value,
        };

        if (type === 'note') {
            data.latitude = document.getElementById('entryLat').value;
            data.longitude = document.getElementById('entryLon').value;
            data.azimuth = document.getElementById('entryAz').value;
        } else if (type === 'channel') {
            data.bandwidth = document.getElementById('entryBW').value;
            data.modulation = document.getElementById('entryMod').value;
        }

        await fetch('api.php?action=add_entry', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        loadChartData();
        loadTableData();
        document.getElementById('addEntryForm').reset();
        // Reset view state
        document.getElementById('entryType').dispatchEvent(new Event('change'));
    });

    // Init
    initChart();
    loadChartData();
    loadTableData();
});
