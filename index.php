<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spectrum Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>
</head>
<body class="spectrum-theme">
    <nav class="navbar navbar-dark bg-black border-bottom border-rgb sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand spectrum-text d-flex align-items-center" href="#">
                <i class="bi bi-broadcast me-2"></i>
                <span class="text-red">R</span><span class="text-green">G</span><span class="text-blue">B</span> Spectrum
            </a>
            <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (Collapsible on mobile) -->
            <div class="col-md-3 col-lg-2 collapse d-md-block bg-dark-glass p-3 border-end border-primary sidebar" id="sidebarMenu">
                <h5 class="text-info mt-3"><i class="bi bi-sliders"></i> Controls</h5>
                <div class="mb-4">
                    <label class="form-label text-light text-small">Min Freq (Hz)</label>
                    <input type="number" class="form-control form-control-sm bg-dark text-light border-secondary" id="minFreq" value="0">

                    <label class="form-label text-light text-small mt-2">Max Freq (Hz)</label>
                    <input type="number" class="form-control form-control-sm bg-dark text-light border-secondary" id="maxFreq" value="10000000000">

                    <button class="btn btn-sm btn-outline-primary w-100 mt-3" id="updateView">
                        <i class="bi bi-arrow-repeat"></i> Update Graph
                    </button>
                </div>

                <hr class="border-light">

                <h5 class="text-warning"><i class="bi bi-pencil-square"></i> Add Entry</h5>
                <form id="addEntryForm">
                    <div class="mb-2">
                        <label class="form-label text-white small">Type</label>
                        <select class="form-select form-select-sm bg-dark text-light border-secondary" id="entryType">
                            <option value="note" selected>Note</option>
                            <option value="channel">Channel</option>
                            <option value="allocation">Range / Allocation</option>
                        </select>
                    </div>

                    <div class="row g-1 mb-2">
                        <div class="col-6">
                            <label class="form-label text-white small">Start Freq (Hz)</label>
                            <input type="number" step="0.0001" class="form-control form-control-sm bg-dark text-light border-secondary" id="entryFreqStart" placeholder="Freq Start (Hz)" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-white small">End Freq (Hz)</label>
                            <input type="number" step="0.0001" class="form-control form-control-sm bg-dark text-light border-secondary" id="entryFreqEnd" placeholder="Freq End (Hz)" disabled>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-white small">Title</label>
                        <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary" id="entryTitle" placeholder="Title / Name" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-white small">Category</label>
                        <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary" id="entryCategory" placeholder="Category (e.g. HAM, WiFi)">
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-white small">Content/Description</label>
                        <textarea class="form-control form-control-sm bg-dark text-light border-secondary" id="entryContent" placeholder="Description / Content" rows="2"></textarea>
                    </div>

                    <!-- Channel Specific -->
                    <div id="channelFields" class="d-none">
                        <div class="row g-1 mb-2">
                            <div class="col-6">
                                <label class="form-label text-white small">Bandwidth (Hz)</label>
                                <input type="number" class="form-control form-control-sm bg-dark text-light border-secondary" id="entryBW" placeholder="BW (Hz)">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-white small">Modulation</label>
                                <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary" id="entryMod" placeholder="Modulation">
                            </div>
                        </div>
                    </div>

                    <!-- Note Specific (Location) -->
                    <div id="noteFields">
                        <div class="row g-1 mb-2">
                            <div class="col-6">
                                <label class="form-label text-white small">Latitude</label>
                                <input type="number" step="0.000001" class="form-control form-control-sm bg-dark text-light border-secondary" id="entryLat" placeholder="Lat">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-white small">Longitude</label>
                                <input type="number" step="0.000001" class="form-control form-control-sm bg-dark text-light border-secondary" id="entryLon" placeholder="Lon">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Azimuth</label>
                            <input type="number" step="0.1" class="form-control form-control-sm bg-dark text-light border-secondary" id="entryAz" placeholder="Azimuth (0-360)">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-sm btn-warning w-100">Save</button>
                </form>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">

                <!-- Spectrum Graph -->
                <div class="card bg-black border-rgb mb-4 shadow-lg">
                    <div class="card-header text-light bg-transparent border-bottom border-secondary d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fw-bold"><i class="bi bi-graph-up"></i> Spectrum Analyzer</span>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary text-light" id="zoomIn" title="Zoom In"><i class="bi bi-plus-lg"></i></button>
                                <button class="btn btn-outline-secondary text-light" id="zoomOut" title="Zoom Out"><i class="bi bi-dash-lg"></i></button>
                                <button class="btn btn-outline-secondary text-light" id="resetZoom" title="Reset Zoom"><i class="bi bi-arrows-fullscreen"></i></button>
                            </div>
                        </div>
                        <small class="text-muted d-none d-md-block">Logarithmic Scale (Scroll/Pinch to Zoom)</small>
                    </div>
                    <div class="card-body p-2">
                        <div class="chart-container" style="position: relative; height:30vh; width:100%">
                            <canvas id="spectrumChart"></canvas>
                        </div>
                    </div>
                    <!-- Selection Details (Hidden by default) -->
                    <div id="selectionDetails" class="card-footer bg-dark-glass text-light border-top border-secondary d-none">
                        <div class="d-flex justify-content-between align-items-start">
                            <div id="selectionContent">
                                <!-- Details injected here -->
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" id="clearSelection" title="Clear Selection">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Data Table Control Bar -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-6">
                        <h4 class="text-light"><i class="bi bi-table"></i> Frequency Data</h4>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control bg-dark text-light border-secondary" id="searchInput" placeholder="Search data...">
                            <button class="btn btn-outline-success" type="button" id="btnSearch">Search</button>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive bg-dark-glass rounded border border-secondary">
                    <table class="table table-dark table-hover mb-0" id="freqTable">
                        <thead>
                            <tr class="text-secondary">
                                <th scope="col" class="sortable" data-sort="val">Freq (Hz) <i class="bi bi-arrow-down-up"></i></th>
                                <th scope="col" class="sortable" data-sort="title">Title/Name <i class="bi bi-arrow-down-up"></i></th>
                                <th scope="col" class="sortable" data-sort="category">Category <i class="bi bi-arrow-down-up"></i></th>
                                <th scope="col">Type</th>
                                <th scope="col">Description/Notes</th>
                                <th scope="col">Location</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav class="mt-3 d-flex justify-content-between align-items-center">
                    <div class="text-muted small" id="pageInfo">Showing 0-0 of 0</div>
                    <ul class="pagination pagination-sm mb-0" id="pagination">
                        <!-- Pagination injected by JS -->
                    </ul>
                </nav>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
