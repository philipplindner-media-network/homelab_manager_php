<?php
// Dateipfade anpassen
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';

// Datenbankverbindung herstellen
$db = new Database();
$message = '';
$tables_to_update = ['devices', 'cables', 'racks', 'patch_panels'];
$results = [];

// Funktion zum Generieren einer 10-stelligen Zufalls-ID
function generateRandomBarcodeId() {
    $id = '';
    for ($i = 0; $i < 10; $i++) {
        $id .= mt_rand(0, 9);
    }
    return $id;
}

try {
    foreach ($tables_to_update as $table) {
        $updated_count = 0;

        // 1. Einträge ohne Barcode-Nummer finden
        // Hier wird geprüft, ob die Spalte 'barcode_number' NULL oder ein leerer String ist
        $db->query("SELECT id FROM $table WHERE barcode_number IS NULL OR barcode_number = ''");
        $items = $db->resultset();

        if (empty($items)) {
            $results[$table] = "Keine fehlenden Barcode-Nummern gefunden.";
        } else {
            foreach ($items as $item) {
                // Generiere eine neue, zufällige ID
                $new_barcode_id = generateRandomBarcodeId();

                // 2. Den Eintrag mit der neuen ID aktualisieren
                $db->query("UPDATE $table SET barcode_number = :barcode_id WHERE id = :item_id");
                $db->bind(':barcode_id', $new_barcode_id);
                $db->bind(':item_id', $item->id);
                $db->execute();
                
                $updated_count++;
            }
            $results[$table] = "Erfolgreich $updated_count Barcode-Nummern hinzugefügt.";
        }
    }
    $message = "Vorgang abgeschlossen.";

} catch (Exception $e) {
    $message = "Ein schwerwiegender Fehler ist aufgetreten: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Barcodes generieren</title>
    <style>
        body { font-family: sans-serif; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Fehlende Barcode-Nummern generieren</h1>
    <p><?= htmlspecialchars($message); ?></p>
    
    <h2>Ergebnis pro Tabelle:</h2>
    <ul>
        <?php foreach ($results as $table_name => $result_message): ?>
            <li><strong><?= htmlspecialchars($table_name) ?>:</strong> <?= htmlspecialchars($result_message) ?></li>
        <?php endforeach; ?>
    </ul>
    
    <a href="index.php">Zurück zur Startseite</a>
</body>
</html>
