<?php
// Stelle sicher, dass die Datenbank-Konfiguration und -Klasse importiert werden
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';

// Ein einfacher Sicherheitsschlüssel
$secret_key = "homeLab2015_123456789";

// Überprüfe den Sicherheitsschlüssel und die Methode
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['key']) || $_POST['key'] !== $secret_key) {
    http_response_code(403);
    die("Zugriff verweigert. (Send Key: ".$_POST['key']." Korek: ".$secret_key.")");
}

if (!isset($_POST['barcode']) || !isset($_POST['hardware_info'])) {
    http_response_code(400);
    die("Erforderliche Daten fehlen.");
}

$barcode_number = $_POST['barcode'];
$hardware_info = $_POST['hardware_info'];

$db = new Database();

try {
    // Finde das Gerät anhand der Barcode-Nummer
    $db->query("SELECT * FROM devices WHERE barcode_number = :barcode_number");
    $db->bind(':barcode_number', $barcode_number);
    $device = $db->single();

    if ($device) {
        // Aktualisiere die Spezifikationen des Geräts
        $current_specs = $device->specifications ?? '';
        $new_specs = $current_specs . "\n\n--- Hardware-Bericht (" . date("Y-m-d H:i:s") . ") ---\n" . $hardware_info;

        $db->query("UPDATE devices SET specifications = :specs WHERE id = :id");
        $db->bind(':specs', $new_specs);
        $db->bind(':id', $device->id);
        $db->execute();

        echo "Hardware-Informationen erfolgreich aktualisiert.";
    } else {
        http_response_code(404);
        echo "Gerät mit Barcode " . htmlspecialchars($barcode_number) . " nicht gefunden.";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Ein Fehler ist aufgetreten: " . $e->getMessage();
}
?>
