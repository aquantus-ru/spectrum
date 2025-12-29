<?php
// api.php
require 'database.php';

header('Content-Type: application/json');

$pdo = Database::connect();
$action = $_GET['action'] ?? '';

try {
    if ($action === 'search') {
        $query = $_GET['q'] ?? '';
        $sql = "SELECT 'channel' as type, id, frequency as val, name as title, description, category FROM channels
                WHERE name LIKE ? OR description LIKE ? OR category LIKE ?
                UNION
                SELECT 'allocation' as type, id, start_freq as val, description as title, 'Range: ' || start_freq || ' - ' || end_freq as description, category FROM spectrum_allocations
                WHERE description LIKE ? OR category LIKE ?
                UNION
                SELECT 'note' as type, id, frequency_start as val, title, content as description, 'User Note' as category FROM notes
                WHERE title LIKE ? OR content LIKE ?
                ORDER BY val ASC";

        $term = "%$query%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$term, $term, $term, $term, $term, $term, $term]);
        echo json_encode($stmt->fetchAll());

    } elseif ($action === 'allocations') {
        $min = $_GET['min'] ?? 0;
        $max = $_GET['max'] ?? 10000000000; // 10GHz default

        $stmt = $pdo->prepare("SELECT * FROM spectrum_allocations WHERE end_freq >= ? AND start_freq <= ? ORDER BY start_freq ASC");
        $stmt->execute([$min, $max]);
        echo json_encode($stmt->fetchAll());

    } elseif ($action === 'channels') {
        $min = $_GET['min'] ?? 0;
        $max = $_GET['max'] ?? 10000000000;

        $stmt = $pdo->prepare("SELECT * FROM channels WHERE frequency >= ? AND frequency <= ? ORDER BY frequency ASC");
        $stmt->execute([$min, $max]);
        echo json_encode($stmt->fetchAll());

    } elseif ($action === 'notes') {
        $min = $_GET['min'] ?? 0;
        $max = $_GET['max'] ?? 10000000000;

        $stmt = $pdo->prepare("SELECT * FROM notes WHERE frequency_start >= ? AND frequency_start <= ? ORDER BY frequency_start ASC");
        $stmt->execute([$min, $max]);
        echo json_encode($stmt->fetchAll());

    } elseif ($action === 'add_note' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $freq_start = $data['freq_start'];
        $freq_end = $data['freq_end'] ?? $freq_start;
        $title = $data['title'];
        $content = $data['content'];

        $stmt = $pdo->prepare("INSERT INTO notes (frequency_start, frequency_end, title, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$freq_start, $freq_end, $title, $content]);
        echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
