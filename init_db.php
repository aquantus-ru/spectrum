<?php
// init_db.php
require 'database.php';

echo "Initializing database...\n";

$pdo = Database::connect();

$commands = [
    "CREATE TABLE IF NOT EXISTS spectrum_allocations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        start_freq REAL NOT NULL,
        end_freq REAL NOT NULL,
        description TEXT,
        category TEXT
    )",
    "CREATE TABLE IF NOT EXISTS channels (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        frequency REAL NOT NULL,
        name TEXT,
        description TEXT,
        bandwidth REAL,
        modulation TEXT,
        category TEXT
    )",
    "CREATE TABLE IF NOT EXISTS notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        frequency_start REAL,
        frequency_end REAL,
        title TEXT,
        content TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($commands as $command) {
    try {
        $pdo->exec($command);
        echo "Executed command successfully.\n";
    } catch (PDOException $e) {
        die("Error executing command: " . $e->getMessage());
    }
}

echo "Database initialized successfully.\n";
?>
