<?php
// seed_db.php
require 'database.php';

$pdo = Database::connect();

function toHz($val, $unit) {
    switch(strtolower($unit)) {
        case 'hz': return $val;
        case 'khz': return $val * 1000;
        case 'mhz': return $val * 1000000;
        case 'ghz': return $val * 1000000000;
        default: return $val;
    }
}

$allocations = [
    // Extremely Low Frequency to High Frequency (simplified)
    ['start' => 3, 'unit_s' => 'kHz', 'end' => 30, 'unit_e' => 'kHz', 'desc' => 'Very Low Frequency (VLF)', 'cat' => 'FCC'],
    ['start' => 30, 'unit_s' => 'kHz', 'end' => 300, 'unit_e' => 'kHz', 'desc' => 'Low Frequency (LF)', 'cat' => 'FCC'],
    ['start' => 300, 'unit_s' => 'kHz', 'end' => 3000, 'unit_e' => 'kHz', 'desc' => 'Medium Frequency (MF) - AM Broadcast', 'cat' => 'FCC'],
    ['start' => 3, 'unit_s' => 'MHz', 'end' => 30, 'unit_e' => 'MHz', 'desc' => 'High Frequency (HF) - Shortwave', 'cat' => 'FCC'],

    // VHF
    ['start' => 30, 'unit_s' => 'MHz', 'end' => 50, 'unit_e' => 'MHz', 'desc' => 'VHF Low Band', 'cat' => 'FCC'],
    ['start' => 50, 'unit_s' => 'MHz', 'end' => 54, 'unit_e' => 'MHz', 'desc' => '6 Meter Amateur Radio', 'cat' => 'Amateur'],
    ['start' => 88, 'unit_s' => 'MHz', 'end' => 108, 'unit_e' => 'MHz', 'desc' => 'FM Broadcast', 'cat' => 'Broadcast'],
    ['start' => 108, 'unit_s' => 'MHz', 'end' => 137, 'unit_e' => 'MHz', 'desc' => 'Airband', 'cat' => 'Aviation'],
    ['start' => 144, 'unit_s' => 'MHz', 'end' => 148, 'unit_e' => 'MHz', 'desc' => '2 Meter Amateur Radio', 'cat' => 'Amateur'],

    // UHF
    ['start' => 300, 'unit_s' => 'MHz', 'end' => 3000, 'unit_e' => 'MHz', 'desc' => 'Ultra High Frequency (UHF)', 'cat' => 'FCC'],
    ['start' => 420, 'unit_s' => 'MHz', 'end' => 450, 'unit_e' => 'MHz', 'desc' => '70 Centimeter Amateur Radio', 'cat' => 'Amateur'],
    ['start' => 902, 'unit_s' => 'MHz', 'end' => 928, 'unit_e' => 'MHz', 'desc' => 'ISM Band (900 MHz)', 'cat' => 'ISM'],

    // SHF
    ['start' => 3, 'unit_s' => 'GHz', 'end' => 30, 'unit_e' => 'GHz', 'desc' => 'Super High Frequency (SHF)', 'cat' => 'FCC'],
    ['start' => 2.4, 'unit_s' => 'GHz', 'end' => 2.5, 'unit_e' => 'GHz', 'desc' => 'ISM Band (2.4 GHz) - WiFi/Bluetooth', 'cat' => 'ISM'],
    ['start' => 5.15, 'unit_s' => 'GHz', 'end' => 5.85, 'unit_e' => 'GHz', 'desc' => 'U-NII / ISM (5 GHz) - WiFi', 'cat' => 'ISM'],
];

$channels = [
    // FRS
    ['freq' => 462.5625, 'unit' => 'MHz', 'name' => 'FRS/GMRS 1', 'desc' => 'Family Radio Service Channel 1', 'cat' => 'FRS'],
    ['freq' => 462.5875, 'unit' => 'MHz', 'name' => 'FRS/GMRS 2', 'desc' => 'Family Radio Service Channel 2', 'cat' => 'FRS'],
    ['freq' => 462.6125, 'unit' => 'MHz', 'name' => 'FRS/GMRS 3', 'desc' => 'Family Radio Service Channel 3', 'cat' => 'FRS'],
    ['freq' => 462.6375, 'unit' => 'MHz', 'name' => 'FRS/GMRS 4', 'desc' => 'Family Radio Service Channel 4', 'cat' => 'FRS'],
    ['freq' => 462.6625, 'unit' => 'MHz', 'name' => 'FRS/GMRS 5', 'desc' => 'Family Radio Service Channel 5', 'cat' => 'FRS'],
    ['freq' => 462.6875, 'unit' => 'MHz', 'name' => 'FRS/GMRS 6', 'desc' => 'Family Radio Service Channel 6', 'cat' => 'FRS'],
    ['freq' => 462.7125, 'unit' => 'MHz', 'name' => 'FRS/GMRS 7', 'desc' => 'Family Radio Service Channel 7', 'cat' => 'FRS'],

    // WiFi 2.4GHz
    ['freq' => 2412, 'unit' => 'MHz', 'name' => 'WiFi Ch 1', 'desc' => '2.4GHz WiFi Channel 1', 'cat' => 'WiFi'],
    ['freq' => 2437, 'unit' => 'MHz', 'name' => 'WiFi Ch 6', 'desc' => '2.4GHz WiFi Channel 6', 'cat' => 'WiFi'],
    ['freq' => 2462, 'unit' => 'MHz', 'name' => 'WiFi Ch 11', 'desc' => '2.4GHz WiFi Channel 11', 'cat' => 'WiFi'],

    // Business / Public Safety (Example)
    ['freq' => 151.625, 'unit' => 'MHz', 'name' => 'Red Dot', 'desc' => 'Itinerant Business Band', 'cat' => 'Business'],
    ['freq' => 154.570, 'unit' => 'MHz', 'name' => 'MURS 4 / Blue Dot', 'desc' => 'Multi-Use Radio Service', 'cat' => 'Business'],
    ['freq' => 154.600, 'unit' => 'MHz', 'name' => 'MURS 5 / Green Dot', 'desc' => 'Multi-Use Radio Service', 'cat' => 'Business'],

    // Cellular (Example LTE Bands centers)
    ['freq' => 700, 'unit' => 'MHz', 'name' => 'LTE Band 12/13/17/71', 'desc' => '700 MHz Block', 'cat' => 'Cellular'],
    ['freq' => 850, 'unit' => 'MHz', 'name' => 'GSM/LTE 850', 'desc' => 'Cellular 850', 'cat' => 'Cellular'],
    ['freq' => 1900, 'unit' => 'MHz', 'name' => 'PCS 1900', 'desc' => 'PCS Band', 'cat' => 'Cellular'],
];

echo "Seeding spectrum_allocations...\n";
$stmt = $pdo->prepare("INSERT INTO spectrum_allocations (start_freq, end_freq, description, category) VALUES (?, ?, ?, ?)");
foreach ($allocations as $a) {
    $start = toHz($a['start'], $a['unit_s']);
    $end = toHz($a['end'], $a['unit_e']);
    $stmt->execute([$start, $end, $a['desc'], $a['cat']]);
}

echo "Seeding channels...\n";
$stmt = $pdo->prepare("INSERT INTO channels (frequency, name, description, category, bandwidth) VALUES (?, ?, ?, ?, ?)");
foreach ($channels as $c) {
    $freq = toHz($c['freq'], $c['unit']);
    // Assume some default bandwidths if not specified
    $bw = 0;
    if ($c['cat'] == 'WiFi') $bw = toHz(20, 'MHz');
    elseif ($c['cat'] == 'FRS') $bw = toHz(12.5, 'kHz');
    else $bw = toHz(25, 'kHz');

    $stmt->execute([$freq, $c['name'], $c['desc'], $c['cat'], $bw]);
}

echo "Database seeded successfully.\n";
?>
