// script.js
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('spectrumChart').getContext('2d');

    // Initialize empty chart
    let spectrumChart = new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [
                {
                    label: 'Channels',
                    data: [],
                    backgroundColor: 'rgba(50, 255, 50, 0.8)',
                    borderColor: 'rgba(50, 255, 50, 1)',
                    pointRadius: 5,
                    pointHoverRadius: 8
                },
                {
                    label: 'Allocations', // We will represent allocations as lines (start/end)
                    data: [],
                    backgroundColor: 'rgba(50, 50, 255, 0.2)',
                    borderColor: 'rgba(50, 50, 255, 0.5)',
                    borderWidth: 10,
                    showLine: true,
                    pointRadius: 0
                },
                {
                    label: 'Notes',
                    data: [],
                    backgroundColor: 'rgba(255, 50, 50, 0.8)',
                    borderColor: 'rgba(255, 50, 50, 1)',
                    pointStyle: 'triangle',
                    pointRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'logarithmic',
                    title: { display: true, text: 'Frequency (Hz)', color: '#fff' },
                    grid: { color: '#333' },
                    ticks: { color: '#aaa', callback: function(value) { return formatFreq(value); } }
                },
                y: {
                    display: false,
                    min: 0,
                    max: 10
                }
            },
            plugins: {
                legend: { labels: { color: '#fff' } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let item = context.raw;
                            return `${item.name || item.desc}: ${formatFreq(item.x)}`;
                        }
                    }
                }
            },
            onClick: (e, elements) => {
                if (elements.length > 0) {
                    const idx = elements[0].datasetIndex;
                    const dataIdx = elements[0].index;
                    const item = spectrumChart.data.datasets[idx].data[dataIdx];
                    showDetails(item);
                }
            }
        }
    });

    function formatFreq(hz) {
        if (hz >= 1e9) return (hz / 1e9).toFixed(4) + ' GHz';
        if (hz >= 1e6) return (hz / 1e6).toFixed(4) + ' MHz';
        if (hz >= 1e3) return (hz / 1e3).toFixed(4) + ' kHz';
        return hz + ' Hz';
    }

    async function loadData() {
        const min = document.getElementById('minFreq').value;
        const max = document.getElementById('maxFreq').value;

        // Fetch Allocations
        const allocRes = await fetch(`api.php?action=allocations&min=${min}&max=${max}`);
        const allocations = await allocRes.json();

        // Fetch Channels
        const chanRes = await fetch(`api.php?action=channels&min=${min}&max=${max}`);
        const channels = await chanRes.json();

        // Fetch Notes
        const noteRes = await fetch(`api.php?action=notes&min=${min}&max=${max}`);
        const notes = await noteRes.json();

        updateChart(allocations, channels, notes);
        populateList([...allocations, ...channels, ...notes]);
    }

    function updateChart(allocs, chans, notesData) {
        // Channels points
        const chanPoints = chans.map(c => ({ x: c.frequency, y: 5, name: c.name, desc: c.description, obj: c }));

        // Notes points
        const notePoints = notesData.map(n => ({ x: n.frequency_start, y: 7, name: n.title, desc: n.content, obj: n }));

        // Allocations (as horizontal bars - approximated with many points or just start/end for simplicity in scatter)
        // Better visualization for ranges would be 'bar' chart on floating values or annotations, but let's try line segments in scatter
        const allocLines = [];
        allocs.forEach((a, i) => {
            // Assign a random Y level to avoid overlap
            const y = 2 + (i % 3);
            allocLines.push({ x: a.start_freq, y: y, desc: a.description, obj: a });
            allocLines.push({ x: a.end_freq, y: y, desc: a.description, obj: a });
            allocLines.push({ x: null, y: null }); // Break line
        });

        spectrumChart.data.datasets[0].data = chanPoints;
        spectrumChart.data.datasets[1].data = allocLines;
        spectrumChart.data.datasets[2].data = notePoints;
        spectrumChart.update();
    }

    function populateList(items) {
        const list = document.getElementById('resultsArea');
        list.innerHTML = '';

        // Sort by frequency (start_freq or frequency)
        items.sort((a, b) => {
            let freqA = a.frequency || a.start_freq || a.frequency_start;
            let freqB = b.frequency || b.start_freq || b.frequency_start;
            return freqA - freqB;
        });

        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'list-group-item list-group-item-dark mb-1 rounded';

            let title = item.name || item.description || item.title;
            let freq = item.frequency ? formatFreq(item.frequency) :
                       (item.start_freq ? `${formatFreq(item.start_freq)} - ${formatFreq(item.end_freq)}` :
                       formatFreq(item.frequency_start));
            let type = item.start_freq ? 'Allocation' : (item.frequency ? 'Channel' : 'Note');
            let content = item.content || item.description || '';
            let cat = item.category ? `<span class="badge bg-secondary">${item.category}</span>` : '';

            div.innerHTML = `
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1 text-primary">${title}</h5>
                    <small class="text-warning">${freq}</small>
                </div>
                <p class="mb-1">${content}</p>
                <small class="text-muted">${type} ${cat}</small>
            `;

            // Click to zoom/focus (simple implementation)
            div.onclick = () => showDetails(item);
            list.appendChild(div);
        });
    }

    function showDetails(item) {
        // Just highlight in list or alert for now
        // A real app might open a modal
        console.log('Selected:', item);
    }

    document.getElementById('updateView').addEventListener('click', loadData);

    document.getElementById('addNoteForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const freq = document.getElementById('noteFreq').value;
        const title = document.getElementById('noteTitle').value;
        const content = document.getElementById('noteContent').value;

        await fetch('api.php?action=add_note', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ freq_start: freq, title, content })
        });

        loadData();
        document.getElementById('addNoteForm').reset();
    });

    document.getElementById('searchForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const q = document.getElementById('searchInput').value;
        const res = await fetch(`api.php?action=search&q=${q}`);
        const results = await res.json();
        populateList(results);
    });

    // Initial load
    loadData();
});
