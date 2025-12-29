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

        spectrumChart = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [
                    {
                        type: 'bar',
                        label: 'Allocations',
                        data: [],
                        backgroundColor: [],
                        borderColor: [],
                        borderWidth: 1,
                        indexAxis: 'y',
                        barThickness: 20,
                    },
                    {
                        type: 'scatter',
                        label: 'Channels',
                        data: [],
                        backgroundColor: [],
                        borderColor: [],
                        borderWidth: 1,
                        pointRadius: [],
                        pointHoverRadius: 8
                    },
                    {
                        type: 'scatter',
                        label: 'Notes',
                        data: [],
                        backgroundColor: [],
                        borderColor: [],
                        borderWidth: 1,
                        pointStyle: 'triangle',
                        pointRadius: [],
                        pointHoverRadius: 10
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        type: 'logarithmic',
                        title: { display: true, text: 'Frequency (Hz)', color: '#aaa' },
                        grid: { color: '#222' },
                        ticks: { color: '#888', callback: function(value) { return formatFreq(value); } },
                        min: 1,
                    },
                    y: {
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
                    const detailsDiv = document.getElementById('selectionDetails');
                    const contentDiv = document.getElementById('selectionContent');

                    resetChartHighlight();

                    if (elements.length > 0) {
                        const idx = elements[0].datasetIndex;
                        const dataIdx = elements[0].index;
                        const item = spectrumChart.data.datasets[idx].data[dataIdx];

                        highlightItem(idx, dataIdx);

                        detailsDiv.classList.remove('d-none');

                        let html = '';
                        if (idx === 0) { // Allocation
                            html = `<strong class="text-primary">${escapeHtml(item.desc)}</strong><br>
                                    <span class="text-info">${formatFreq(item.x[0])} - ${formatFreq(item.x[1])}</span><br>
                                    <small class="text-muted">Category: ${escapeHtml(item.obj.category)}</small>`;
                        } else {
                            html = `<strong class="text-primary">${escapeHtml(item.name || item.title)}</strong><br>
                                    <span class="text-warning">${formatFreq(item.x)}</span><br>
                                    <p class="mb-0 small">${escapeHtml(item.desc || item.obj.content || '')}</p>`;
                            if (item.obj.latitude) {
                                html += `<small class="text-muted"><i class="bi bi-geo-alt"></i> ${item.obj.latitude}, ${item.obj.longitude} (${item.obj.azimuth}°)</small>`;
                            }
                        }
                        contentDiv.innerHTML = html;
                    } else {
                        detailsDiv.classList.add('d-none');
                        contentDiv.innerHTML = '';
                    }
                    spectrumChart.update();
                }
            }
        });

        // Clear Selection Button
        document.getElementById('clearSelection').addEventListener('click', () => {
            document.getElementById('selectionDetails').classList.add('d-none');
            document.getElementById('selectionContent').innerHTML = '';
            resetChartHighlight();
            spectrumChart.update();
        });

        // Zoom Controls
        document.getElementById('zoomIn').addEventListener('click', () => {
            spectrumChart.zoom(1.1);
        });
        document.getElementById('zoomOut').addEventListener('click', () => {
            spectrumChart.zoom(0.9);
        });
        document.getElementById('resetZoom').addEventListener('click', () => {
            spectrumChart.resetZoom();
        });
    }

    const defaultColors = {
        0: { bg: 'rgba(50, 50, 255, 0.2)', border: 'rgba(50, 50, 255, 0.8)' },
        1: { bg: 'rgba(50, 255, 50, 1)', border: '#fff' },
        2: { bg: 'rgba(255, 50, 50, 1)', border: '#fff' }
    };

    function resetChartHighlight() {
        spectrumChart.data.datasets.forEach((ds, dsIdx) => {
            const len = ds.data.length;
            const def = defaultColors[dsIdx];
            ds.backgroundColor = new Array(len).fill(def.bg);
            ds.borderColor = new Array(len).fill(def.border);
            if (dsIdx > 0) ds.pointRadius = new Array(len).fill(dsIdx === 1 ? 6 : 8);
        });
    }

    function highlightItem(dsIdx, dataIdx) {
        const ds = spectrumChart.data.datasets[dsIdx];
        ds.backgroundColor[dataIdx] = 'rgba(255, 255, 255, 0.9)';
        ds.borderColor[dataIdx] = '#ffff00';
        if (dsIdx > 0) {
            ds.pointRadius[dataIdx] = 12;
        }
    }

    function formatFreq(hz) {
        if (hz >= 1e9) return (hz / 1e9).toFixed(4) + ' GHz';
        if (hz >= 1e6) return (hz / 1e6).toFixed(4) + ' MHz';
        if (hz >= 1e3) return (hz / 1e3).toFixed(4) + ' kHz';
        return hz + ' Hz';
    }

    async function loadChartData() {
        const min = document.getElementById('minFreq').value;
        const max = document.getElementById('maxFreq').value;

        const res = await fetch(`api.php?action=chart_data&min=${min}&max=${max}`);
        const data = await res.json();

        const chanPoints = data.channels.map(c => ({
            x: c.frequency, y: 'Channels', name: c.name, desc: c.description, obj: c
        }));

        const notePoints = data.notes.map(n => ({
            x: n.frequency_start, y: 'Notes', title: n.title, desc: n.content, obj: n
        }));

        const allocBars = data.allocations.map(a => ({
            x: [Math.max(1, a.start_freq), Math.max(1, a.end_freq)],
            y: 'Allocations',
            desc: a.description,
            obj: a
        }));

        spectrumChart.data.datasets[0].data = allocBars;
        spectrumChart.data.datasets[1].data = chanPoints;
        spectrumChart.data.datasets[2].data = notePoints;

        resetChartHighlight();
        spectrumChart.update();
    }

    async function loadTableData() {
        let url = `api.php?action=${searchActive ? 'search' : 'list_all'}&page=${currentPage}&limit=${currentLimit}`;
        if (searchActive) {
            url += `&q=${encodeURIComponent(searchQuery)}`;
        } else {
            url += `&sort=${currentSort}&order=${currentOrder}`;
        }

        const res = await fetch(url);
        const json = await res.json();

        const items = json.data;
        const total = json.total || items.length;

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

            // Locate Button Logic
            let locateBtn = `<button class="btn btn-sm btn-link text-info p-0 ms-2 locate-btn"
                                data-val="${item.val}"
                                data-end="${item.end_val || ''}"
                                title="Locate on Graph">
                                <i class="bi bi-search"></i>
                             </button>`;

            tr.innerHTML = `
                <td>
                    ${escapeHtml(freq)}
                    ${locateBtn}
                </td>
                <td>${escapeHtml(item.title || '-')}</td>
                <td>${escapeHtml(item.category || '-')}</td>
                <td>${typeBadge}</td>
                <td class="text-wrap" style="max-width: 300px;">${escapeHtml(item.description || '')}</td>
                <td>${loc}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Locate Handler
    document.getElementById('tableBody').addEventListener('click', (e) => {
        const btn = e.target.closest('.locate-btn');
        if (btn) {
            e.stopPropagation();
            const val = parseFloat(btn.dataset.val);
            const end = btn.dataset.end ? parseFloat(btn.dataset.end) : null;
            locateItem(val, end);
        }
    });

    function locateItem(start, end) {
        let min, max;
        // Log scale safety
        if (start < 1) start = 1;

        if (end && end > start) {
            // Range
            // Center around the range with some padding
            // Log scale padding is multiplicative
            // Let's show a bit wider than the range
            min = start * 0.8;
            max = end * 1.25;
        } else {
            // Point
            // Show +/- a factor around the point
            min = start * 0.5;
            max = start * 2.0;
        }

        if (min < 1) min = 1;

        if (spectrumChart && spectrumChart.scales.x) {
            spectrumChart.zoomScale('x', {min, max}, 'default');
            document.querySelector('.card').scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    }

    function renderPagination(totalItems) {
        const totalPages = Math.ceil(totalItems / currentLimit);
        const pagination = document.getElementById('pagination');
        const pageInfo = document.getElementById('pageInfo');

        const start = (currentPage - 1) * currentLimit + 1;
        const end = Math.min(currentPage * currentLimit, totalItems);
        pageInfo.textContent = `Showing ${totalItems > 0 ? start : 0}-${end} of ${totalItems}`;

        let html = '';

        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a>
                 </li>`;

        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                     </li>`;
        }

        html += `<li class="page-item ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a>
                 </li>`;

        pagination.innerHTML = html;

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

    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const sort = th.dataset.sort;
            if (currentSort === sort) {
                currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort = sort;
                currentOrder = 'asc';
            }
            currentPage = 1;
            searchActive = false;
            loadTableData();
        });
    });

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

    document.getElementById('updateView').addEventListener('click', loadChartData);

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
        document.getElementById('entryType').dispatchEvent(new Event('change'));
    });

    initChart();
    loadChartData();
    loadTableData();
});
