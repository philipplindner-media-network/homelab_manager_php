<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/Database.php';

$db = new Database();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage_devices.php');
    exit;
}

$id = $_GET['id'];

// Gerät aus der Datenbank abrufen
$db->query('SELECT * FROM devices WHERE id = :id');
$db->bind(':id', $id);
$device = $db->single();

if (!$device) {
    echo "Gerät nicht gefunden.";
    exit;
}

// Hardware-Daten von den normalen Spezifikationen trennen und parsen
$hardware_info = '';
$normal_specs = $device->specifications ?? '';

// Prüfe, ob Hardware-Bericht vorhanden ist
$separator = "--- Hardware-Bericht (";
$separator_pos = strpos($normal_specs, $separator);

if ($separator_pos !== false) {
    $hardware_info = trim(substr($normal_specs, $separator_pos));
    $normal_specs = substr($normal_specs, 0, $separator_pos);
    
    // Die einzelnen Abschnitte des Hardware-Berichts aufteilen
    $sections = preg_split('/^---\s(.+?)\s---/m', $hardware_info, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
    $parsed_hardware_info = [];
    for ($i = 0; $i < count($sections); $i += 2) {
        $key = trim($sections[$i]);
        $value = trim($sections[$i + 1]);
        $parsed_hardware_info[$key] = $value;
    }
} else {
    $parsed_hardware_info = null;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Gerät: <?= htmlspecialchars($device->name) ?></title>
    <style>
        body { font-family: sans-serif; }
        .container { max-width: 900px; margin: 20px auto; }
        .device-details { border: 1px solid #ccc; padding: 20px; border-radius: 8px; }
        .detail-item { margin-bottom: 10px; }
        .detail-item strong { display: inline-block; width: 150px; }
        .hardware-info-block {
            background: #f4f4f4;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .hardware-info-block h3 { margin-top: 0; }
        .hardware-info-block pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 14px;
        }
        .hardware-summary ul {
            list-style: none;
            padding: 0;
            margin: 0 0 15px 0;
            display: flex;
            flex-wrap: wrap;
        }
        .hardware-summary li {
            width: 50%;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="manage_devices.php">Zurück zur Übersicht</a>
    <h1>Gerät: <?= htmlspecialchars($device->name) ?></h1>

    <div class="device-details">
        <div class="detail-item"><strong>ID:</strong> <?= htmlspecialchars($device->id) ?></div>
        <div class="detail-item"><strong>Barcode:</strong> <?= htmlspecialchars($device->barcode_number) ?></div>
        <div class="detail-item"><strong>Typ:</strong> <?= htmlspecialchars($device->type) ?></div>
        <div class="detail-item"><strong>IP-Adresse:</strong> <?= htmlspecialchars($device->ip_address) ?></div>
        <div class="detail-item"><strong>MAC-Adresse:</strong> <?= htmlspecialchars($device->mac_address) ?></div>
        <div class="detail-item"><strong>Standort:</strong> <?= htmlspecialchars($device->location) ?></div>
        <div class="detail-item"><strong>Kaufdatum:</strong> <?= htmlspecialchars($device->purchase_date) ?></div>
        <div class="detail-item"><strong>Garantieende:</strong> <?= htmlspecialchars($device->warranty_expires) ?></div>
        <div class="detail-item">
            <strong>Spezifikationen:</strong>
            <p><?= nl2br(htmlspecialchars(trim($normal_specs))) ?></p>
        </div>
        <div class="detail-item">
            <strong>Notizen:</strong>
            <p><?= nl2br(htmlspecialchars($device->notes)) ?></p>
        </div>
    </div>
    
    <?php if (!empty($parsed_hardware_info)): ?>
    <div class="hardware-info-block">
        <h3>Automatischer Hardware-Bericht</h3>
        
        <?php if (isset($parsed_hardware_info['System-Bericht'])): ?>
        <h4>System-Zusammenfassung</h4>
        <div class="hardware-summary">
            <ul>
            <?php 
                $system_lines = explode("\n", trim($parsed_hardware_info['System-Bericht']));
                foreach ($system_lines as $line) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        echo '<li><strong>' . trim($parts[0]) . ':</strong> ' . trim($parts[1]) . '</li>';
                    }
                }
            ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (isset($parsed_hardware_info['PCI-Geräte'])): ?>
        <h4>PCI-Geräte</h4>
        <pre><?= htmlspecialchars(trim($parsed_hardware_info['PCI-Geräte'])) ?></pre>
        <?php endif; ?>
        
        <?php if (isset($parsed_hardware_info['USB-Geräte'])): ?>
        <h4>USB-Geräte</h4>
        <pre><?= htmlspecialchars(trim($parsed_hardware_info['USB-Geräte'])) ?></pre>
        <?php endif; ?>
        
        <?php if (isset($parsed_hardware_info['Netzwerk-Schnittstellen'])): ?>
        <h4>Netzwerk-Schnittstellen</h4>
        <pre><?= htmlspecialchars(trim($parsed_hardware_info['Netzwerk-Schnittstellen'])) ?></pre>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($device->qr_code_path)): ?>
        <div class="detail-item">
            <strong>QR-Code:</strong>
            <p><img src="<?= htmlspecialchars($device->qr_code_path) ?>" alt="QR Code"></p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
