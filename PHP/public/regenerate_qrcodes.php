<?php
// Dateipfade anpassen
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

// QR-Code-Bibliotheken importieren
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\EccLevel;
//use Exception;

// Datenbankverbindung herstellen
$db = new Database();
$message = '';

try {
    // 1. Alle Geräte-IDs und Namen aus der Datenbank abrufen
    $db->query('SELECT id, name FROM devices');
    $devices = $db->resultset();

    if (empty($devices)) {
        $message = "Keine Geräte in der Datenbank gefunden.";
    } else {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => 0,
            'scale'      => 8,
            'quietzone'  => 4,
        ]);

        $regenerated_count = 0;

        // 2. Für jedes Gerät einen neuen QR-Code generieren
foreach ($devices as $device) {
    // Greife auf die Eigenschaften des Objekts mit -> zu
    $device_id = $device->id;
    $data_for_qr = 'https://philipp-lindner-server.de/homelab_manager/public/view_device.php?id=' . $device_id;
    $qr_file_name = 'device_' . $device_id . '.png';
    $qr_file_path = QR_CODE_DIR . $qr_file_name;

    // QR-Code erstellen und speichern
    (new QRCode($options))->render($data_for_qr, $qr_file_path);

    // 3. Den neuen Pfad in der Datenbank aktualisieren
    $db->query('UPDATE devices SET qr_code_path = :qr_code_path WHERE id = :id');
    $db->bind(':qr_code_path', QR_CODE_BASE_URL . $qr_file_name);
    $db->bind(':id', $device_id);
    $db->execute();
    
    $regenerated_count++;
}
        $message = "Erfolgreich $regenerated_count QR-Codes neu generiert und aktualisiert!";
    }

} catch (Exception $e) {
    $message = "Ein Fehler ist aufgetreten: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>QR-Codes neu generieren</title>
</head>
<body>
    <h1>QR-Codes neu generieren</h1>
    <p><?= htmlspecialchars($message); ?></p>
    <a href="index.php">Zurück zur Startseite</a>
</body>
</html>
