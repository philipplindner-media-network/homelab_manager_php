<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// Sicherstellen, dass eine ID übergeben wurde
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Fehler: Keine Geräte-ID angegeben.");
}

$device_id = $_GET['id'];

try {
    // 1. Geräteinformationen aus der Datenbank abrufen
    $db->query('SELECT * FROM devices WHERE id = :id');
    $db->bind(':id', $device_id);
    $device = $db->single();

    if (!$device) {
        die("Fehler: Gerät mit ID " . htmlspecialchars($device_id) . " nicht gefunden.");
    }
    
    // 2. Die Informationen in einen lesbaren Textstring formatieren
    // Verwende \n für Zeilenumbrüche, um die Lesbarkeit im QR-Code-Scanner zu verbessern
    $data_for_qr = "Geräte-ID: " . $device->id . "\n";
    $data_for_qr .= "Name: " . $device->name . "\n";
    $data_for_qr .= "Typ: " . $device->type . "\n";
    $data_for_qr .= "IP: " . $device->ip_address . "\n";
    $data_for_qr .= "MAC: " . $device->mac_address . "\n";
    $data_for_qr .= "Standort: " . $device->location . "\n";
    $data_for_qr .= "Spezifikationen: " . $device->specifications . "\n";
    $data_for_qr .= "Notizen: " . $device->notes . "\n";
    $data_for_qr .= "Management-URL: https://philipp-lindner-server.de/homelab_manager/public/view_device.php?id=" . $device->id;

    // 3. QR-Code-Optionen festlegen und rendern
    $options = new QROptions([
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => 0,
        'scale'      => 8,
        'quietzone'  => 4,
    ]);

    // Den QR-Code direkt in den Browser ausgeben
    (new QRCode($options))->render($data_for_qr);

} catch (Exception $e) {
    die("Ein Fehler ist aufgetreten: " . $e->getMessage());
}
