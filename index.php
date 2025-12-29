<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spectrum Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>
</head>
<body class="spectrum-theme">
    <nav class="navbar navbar-dark bg-black border-bottom border-rgb">
        <div class="container-fluid">
            <a class="navbar-brand spectrum-text" href="#">
                <span class="text-red">R</span><span class="text-green">G</span><span class="text-blue">B</span> Spectrum
            </a>
            <form class="d-flex" id="searchForm">
                <input class="form-control me-2 bg-dark text-light border-secondary" type="search" placeholder="Search Freq/Name" aria-label="Search" id="searchInput">
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <div class="row">
            <!-- Sidebar / Controls -->
            <div class="col-md-3 bg-dark-glass p-3 rounded border-start border-end border-primary">
                <h5 class="text-info">Controls</h5>
                <div class="mb-3">
                    <label class="form-label text-light">Frequency Range (Hz)</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-secondary text-light">Min</span>
                        <input type="number" class="form-control bg-dark text-light" id="minFreq" value="0">
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-secondary text-light">Max</span>
                        <input type="number" class="form-control bg-dark text-light" id="maxFreq" value="10000000000">
                    </div>
                    <button class="btn btn-primary w-100 mt-2" id="updateView">Update View</button>
                </div>

                <hr class="border-light">

                <h5 class="text-warning">Add Note</h5>
                <form id="addNoteForm">
                    <div class="mb-2">
                        <input type="number" step="0.0001" class="form-control bg-dark text-light" id="noteFreq" placeholder="Frequency (Hz)" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" class="form-control bg-dark text-light" id="noteTitle" placeholder="Title" required>
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control bg-dark text-light" id="noteContent" placeholder="Content" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Save Note</button>
                </form>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Spectrum Graph -->
                <div class="card bg-black border-rgb mb-4">
                    <div class="card-header text-light">Spectrum Analyzer</div>
                    <div class="card-body">
                        <canvas id="spectrumChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Data List -->
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="text-light border-bottom border-danger pb-2">Results / Details</h4>
                        <div id="resultsArea" class="list-group bg-transparent">
                            <!-- Dynamic Content -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
