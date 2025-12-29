# RGB Spectrum Manager

A spectrum management application using PHP and SQLite, featuring an "RGB Beam Splitter" design. It allows visualization and management of frequency allocations, channels, and notes from 1Hz to 10GHz.

## Features

- **RGB Beam Splitter Design**: Dark mode with neon RGB accents.
- **Spectrum Visualization**: Interactive Logarithmic Chart (Chart.js) showing Allocations (Blue), Channels (Green), and Notes (Red).
- **Frequency Management**:
  - View preloaded FCC Allocations (1Hz - 10GHz).
  - Preloaded Channels (FRS, GMRS, MURS, Business, CB, Amateur, NOAA, WiFi, etc.).
  - Add custom Notes, Channels, and Allocations.
- **Search & Filter**: Search by frequency, title, or description.
- **Details View**: Click on chart items to see detailed information (Bandwidth, Modulation, Location, etc.).

## Setup

1. **Requirements**: PHP with PDO_SQLite extension enabled.
2. **Initialize Database**:
   ```bash
   php init_db.php
   php seed_db.php
   ```
3. **Run Server**:
   ```bash
   php -S localhost:8000
   ```
4. **Access**: Open `http://localhost:8000` in your browser.

## File Structure

- `index.php`: Main frontend.
- `api.php`: Backend API handling requests.
- `database.php`: Database connection helper.
- `init_db.php`: Database schema setup.
- `seed_db.php`: Data seeder (FCC defaults, Channels).
- `script.js`: Frontend logic (Chart.js, Search, Forms).
- `style.css`: Custom styling.
