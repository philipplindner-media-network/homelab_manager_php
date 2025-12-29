<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';

$db = new Database();
$found_item = null;
$item_type = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barcode_number'])) {
    $barcode_number = $_POST['barcode_number'];

    // Suche in der devices-Tabelle
    $db->query("SELECT * FROM devices WHERE barcode_number = :barcode_number");
    $db->bind(':barcode_number', $barcode_number);
    $found_item = $db->single();
    if ($found_item) {
        $item_type = 'Gerät';
    }

    // Wenn nicht gefunden, suche in der cables-Tabelle
    if (!$found_item) {
        $db->query("SELECT * FROM cables WHERE barcode_number = :barcode_number");
        $db->bind(':barcode_number', $barcode_number);
        $found_item = $db->single();
        if ($found_item) {
            $item_type = 'Kabel';
        }
    }

    // Wenn immer noch nicht gefunden, suche in der racks-Tabelle
    if (!$found_item) {
        $db->query("SELECT * FROM racks WHERE barcode_number = :barcode_number");
        $db->bind(':barcode_number', $barcode_number);
        $found_item = $db->single();
        if ($found_item) {
            $item_type = 'Rack';
        }
    }

    // Wenn immer noch nicht gefunden, suche in der patch_panels-Tabelle
    if (!$found_item) {
        $db->query("SELECT * FROM patch_panels WHERE barcode_number = :barcode_number");
        $db->bind(':barcode_number', $barcode_number);
        $found_item = $db->single();
        if ($found_item) {
            $item_type = 'Patch-Panel';
        }
    }

    if (!$found_item) {
        $message = "Kein passendes Element mit der Nummer '" . htmlspecialchars($barcode_number) . "' gefunden.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Barcode Assistent</title>
    <style>
        body { font-family: sans-serif; text-align: center; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        input[type="text"] { width: 80%; padding: 10px; font-size: 1.2em; text-align: center; }
        .details { margin-top: 20px; text-align: left; background: #f9f9f9; padding: 15px; border-radius: 5px; }
        .details h2 { margin-top: 0; }
        .message { color: red; font-weight: bold; }
        .button-group { margin-top: 15px; }
        .button-group button { padding: 10px 20px; font-size: 1em; }
    </style>
</head>
<body>

<div class="container">
    <h1>Barcode Assistent</h1>
    <p>Bitte scannen Sie einen Barcode.</p>
    
    <form id="barcodeForm" method="post" action="barcode_scanner.php">
        <input type="text" name="barcode_number" id="barcode_input" autofocus autocomplete="off" placeholder="Barcode hier scannen..." required>
        <div class="button-group">
            <button type="submit">Suchen</button>
        </div>
    </form>


    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($found_item): ?>
        <div class="details">
            <h2>Details für <?= htmlspecialchars($item_type) ?> (ID: <?= htmlspecialchars($found_item->id) ?>)</h2>
            <p><strong>Name:</strong> <?= htmlspecialchars($found_item->name) ?></p>

            <?php if ($item_type === 'Gerät'): ?>
                <p><strong>Typ:</strong> <?= htmlspecialchars($found_item->type) ?></p>
                <p><strong>IP-Adresse:</strong> <?= htmlspecialchars($found_item->ip_address) ?></p>
                <p><strong>Standort:</strong> <?= htmlspecialchars($found_item->location) ?></p>
                <p><a href="view_device.php?id=<?= htmlspecialchars($found_item->id) ?>">Zur vollständigen Ansicht</a></p>
            <?php elseif ($item_type === 'Rack'): ?>
                <p><strong>Beschreibung:</strong> <?= htmlspecialchars($found_item->notes) ?></p>
                <p><strong>Standort:</strong> <?= htmlspecialchars($found_item->location) ?></p>
            <?php elseif ($item_type === 'Kabel'): ?>
                <p><strong>Beschreibung:</strong> <?= htmlspecialchars($found_item->notes) ?></p>
            <?php elseif ($item_type === 'Patch-Panel'): ?>
                 <p><strong>Beschreibung:</strong> <?= htmlspecialchars($found_item->notes) ?></p>
                 <p><strong>Ports:</strong> <?= htmlspecialchars($found_item->num_ports) ?></p>
            <?php endif; ?>

            </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const barcodeInput = document.getElementById('barcode_input');
        let searchTimeout = null;

        barcodeInput.focus();

        barcodeInput.addEventListener('keyup', (e) => {
            // Lösche den vorherigen Timer, um Mehrfacheingaben zu vermeiden
            clearTimeout(searchTimeout);

            // Setze einen neuen Timer
            searchTimeout = setTimeout(() => {
                // Überprüfe, ob das Eingabefeld einen Wert hat
                if (barcodeInput.value.trim().length > 0) {
                    // Sende das Formular, sobald der Timer abgelaufen ist
                    document.getElementById('barcodeForm').submit();
                }
            }, 300); // 300ms Verzögerung
        });
    });
</script>

</body>
</html>
