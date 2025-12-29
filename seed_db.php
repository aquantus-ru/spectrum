<?php
// seed_db.php
require 'database.php';

$pdo = Database::connect();

// Clear existing data to avoid duplicates on re-seed
$pdo->exec("DELETE FROM spectrum_allocations");
$pdo->exec("DELETE FROM channels");
$pdo->exec("DELETE FROM notes");

function toHz($val, $unit) {
    switch(strtolower(trim($unit))) {
        case 'hz': return $val;
        case 'khz': return $val * 1000;
        case 'mhz': return $val * 1000000;
        case 'ghz': return $val * 1000000000;
        default: return $val;
    }
}

// ---------------------------
// Allocations (Ranges)
// ---------------------------
$allocations = [
    // ELF / VLF
    ['s' => 1, 'u_s' => 'Hz', 'e' => 8.3, 'u_e' => 'kHz', 'd' => 'Not officially allocated (Natural/Submarine)', 'c' => 'Unallocated'],
    ['s' => 8.3, 'u_s' => 'kHz', 'e' => 9, 'u_e' => 'kHz', 'd' => 'Meteorological Aids (Lightning Detection)', 'c' => 'Meteorological'],
    ['s' => 9, 'u_s' => 'kHz', 'e' => 14, 'u_e' => 'kHz', 'd' => 'Radionavigation', 'c' => 'Radionavigation'],
    ['s' => 14, 'u_s' => 'kHz', 'e' => 19.95, 'u_e' => 'kHz', 'd' => 'Fixed and Maritime Mobile', 'c' => 'Mobile'],
    ['s' => 19.95, 'u_s' => 'kHz', 'e' => 20.05, 'u_e' => 'kHz', 'd' => 'Standard Frequency and Time Signal', 'c' => 'Time'],
    ['s' => 20.05, 'u_s' => 'kHz', 'e' => 59, 'u_e' => 'kHz', 'd' => 'Fixed and Maritime Mobile', 'c' => 'Mobile'],
    ['s' => 59, 'u_s' => 'kHz', 'e' => 61, 'u_e' => 'kHz', 'd' => 'Standard Frequency and Time Signal (WWVB)', 'c' => 'Time'],
    ['s' => 61, 'u_s' => 'kHz', 'e' => 70, 'u_e' => 'kHz', 'd' => 'Fixed and Maritime Mobile', 'c' => 'Mobile'],
    ['s' => 70, 'u_s' => 'kHz', 'e' => 90, 'u_e' => 'kHz', 'd' => 'Radionavigation (Decca/LORAN-C)', 'c' => 'Radionavigation'],
    ['s' => 90, 'u_s' => 'kHz', 'e' => 110, 'u_e' => 'kHz', 'd' => 'Radionavigation (Long-range)', 'c' => 'Radionavigation'],
    ['s' => 110, 'u_s' => 'kHz', 'e' => 130, 'u_e' => 'kHz', 'd' => 'Fixed and Maritime Mobile, Radionavigation', 'c' => 'Mobile'],
    ['s' => 130, 'u_s' => 'kHz', 'e' => 135.7, 'u_e' => 'kHz', 'd' => 'Fixed and Maritime Mobile', 'c' => 'Mobile'],
    ['s' => 135.7, 'u_s' => 'kHz', 'e' => 137.8, 'u_e' => 'kHz', 'd' => 'Amateur Radio (2200m)', 'c' => 'Amateur'],
    ['s' => 137.8, 'u_s' => 'kHz', 'e' => 160, 'u_e' => 'kHz', 'd' => 'Fixed and Maritime Mobile', 'c' => 'Mobile'],
    ['s' => 160, 'u_s' => 'kHz', 'e' => 190, 'u_e' => 'kHz', 'd' => 'Fixed (Lowfer)', 'c' => 'Fixed'],
    ['s' => 190, 'u_s' => 'kHz', 'e' => 275, 'u_e' => 'kHz', 'd' => 'Aeronautical Radionavigation (NDB)', 'c' => 'Aviation'],
    ['s' => 275, 'u_s' => 'kHz', 'e' => 285, 'u_e' => 'kHz', 'd' => 'Aeronautical/Maritime Radionavigation', 'c' => 'Navigation'],
    ['s' => 285, 'u_s' => 'kHz', 'e' => 300, 'u_e' => 'kHz', 'd' => 'Maritime Radionavigation', 'c' => 'Marine'],

    // MF
    ['s' => 300, 'u_s' => 'kHz', 'e' => 535, 'u_e' => 'kHz', 'd' => 'Maritime Mobile / Aero Nav (NDB)', 'c' => 'Marine/Aviation'],
    ['s' => 535, 'u_s' => 'kHz', 'e' => 1705, 'u_e' => 'kHz', 'd' => 'AM Radio Broadcasting', 'c' => 'Broadcast'],
    ['s' => 1.7, 'u_s' => 'MHz', 'e' => 3, 'u_e' => 'MHz', 'd' => 'Amateur (160m), Maritime, Fixed', 'c' => 'Amateur/Marine'],

    // HF
    ['s' => 3, 'u_s' => 'MHz', 'e' => 30, 'u_e' => 'MHz', 'd' => 'Shortwave (Amateur, Broadcast, Marine, CB)', 'c' => 'HF Mixed'],

    // VHF
    ['s' => 30, 'u_s' => 'MHz', 'e' => 54, 'u_e' => 'MHz', 'd' => 'Land Mobile, Amateur (6m)', 'c' => 'Mobile/Amateur'],
    ['s' => 54, 'u_s' => 'MHz', 'e' => 88, 'u_e' => 'MHz', 'd' => 'VHF TV Ch 2-6', 'c' => 'Broadcast'],
    ['s' => 88, 'u_s' => 'MHz', 'e' => 108, 'u_e' => 'MHz', 'd' => 'FM Radio Broadcasting', 'c' => 'Broadcast'],
    ['s' => 108, 'u_s' => 'MHz', 'e' => 137, 'u_e' => 'MHz', 'd' => 'Airband / ATC', 'c' => 'Aviation'],
    ['s' => 137, 'u_s' => 'MHz', 'e' => 174, 'u_e' => 'MHz', 'd' => 'Sat Wx, Amateur (2m), Land Mobile', 'c' => 'Mixed'],
    ['s' => 174, 'u_s' => 'MHz', 'e' => 216, 'u_e' => 'MHz', 'd' => 'VHF TV Ch 7-13', 'c' => 'Broadcast'],
    ['s' => 216, 'u_s' => 'MHz', 'e' => 300, 'u_e' => 'MHz', 'd' => 'Fixed, Mobile, Maritime', 'c' => 'Mixed'],

    // UHF
    ['s' => 300, 'u_s' => 'MHz', 'e' => 450, 'u_e' => 'MHz', 'd' => 'Military / Gov', 'c' => 'Government'],
    ['s' => 450, 'u_s' => 'MHz', 'e' => 470, 'u_e' => 'MHz', 'd' => 'Land Mobile (Biz, GMRS, FRS)', 'c' => 'Mobile'],
    ['s' => 470, 'u_s' => 'MHz', 'e' => 608, 'u_e' => 'MHz', 'd' => 'UHF TV Ch 14-36', 'c' => 'Broadcast'],
    ['s' => 608, 'u_s' => 'MHz', 'e' => 960, 'u_e' => 'MHz', 'd' => 'Mobile Broadband (600/700/800), ISM (900)', 'c' => 'Cellular/ISM'],
    ['s' => 960, 'u_s' => 'MHz', 'e' => 1.6, 'u_e' => 'GHz', 'd' => 'Aero Nav, GPS', 'c' => 'Aviation/Sat'],
    ['s' => 1.6, 'u_s' => 'GHz', 'e' => 2.3, 'u_e' => 'GHz', 'd' => 'Mobile Sat, PCS, AWS', 'c' => 'Cellular/Sat'],
    ['s' => 2.3, 'u_s' => 'GHz', 'e' => 2.4, 'u_e' => 'GHz', 'd' => 'WCS / Satellite Radio', 'c' => 'Broadcast'],
    ['s' => 2.4, 'u_s' => 'GHz', 'e' => 2.4835, 'u_e' => 'GHz', 'd' => 'ISM (WiFi, BT, Microwave)', 'c' => 'ISM'],
    ['s' => 2.5, 'u_s' => 'GHz', 'e' => 3, 'u_e' => 'GHz', 'd' => 'EBS / BRS', 'c' => 'Broadband'],

    // SHF
    ['s' => 3, 'u_s' => 'GHz', 'e' => 3.7, 'u_e' => 'GHz', 'd' => 'Radar, Sat, CBRS (3.5)', 'c' => 'Radar/Sat'],
    ['s' => 3.7, 'u_s' => 'GHz', 'e' => 4.2, 'u_e' => 'GHz', 'd' => 'C-Band Sat, 5G', 'c' => 'Sat/Cellular'],
    ['s' => 4.2, 'u_s' => 'GHz', 'e' => 5, 'u_e' => 'GHz', 'd' => 'Aero Nav (Altimeters), Fixed', 'c' => 'Aviation'],
    ['s' => 5, 'u_s' => 'GHz', 'e' => 5.9, 'u_e' => 'GHz', 'd' => 'WiFi 5GHz, ITS', 'c' => 'ISM/WiFi'],
    ['s' => 5.9, 'u_s' => 'GHz', 'e' => 7.1, 'u_e' => 'GHz', 'd' => 'WiFi 6GHz, Fixed Microwave', 'c' => 'WiFi/Fixed'],
    ['s' => 7.1, 'u_s' => 'GHz', 'e' => 8.5, 'u_e' => 'GHz', 'd' => 'Gov/Mil Sat (X-Band)', 'c' => 'Government'],
    ['s' => 8.5, 'u_s' => 'GHz', 'e' => 10, 'u_e' => 'GHz', 'd' => 'Precision Radar, Radio Astronomy', 'c' => 'Radar']
];

// ---------------------------
// Channels
// ---------------------------
$channels = [
    // FRS / GMRS
    ['f' => 462.5625, 'u' => 'MHz', 'n' => 'FRS/GMRS 1', 'd' => 'Talkabout/Baofeng Ch 01 (2W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 462.5875, 'u' => 'MHz', 'n' => 'FRS/GMRS 2', 'd' => 'Talkabout/Baofeng Ch 02 (2W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 462.6125, 'u' => 'MHz', 'n' => 'FRS/GMRS 3', 'd' => 'Talkabout/Baofeng Ch 03 (2W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 462.6375, 'u' => 'MHz', 'n' => 'FRS/GMRS 4', 'd' => 'Talkabout/Baofeng Ch 04 (2W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 462.6625, 'u' => 'MHz', 'n' => 'FRS/GMRS 5', 'd' => 'Talkabout/Baofeng Ch 05 (2W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 462.6875, 'u' => 'MHz', 'n' => 'FRS/GMRS 6', 'd' => 'Talkabout/Baofeng Ch 06 (2W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 462.7125, 'u' => 'MHz', 'n' => 'FRS/GMRS 7', 'd' => 'Talkabout/Baofeng Ch 07 (2W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 467.5625, 'u' => 'MHz', 'n' => 'FRS/GMRS 8', 'd' => 'Talkabout Ch 08 (0.5W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 467.5875, 'u' => 'MHz', 'n' => 'FRS/GMRS 9', 'd' => 'Talkabout Ch 09 (0.5W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 467.6125, 'u' => 'MHz', 'n' => 'FRS/GMRS 10', 'd' => 'Talkabout Ch 10 (0.5W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 467.6375, 'u' => 'MHz', 'n' => 'FRS/GMRS 11', 'd' => 'Talkabout Ch 11 (0.5W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 467.6625, 'u' => 'MHz', 'n' => 'FRS/GMRS 12', 'd' => 'Talkabout Ch 12 (0.5W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 467.6875, 'u' => 'MHz', 'n' => 'FRS/GMRS 13', 'd' => 'Talkabout Ch 13 (0.5W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 467.7125, 'u' => 'MHz', 'n' => 'FRS/GMRS 14', 'd' => 'Talkabout Ch 14 (0.5W)', 'c' => 'FRS/GMRS', 'b' => 12500],
    ['f' => 462.5500, 'u' => 'MHz', 'n' => 'FRS/GMRS 15', 'd' => 'Talkabout Ch 15 (50W)', 'c' => 'FRS/GMRS', 'b' => 25000],
    ['f' => 462.5750, 'u' => 'MHz', 'n' => 'FRS/GMRS 16', 'd' => 'Talkabout Ch 16 (50W)', 'c' => 'FRS/GMRS', 'b' => 25000],
    ['f' => 462.6000, 'u' => 'MHz', 'n' => 'FRS/GMRS 17', 'd' => 'Talkabout Ch 17 (50W)', 'c' => 'FRS/GMRS', 'b' => 25000],
    ['f' => 462.6250, 'u' => 'MHz', 'n' => 'FRS/GMRS 18', 'd' => 'Talkabout Ch 18 (50W)', 'c' => 'FRS/GMRS', 'b' => 25000],
    ['f' => 462.6500, 'u' => 'MHz', 'n' => 'FRS/GMRS 19', 'd' => 'Talkabout Ch 19 (50W)', 'c' => 'FRS/GMRS', 'b' => 25000],
    ['f' => 462.6750, 'u' => 'MHz', 'n' => 'FRS/GMRS 20', 'd' => 'Talkabout Ch 20 (50W)', 'c' => 'FRS/GMRS', 'b' => 25000],
    ['f' => 462.7000, 'u' => 'MHz', 'n' => 'FRS/GMRS 21', 'd' => 'Talkabout Ch 21 (50W)', 'c' => 'FRS/GMRS', 'b' => 25000],
    ['f' => 462.7250, 'u' => 'MHz', 'n' => 'FRS/GMRS 22', 'd' => 'Talkabout Ch 22 (50W)', 'c' => 'FRS/GMRS', 'b' => 25000],

    // MURS
    ['f' => 151.820, 'u' => 'MHz', 'n' => 'MURS 1 (Blue)', 'd' => 'VHF Business', 'c' => 'MURS', 'b' => 12500],
    ['f' => 151.880, 'u' => 'MHz', 'n' => 'MURS 2 (Blue)', 'd' => 'VHF Business', 'c' => 'MURS', 'b' => 12500],
    ['f' => 151.940, 'u' => 'MHz', 'n' => 'MURS 3 (Blue)', 'd' => 'VHF Business', 'c' => 'MURS', 'b' => 12500],
    ['f' => 154.570, 'u' => 'MHz', 'n' => 'MURS 4 (Blue)', 'd' => 'VHF Business (Common ProTalk)', 'c' => 'MURS', 'b' => 25000],
    ['f' => 154.600, 'u' => 'MHz', 'n' => 'MURS 5 (Green)', 'd' => 'VHF Business', 'c' => 'MURS', 'b' => 25000],

    // UHF Business Dot/Star
    ['f' => 464.5000, 'u' => 'MHz', 'n' => 'Brown Dot', 'd' => 'UHF Business (Common Default)', 'c' => 'Business', 'b' => 25000],
    ['f' => 464.5500, 'u' => 'MHz', 'n' => 'Yellow Dot', 'd' => 'UHF Business (Common Default)', 'c' => 'Business', 'b' => 25000],
    ['f' => 467.7625, 'u' => 'MHz', 'n' => 'J-Star', 'd' => 'UHF Business', 'c' => 'Business', 'b' => 12500],
    ['f' => 467.8500, 'u' => 'MHz', 'n' => 'Silver Star', 'd' => 'UHF Business', 'c' => 'Business', 'b' => 12500],
    ['f' => 467.9250, 'u' => 'MHz', 'n' => 'Gold Star', 'd' => 'UHF Business', 'c' => 'Business', 'b' => 12500],
    ['f' => 467.8125, 'u' => 'MHz', 'n' => 'K-Star', 'd' => 'UHF Business', 'c' => 'Business', 'b' => 12500],
    ['f' => 462.7500, 'u' => 'MHz', 'n' => 'Red Dot', 'd' => 'UHF Business', 'c' => 'Business', 'b' => 25000],
    ['f' => 462.8250, 'u' => 'MHz', 'n' => 'White Dot', 'd' => 'UHF Business', 'c' => 'Business', 'b' => 25000],
    ['f' => 462.9125, 'u' => 'MHz', 'n' => 'Blue Dot', 'd' => 'UHF Business', 'c' => 'Business', 'b' => 25000],

    // CB
    ['f' => 27.065, 'u' => 'MHz', 'n' => 'CB Ch 09', 'd' => 'Emergency Calling', 'c' => 'CB', 'b' => 10000],
    ['f' => 27.115, 'u' => 'MHz', 'n' => 'CB Ch 13', 'd' => 'Marine/RV', 'c' => 'CB', 'b' => 10000],
    ['f' => 27.165, 'u' => 'MHz', 'n' => 'CB Ch 17', 'd' => 'Truckers N/S', 'c' => 'CB', 'b' => 10000],
    ['f' => 27.185, 'u' => 'MHz', 'n' => 'CB Ch 19', 'd' => 'Highway/Truckers', 'c' => 'CB', 'b' => 10000],
    ['f' => 27.355, 'u' => 'MHz', 'n' => 'CB Ch 35', 'd' => 'SSB LSB Calling', 'c' => 'CB', 'b' => 10000],
    ['f' => 27.385, 'u' => 'MHz', 'n' => 'CB Ch 38', 'd' => 'SSB USB/LSB Calling', 'c' => 'CB', 'b' => 10000],

    // Amateur / Baofeng
    ['f' => 146.520, 'u' => 'MHz', 'n' => '2M Call', 'd' => 'National Simplex Calling', 'c' => 'Amateur', 'b' => 25000],
    ['f' => 446.000, 'u' => 'MHz', 'n' => '70CM Call', 'd' => 'National Simplex Calling', 'c' => 'Amateur', 'b' => 25000],

    // NOAA
    ['f' => 162.550, 'u' => 'MHz', 'n' => 'WX1', 'd' => 'NOAA Weather Radio', 'c' => 'Weather', 'b' => 25000],
    ['f' => 162.400, 'u' => 'MHz', 'n' => 'WX2', 'd' => 'NOAA Weather Radio', 'c' => 'Weather', 'b' => 25000],
    ['f' => 162.475, 'u' => 'MHz', 'n' => 'WX3', 'd' => 'NOAA Weather Radio', 'c' => 'Weather', 'b' => 25000],
    ['f' => 162.425, 'u' => 'MHz', 'n' => 'WX4', 'd' => 'NOAA Weather Radio', 'c' => 'Weather', 'b' => 25000],
    ['f' => 162.450, 'u' => 'MHz', 'n' => 'WX5', 'd' => 'NOAA Weather Radio', 'c' => 'Weather', 'b' => 25000],
    ['f' => 162.500, 'u' => 'MHz', 'n' => 'WX6', 'd' => 'NOAA Weather Radio', 'c' => 'Weather', 'b' => 25000],
    ['f' => 162.525, 'u' => 'MHz', 'n' => 'WX7', 'd' => 'NOAA Weather Radio', 'c' => 'Weather', 'b' => 25000],

    // ISM / Misc
    ['f' => 915, 'u' => 'MHz', 'n' => 'ISM 915', 'd' => 'LoRa/Sensors Center', 'c' => 'ISM', 'b' => 26000000],
    ['f' => 2437, 'u' => 'MHz', 'n' => 'WiFi Ch 6', 'd' => '2.4GHz WiFi Center', 'c' => 'WiFi', 'b' => 22000000],
    ['f' => 5800, 'u' => 'MHz', 'n' => 'ISM 5.8', 'd' => '5.8 GHz ISM Center', 'c' => 'ISM', 'b' => 150000000],

    // Time
    ['f' => 60, 'u' => 'kHz', 'n' => 'WWVB', 'd' => 'Time Signal (Ft. Collins, CO)', 'c' => 'Time', 'b' => 0]
];

echo "Seeding spectrum_allocations...\n";
$stmt = $pdo->prepare("INSERT INTO spectrum_allocations (start_freq, end_freq, description, category) VALUES (?, ?, ?, ?)");
foreach ($allocations as $a) {
    $start = toHz($a['s'], $a['u_s']);
    $end = toHz($a['e'], $a['u_e']);
    $stmt->execute([$start, $end, $a['d'], $a['c']]);
}

echo "Seeding channels...\n";
$stmt = $pdo->prepare("INSERT INTO channels (frequency, name, description, category, bandwidth) VALUES (?, ?, ?, ?, ?)");
foreach ($channels as $c) {
    $freq = toHz($c['f'], $c['u']);
    $stmt->execute([$freq, $c['n'], $c['d'], $c['c'], $c['b']]);
}

echo "Database seeded successfully.\n";
?>
