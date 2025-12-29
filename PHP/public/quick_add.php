
<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

//include 'includes/header.php';

function generateRandomBarcodeId() {
    $id = '';
    for ($i = 0; $i < 10; $i++) {
        $id .= mt_rand(0, 9);
    }
    return $id;
}

$db = new Database();
$message = '';
$qr_code_display_path = '';
$generated_barcode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $location = $_POST['location'];
    
    // Automatisch eine Barcode-Nummer generieren
    $barcode_number = generateRandomBarcodeId();
    $generated_barcode = $barcode_number;
    $qr_code_path = '';
    
    try {
        // Gerät in die Datenbank einfügen
        $db->query("INSERT INTO devices (name, type, location, barcode_number) VALUES (:name, :type, :location, :barcode_number)");
        $db->bind(':name', $name);
        $db->bind(':type', $type);
        $db->bind(':location', $location);
        $db->bind(':barcode_number', $barcode_number);
        $db->execute();
        
        $device_id = $db->lastInsertId();
        
        if ($device_id) {
            // QR-Code erstellen
            $qr_code_dir = 'qr_codes/';
            if (!is_dir($qr_code_dir)) {
                mkdir($qr_code_dir, 0777, true);
            }
            
            // Link zur Detailansicht des Geräts
            $data_for_qr = QR_CODE_BASE_URL . 'view_device.php?id=' . $device_id;
            $qr_file_name = 'device_' . $device_id . '.png';
            $qr_file_path = $qr_code_dir . $qr_file_name;

            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel'   => 0,
                'scale'      => 8,
                'quietzone'  => 4,
            ]);

            (new QRCode($options))->render($data_for_qr, $qr_file_path);
            
            // Datenbank-Eintrag mit dem QR-Code-Pfad aktualisieren
            $db->query("UPDATE devices SET qr_code_path = :qr_code_path WHERE id = :id");
            $db->bind(':qr_code_path', $qr_file_path);
            $db->bind(':id', $device_id);
            $db->execute();
            
            $message = "Gerät erfolgreich hinzugefügt!";
            $qr_code_display_path = $qr_code_path;

        } else {
            $message = "Fehler beim Hinzufügen des Geräts.";
        }
    
    } catch (PDOException $e) {
        $message = "Datenbankfehler: " . $e->getMessage();
    } catch (Exception $e) {
        $message = "Fehler beim Erstellen des QR-Codes: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Gerät schnell hinzufügen</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 600px;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #2c3e50;
            font-size: 2em;
            margin-bottom: 5px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        form div {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #3498db;
        }
        .submit-button {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .submit-button:hover {
            background-color: #2980b9;
        }
        .message-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .message-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .message-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .qr-code-section {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background-color: #fafafa;
            border: 1px dashed #ccc;
            border-radius: 8px;
        }
        .qr-code-section h3 {
            margin-top: 0;
            color: #34495e;
        }
        .qr-code-section img {
            max-width: 150px;
            height: auto;
            border: 5px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            margin-top: 15px;
        }
        .barcode-number {
            font-weight: bold;
            margin-top: 10px;
            color: #333;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="index.php" class="back-link">&larr; Zurück zum Hauptmenü</a>
        <h1>Gerät schnell hinzufügen</h1>
    </div>
    
    <?php if ($message): ?>
        <div class="message-box <?= strpos($message, 'erfolgreich') !== false ? 'message-success' : 'message-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($qr_code_display_path): ?>
        <div class="qr-code-section">
            <h3>Gerät erfolgreich hinzugefügt!</h3>
            <p>Verwende diesen QR-Code für den schnellen Zugriff:</p>
            <p class="barcode-number">Barcode: <?= htmlspecialchars($generated_barcode) ?></p>
            <img src="<?= htmlspecialchars($qr_code_display_path) ?>" alt="QR Code für das neue Gerät">
        </div>
    <?php else: ?>
        <form method="post" action="quick_add.php">
            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="type">Typ:</label>
                <input type="text" id="type" name="type" required>
            </div>
            <div>
                <label for="location">Standort:</label>
                <input type="text" id="location" name="location" required>
            </div>
            <button type="submit" class="submit-button">Gerät hinzufügen</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
