<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Für die QR-Code-Bibliothek

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\EccLevel;


include 'includes/header.php';


$db = new Database();
$message = '';
$editing_item = null;

// Funktion zum Generieren einer 10-stelligen Zufalls-ID
function generateRandomBarcodeId() {
    $id = '';
    for ($i = 0; $i < 10; $i++) {
        $id .= mt_rand(0, 9);
    }
    return $id;
}

// Barcode-ID für das Formular generieren
$new_barcode_id = generateRandomBarcodeId();

// Hole Rack-Daten für das Dropdown-Menü
$db->query('SELECT id, name FROM racks ORDER BY name ASC');
$racks = $db->resultset();

// Handle POST-Anfragen (Hinzufügen oder Bearbeiten)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $num_ports = $_POST['num_ports'];
    $notes = $_POST['notes'];
    $rack_id = $_POST['rack_id'];
    $rack_unit_start = $_POST['rack_unit_start'];
    $rack_unit_end = $_POST['rack_unit_end'];
    $barcode_number = $_POST['barcode_number'];

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Bearbeiten eines bestehenden Patch-Panels
        $db->query('UPDATE patch_panels SET name = :name, location = :location, num_ports = :num_ports, notes = :notes, rack_id = :rack_id, rack_unit_start = :rack_unit_start, rack_unit_end = :rack_unit_end, barcode_number = :barcode_number WHERE id = :id');
        $db->bind(':name', $name);
        $db->bind(':location', $location);
        $db->bind(':num_ports', $num_ports);
        $db->bind(':notes', $notes);
        $db->bind(':rack_id', $rack_id);
        $db->bind(':rack_unit_start', $rack_unit_start);
        $db->bind(':rack_unit_end', $rack_unit_end);
        $db->bind(':barcode_number', $barcode_number);
        $db->bind(':id', $_POST['id']);
        if ($db->execute()) {
            $message = '<p style="color:green;">Patch-Panel erfolgreich aktualisiert!</p>';
        } else {
            $message = '<p style="color:red;">Fehler beim Aktualisieren des Patch-Panels.</p>';
        }
    } else {
        // Hinzufügen eines neuen Patch-Panels
        $db->query('INSERT INTO patch_panels (name, location, num_ports, notes, rack_id, rack_unit_start, rack_unit_end, barcode_number) VALUES (:name, :location, :num_ports, :notes, :rack_id, :rack_unit_start, :rack_unit_end, :barcode_number)');
        $db->bind(':name', $name);
        $db->bind(':location', $location);
        $db->bind(':num_ports', $num_ports);
        $db->bind(':notes', $notes);
        $db->bind(':rack_id', $rack_id);
        $db->bind(':rack_unit_start', $rack_unit_start);
        $db->bind(':rack_unit_end', $rack_unit_end);
        $db->bind(':barcode_number', $barcode_number);
        if ($db->execute()) {
            $message = '<p style="color:green;">Patch-Panel erfolgreich hinzugefügt!</p>';
        } else {
            $message = '<p style="color:red;">Fehler beim Hinzufügen des Patch-Panels.</p>';
        }
    }
}

// Handle GET-Anfragen (Löschen oder Bearbeiten-Formular anzeigen)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['delete']) && !empty($_GET['delete'])) {
        // Löschen eines Patch-Panels
        $db->query('DELETE FROM patch_panels WHERE id = :id');
        $db->bind(':id', $_GET['delete']);
        if ($db->execute()) {
            $message = '<p style="color:green;">Patch-Panel erfolgreich gelöscht!</p>';
        } else {
            $message = '<p style="color:red;">Fehler beim Löschen des Patch-Panels.</p>';
        }
    }

    if (isset($_GET['edit']) && !empty($_GET['edit'])) {
        // Daten für die Bearbeitung abrufen
        $db->query('SELECT * FROM patch_panels WHERE id = :id');
        $db->bind(':id', $_GET['edit']);
        $editing_item = $db->single();
    }
}

// Alle Patch-Panels abrufen zur Anzeige
$db->query('SELECT * FROM patch_panels ORDER BY name ASC');
$patch_panels = $db->resultset();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patch-Panel-Verwaltung</title>
    <style>
        body { font-family: sans-serif; }
        .container { max-width: 800px; margin: 20px auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .actions a { margin-right: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Patch-Panel-Verwaltung</h1>
    <?= $message ?>

    <h2><?= ($editing_item) ? 'Patch-Panel bearbeiten' : 'Neues Patch-Panel hinzufügen' ?></h2>
    <form method="post" action="manage_patch_panels.php">
        <input type="hidden" name="id" value="<?= htmlspecialchars($editing_item->id ?? '') ?>">
        
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($editing_item->name ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="location">Standort:</label>
            <input type="text" id="location" name="location" value="<?= htmlspecialchars($editing_item->location ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="num_ports">Anzahl der Ports:</label>
            <input type="number" id="num_ports" name="num_ports" value="<?= htmlspecialchars($editing_item->num_ports ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="notes">Notizen:</label>
            <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($editing_item->notes ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="rack_id">Rack:</label>
            <select id="rack_id" name="rack_id">
                <option value="">-- Keinem Rack zuweisen --</option>
                <?php foreach ($racks as $rack): ?>
                    <option value="<?= htmlspecialchars($rack->id) ?>" <?= (isset($editing_item->rack_id) && $editing_item->rack_id == $rack->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rack->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="rack_unit_start">Rack Unit (Anfang):</label>
            <input type="number" id="rack_unit_start" name="rack_unit_start" value="<?= htmlspecialchars($editing_item->rack_unit_start ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="rack_unit_end">Rack Unit (Ende):</label>
            <input type="number" id="rack_unit_end" name="rack_unit_end" value="<?= htmlspecialchars($editing_item->rack_unit_end ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="barcode_number">Barcode-Nummer:</label>
            <input type="text" id="barcode_number" name="barcode_number" value="<?= htmlspecialchars($editing_item->barcode_number ?? $new_barcode_id) ?>" readonly>
        </div>

        <button type="submit"><?= ($editing_item) ? 'Aktualisieren' : 'Hinzufügen' ?></button>
        <?php if ($editing_item): ?>
            <a href="manage_patch_panels.php">Abbrechen</a>
        <?php endif; ?>
    </form>

    <h2>Alle Patch-Panels</h2>
    <?php if ($patch_panels): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Ports</th>
                    <th>Rack</th>
                    <th>Barcode</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patch_panels as $panel): ?>
                    <tr>
                        <td><?= htmlspecialchars($panel->id) ?></td>
                        <td><?= htmlspecialchars($panel->name) ?></td>
                        <td><?= htmlspecialchars($panel->num_ports) ?></td>
                        <td>
                            <?php 
                                // Rack-Namen anzeigen, falls zugewiesen
                                if (!empty($panel->rack_id)) {
                                    $rack_name = '';
                                    foreach ($racks as $rack) {
                                        if ($rack->id == $panel->rack_id) {
                                            $rack_name = $rack->name;
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($rack_name);
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($panel->barcode_number) ?></td>
                        <td class="actions">
                            <a href="manage_patch_panels.php?edit=<?= htmlspecialchars($panel->id) ?>">Bearbeiten</a>
                            <a href="manage_patch_panels.php?delete=<?= htmlspecialchars($panel->id) ?>" onclick="return confirm('Sicher, dass Sie dieses Patch-Panel löschen möchten?');">Löschen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Keine Patch-Panels gefunden.</p>
    <?php endif; ?>
</div>

</body>
</html>
