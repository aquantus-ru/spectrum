<?php
// api.php
require 'database.php';

header('Content-Type: application/json');

$pdo = Database::connect();
$action = $_GET['action'] ?? '';

// Pagination helper
function getPaginationParams() {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    if ($page < 1) $page = 1;
    if ($limit < 1) $limit = 20;
    return ['offset' => ($page - 1) * $limit, 'limit' => $limit];
}

try {
    if ($action === 'search') {
        $query = $_GET['q'] ?? '';
        $term = "%$query%";
        $isNumeric = is_numeric($query);
        $freqVal = $isNumeric ? (float)$query : null;

        // Base search on text fields
        // For numeric inputs, we also check if the value is within an allocation/note range
        // or matches a channel frequency directly.

        // CHANNELS
        $chanWhere = "name LIKE ? OR description LIKE ? OR category LIKE ? OR CAST(frequency AS TEXT) LIKE ?";
        $chanParams = [$term, $term, $term, $term];

        // ALLOCATIONS
        // Search text OR start/end freq strings OR (if numeric) value inside range
        $allocWhere = "description LIKE ? OR category LIKE ? OR CAST(start_freq AS TEXT) LIKE ? OR CAST(end_freq AS TEXT) LIKE ?";
        $allocParams = [$term, $term, $term, $term];
        if ($isNumeric) {
            $allocWhere .= " OR (? >= start_freq AND ? <= end_freq)";
            $allocParams[] = $freqVal;
            $allocParams[] = $freqVal;
        }

        // NOTES
        $noteWhere = "title LIKE ? OR content LIKE ? OR CAST(frequency_start AS TEXT) LIKE ?";
        $noteParams = [$term, $term, $term];
        if ($isNumeric) {
            $noteWhere .= " OR (? >= frequency_start AND ? <= frequency_end)";
            $noteParams[] = $freqVal;
            $noteParams[] = $freqVal;
        }

        // Construct Main Query
        $sql = "SELECT 'channel' as type, id, frequency as val, name as title, description, category, NULL as lat, NULL as lon, NULL as az
                FROM channels
                WHERE $chanWhere
                UNION
                SELECT 'allocation' as type, id, start_freq as val, description as title, 'Range: ' || start_freq || ' - ' || end_freq as description, category, NULL as lat, NULL as lon, NULL as az
                FROM spectrum_allocations
                WHERE $allocWhere
                UNION
                SELECT 'note' as type, id, frequency_start as val, title, content as description, 'User Note' as category, latitude as lat, longitude as lon, azimuth as az
                FROM notes
                WHERE $noteWhere
                ORDER BY val ASC";

        $pg = isset($_GET['page']) ? getPaginationParams() : ['limit' => 20, 'offset' => 0];

        // Combine all parameters
        $allParams = array_merge($chanParams, $allocParams, $noteParams);

        // Count Query
        $countSql = "SELECT COUNT(*) as total FROM (
            SELECT 1 FROM channels WHERE $chanWhere
            UNION ALL
            SELECT 1 FROM spectrum_allocations WHERE $allocWhere
            UNION ALL
            SELECT 1 FROM notes WHERE $noteWhere
        )";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($allParams);
        $total = $countStmt->fetch()['total'];

        // Data Query with Limit
        $sql .= " LIMIT " . $pg['limit'] . " OFFSET " . $pg['offset'];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($allParams);

        echo json_encode([
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => ($pg['offset'] / $pg['limit']) + 1,
            'limit' => $pg['limit']
        ]);

    } elseif ($action === 'list_all') {
        // Combined list for the table with sorting and pagination
        $pg = getPaginationParams();
        $sort = $_GET['sort'] ?? 'val';
        $order = strtolower($_GET['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

        // Whitelist sort columns to prevent injection
        $allowedSorts = ['val', 'title', 'category', 'type'];
        if (!in_array($sort, $allowedSorts)) $sort = 'val';

        $sql = "SELECT * FROM (
                    SELECT 'channel' as type, id, frequency as val, name as title, description, category, NULL as lat, NULL as lon, NULL as az FROM channels
                    UNION
                    SELECT 'allocation' as type, id, start_freq as val, description as title, 'Range: ' || start_freq || ' - ' || end_freq as description, category, NULL as lat, NULL as lon, NULL as az FROM spectrum_allocations
                    UNION
                    SELECT 'note' as type, id, frequency_start as val, title, content as description, 'User Note' as category, latitude as lat, longitude as lon, azimuth as az FROM notes
                ) as combined
                ORDER BY $sort $order
                LIMIT :limit OFFSET :offset";

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM (
                    SELECT 1 FROM channels
                    UNION ALL
                    SELECT 1 FROM spectrum_allocations
                    UNION ALL
                    SELECT 1 FROM notes
                )";
        $countStmt = $pdo->query($countSql);
        $total = $countStmt->fetch()['total'];

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $pg['limit'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pg['offset'], PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => ($pg['offset'] / $pg['limit']) + 1,
            'limit' => $pg['limit']
        ]);

    } elseif ($action === 'chart_data') {
        // Return all data for the chart (no pagination, but filtered by range)
        // We do this separately to keep the chart populated regardless of the table page
        $min = $_GET['min'] ?? 0;
        $max = $_GET['max'] ?? 10000000000;

        // Allocations
        $stmtAlloc = $pdo->prepare("SELECT * FROM spectrum_allocations WHERE end_freq >= ? AND start_freq <= ? ORDER BY start_freq ASC");
        $stmtAlloc->execute([$min, $max]);
        $allocs = $stmtAlloc->fetchAll();

        // Channels
        $stmtChan = $pdo->prepare("SELECT * FROM channels WHERE frequency >= ? AND frequency <= ? ORDER BY frequency ASC");
        $stmtChan->execute([$min, $max]);
        $chans = $stmtChan->fetchAll();

        // Notes
        $stmtNote = $pdo->prepare("SELECT * FROM notes WHERE frequency_start >= ? AND frequency_start <= ? ORDER BY frequency_start ASC");
        $stmtNote->execute([$min, $max]);
        $notes = $stmtNote->fetchAll();

        echo json_encode([
            'allocations' => $allocs,
            'channels' => $chans,
            'notes' => $notes
        ]);

    } elseif ($action === 'add_entry' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $type = $data['type'] ?? 'note';
        $freq_start = $data['freq_start'];
        $freq_end = $data['freq_end'] ?? $freq_start;
        // If single point (channel/note), ensure end == start
        if ($type === 'channel' || ($type === 'note' && empty($data['freq_end']))) {
            $freq_end = $freq_start;
        }

        $title = $data['title'];
        $content = $data['content'] ?? '';
        $category = $data['category'] ?? 'User';

        if ($type === 'channel') {
            // Add to channels table
            $bw = $data['bandwidth'] ?? 0;
            $mod = $data['modulation'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO channels (frequency, name, description, category, bandwidth, modulation) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$freq_start, $title, $content, $category, $bw, $mod]);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId(), 'type' => 'channel']);
        } elseif ($type === 'allocation') {
            // Add to allocations table
            $stmt = $pdo->prepare("INSERT INTO spectrum_allocations (start_freq, end_freq, description, category) VALUES (?, ?, ?, ?)");
            $stmt->execute([$freq_start, $freq_end, $title . ' - ' . $content, $category]);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId(), 'type' => 'allocation']);
        } else {
            // Add to notes table
            $lat = isset($data['latitude']) && $data['latitude'] !== '' ? $data['latitude'] : null;
            $lon = isset($data['longitude']) && $data['longitude'] !== '' ? $data['longitude'] : null;
            $az = isset($data['azimuth']) && $data['azimuth'] !== '' ? $data['azimuth'] : null;

            $stmt = $pdo->prepare("INSERT INTO notes (frequency_start, frequency_end, title, content, latitude, longitude, azimuth) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$freq_start, $freq_end, $title, $content, $lat, $lon, $az]);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId(), 'type' => 'note']);
        }
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>