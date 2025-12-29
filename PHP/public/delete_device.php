<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';

$db = new Database();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Zuerst den Pfad des QR-Codes abrufen, um das Bild zu löschen
    $db->query('SELECT qr_code_path FROM devices WHERE id = :id');
    $db->bind(':id', $id);
    $device = $db->single();

    if ($device && $device->qr_code_path) {
        $qr_file_name = basename($device->qr_code_path);
        $qr_file_path = QR_CODE_DIR . $qr_file_name;

        if (file_exists($qr_file_path)) {
            unlink($qr_file_path); // QR-Code-Bild löschen
        }
    }

    // Gerät aus der Datenbank löschen
    $db->query('DELETE FROM devices WHERE id = :id');
    $db->bind(':id', $id);

    if ($db->execute()) {
        header('Location: index.php?deleted=success');
        exit();
    } else {
        header('Location: index.php?deleted=fail');
        exit();
    }
} else {
    header('Location: index.php'); // Keine ID angegeben, zurück zur Übersicht
    exit();
}
?>
