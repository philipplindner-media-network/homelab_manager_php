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

// Funktion zum Generieren einer 10-stelligen Zufalls-ID
function generateRandomBarcodeId() {
    $id = '';
    for ($i = 0; $i < 10; $i++) {
        $id .= mt_rand(0, 9);
    }
    return $id;
}

// Generiere eine neue Barcode-ID für das Formular
$new_barcode_id = generateRandomBarcodeId();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $ip_address = $_POST['ip_address'];
    $mac_address = $_POST['mac_address'];
    $location = $_POST['location'];
    $purchase_date = $_POST['purchase_date'];
    $warranty_expires = $_POST['warranty_expires'];
    $specifications = $_POST['specifications'];
    $notes = $_POST['notes'];
    $barcode_number = $_POST['barcode_number']; // Die generierte ID aus dem Formular abrufen

    // SQL-Query zum Einfügen der Daten
    $db->query('INSERT INTO devices (name, type, ip_address, mac_address, location, purchase_date, warranty_expires, specifications, notes, barcode_number) VALUES (:name, :type, :ip_address, :mac_address, :location, :purchase_date, :warranty_expires, :specifications, :notes, :barcode_number)');

    // Parameter binden
    $db->bind(':name', $name);
    $db->bind(':type', $type);
    $db->bind(':ip_address', $ip_address);
    $db->bind(':mac_address', $mac_address);
    $db->bind(':location', $location);
    $db->bind(':purchase_date', $purchase_date);
    $db->bind(':warranty_expires', $warranty_expires);
    $db->bind(':specifications', $specifications);
    $db->bind(':notes', $notes);
    $db->bind(':barcode_number', $barcode_number);

    if ($db->execute()) {
        $device_id = $db->lastInsertId();

        // QR-Code generieren
        $data_for_qr = QR_CODE_BASE_URL . 'view_device.php?id=' . $device_id;
        $qr_file_name = 'device_' . $device_id . '.png';
        $qr_file_path = QR_CODE_DIR . $qr_file_name;

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => 0,
            'scale'      => 8,
            'quietzone'  => 4,
        ]);

        try {
            (new QRCode($options))->render($data_for_qr, $qr_file_path);

            // Pfad des QR-Codes in der Datenbank aktualisieren
            $db->query('UPDATE devices SET qr_code_path = :qr_code_path WHERE id = :id');
            $db->bind(':qr_code_path', QR_CODE_BASE_URL . $qr_file_name);
            $db->bind(':id', $device_id);
            $db->execute();

            $message = '<p style="color:green;">Gerät erfolgreich hinzugefügt und QR-Code generiert!</p>';
        } catch (Exception $e) {
            $message = '<p style="color:red;">Fehler beim Generieren des QR-Codes: ' . $e->getMessage() . '</p>';
        }

    } else {
        $message = '<p style="color:red;">Fehler beim Hinzufügen des Geräts.</p>';
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Gerät hinzufügen</title>
    <style>
        body { font-family: sans-serif; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; }
        input[type="text"], input[type="date"], select, textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>

    <h1>Neues Gerät hinzufügen</h1>

    <?php echo $message; ?>

    <form action="add_device.php" method="post">
        
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="type">Typ:</label>
            <select id="type" name="type" required>
                <option value="">-- Typ auswählen --</option>
                <?php
                $device_types = ['Server', 'Switch', 'Router', 'Firewall', 'PC', 'Access Point', 'Raspberry Pi', 'Storage', 'Sonstiges'];
                foreach ($device_types as $type_option) {
                    echo '<option value="' . htmlspecialchars($type_option) . '">' . htmlspecialchars($type_option) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="barcode_number">Barcode-Nummer:</label>
            <input type="text" id="barcode_number" name="barcode_number" value="<?= htmlspecialchars($new_barcode_id) ?>" readonly>
        </div>

        <div class="form-group">
            <label for="ip_address">IP-Adresse:</label>
            <input type="text" id="ip_address" name="ip_address">
        </div>

        <div class="form-group">
            <label for="mac_address">MAC-Adresse:</label>
            <input type="text" id="mac_address" name="mac_address">
        </div>

        <div class="form-group">
            <label for="location">Standort:</label>
            <input type="text" id="location" name="location">
        </div>

        <div class="form-group">
            <label for="purchase_date">Kaufdatum:</label>
            <input type="date" id="purchase_date" name="purchase_date">
        </div>

        <div class="form-group">
            <label for="warranty_expires">Garantieende:</label>
            <input type="date" id="warranty_expires" name="warranty_expires">
        </div>

        <div class="form-group">
            <label for="specifications">Spezifikationen:</label>
            <textarea id="specifications" name="specifications" rows="4"></textarea>
        </div>

        <div class="form-group">
            <label for="notes">Notizen:</label>
            <textarea id="notes" name="notes" rows="4"></textarea>
        </div>

        <div class="form-group">
            <button type="submit">Gerät hinzufügen</button>
        </div>

    </form>

</body>
</html>
