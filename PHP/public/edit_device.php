<?php
require_once 'includes/auth_check.php'; // Stellt sicher, dass der Benutzer angemeldet ist
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Für die QR-Code-Bibliothek

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

include 'includes/header.php';

$db = new Database();
$message = '';
$device_id = null;
$device = null;

// Racks für das Dropdown abrufen
$db->query('SELECT id, name FROM racks ORDER BY name ASC');
$racks = $db->resultSet();

if (isset($_GET['id'])) {
    $device_id = (int)$_GET['id'];
    $db->query('SELECT * FROM devices WHERE id = :id');
    $db->bind(':id', $device_id);
    $device = $db->single();

    if (!$device) {
        $message = '<p style="error-message">Gerät nicht gefunden.</p>';
        $device_id = null; // Setze ID auf null, damit das Formular nicht angezeigt wird
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $device_id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $ip_address = trim($_POST['ip_address']);
    $mac_address = trim($_POST['mac_address']);
    $location = trim($_POST['location']);
    $purchase_date = trim($_POST['purchase_date']);
    $warranty_expires = trim($_POST['warranty_expires']);
    $specifications = trim($_POST['specifications']);
    $notes = trim($_POST['notes']);

    // Neue Felder für Rack-Zuweisung
    $rack_id = !empty($_POST['rack_id']) ? (int)$_POST['rack_id'] : null;
    $rack_unit_start = !empty($_POST['rack_unit_start']) ? (int)$_POST['rack_unit_start'] : null;
    $rack_unit_end = !empty($_POST['rack_unit_end']) ? (int)$_POST['rack_unit_end'] : null;

    // Optional: Validierung für Rack HE
    if ($rack_id !== null) {
        if ($rack_unit_start === null || $rack_unit_start <= 0) {
            $message = '<p style="error-message">Bitte gib einen gültigen Start-HE für das Rack an.</p>';
        }
        // Optional: Prüfen, ob Rack HE Ende größer oder gleich Start ist
        if ($rack_unit_end !== null && $rack_unit_end < $rack_unit_start) {
             $message = '<p style="error-message">Rack HE Ende muss größer oder gleich Rack HE Start sein.</p>';
        }
    }

    if (empty($message)) { // Nur fortfahren, wenn keine Validierungsfehler vorliegen
        $db->query('UPDATE devices SET 
                        name = :name, 
                        type = :type, 
                        ip_address = :ip_address, 
                        mac_address = :mac_address, 
                        location = :location, 
                        purchase_date = :purchase_date, 
                        warranty_expires = :warranty_expires, 
                        specifications = :specifications, 
                        notes = :notes,
                        rack_id = :rack_id,
                        rack_unit_start = :rack_unit_start,
                        rack_unit_end = :rack_unit_end
                    WHERE id = :id');

        $db->bind(':name', $name);
        $db->bind(':type', $type);
        $db->bind(':ip_address', $ip_address);
        $db->bind(':mac_address', $mac_address);
        $db->bind(':location', $location);
        $db->bind(':purchase_date', $purchase_date);
        $db->bind(':warranty_expires', $warranty_expires);
        $db->bind(':specifications', $specifications);
        $db->bind(':notes', $notes);
        $db->bind(':rack_id', $rack_id);
        $db->bind(':rack_unit_start', $rack_unit_start);
        $db->bind(':rack_unit_end', $rack_unit_end);
        $db->bind(':id', $device_id);

        if ($db->execute()) {
            // QR-Code bei Aktualisierung neu generieren ist hier nicht zwingend notwendig,
            // da der QR-Code nur auf die view_device.php mit der ID verweist.
            // Falls du QR-Codes erstellen würdest, die Gerätedaten direkt enthalten,
            // müsstest du ihn hier neu generieren.

            $message = '<p style="success-message">Gerät erfolgreich aktualisiert!</p>';
            // Gerät erneut abrufen, um aktualisierte Daten im Formular anzuzeigen
            $db->query('SELECT * FROM devices WHERE id = :id');
            $db->bind(':id', $device_id);
            $device = $db->single();

        } else {
            $message = '<p style="error-message">Fehler beim Aktualisieren des Geräts.</p>';
        }
    }
}
?>

<h2>Gerät bearbeiten: <?php echo htmlspecialchars($device ? $device->name : ''); ?></h2>
<?php echo $message; ?>

<?php if ($device_id && $device): ?>
    <form action="edit_device.php" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($device->id); ?>">

        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($device->name); ?>" required>

        <label for="type">Typ:</label>
        <input type="text" id="type" name="type" value="<?php echo htmlspecialchars($device->type); ?>">

        <label for="ip_address">IP-Adresse:</label>
        <input type="text" id="ip_address" name="ip_address" value="<?php echo htmlspecialchars($device->ip_address); ?>">

        <label for="mac_address">MAC-Adresse:</label>
        <input type="text" id="mac_address" name="mac_address" value="<?php echo htmlspecialchars($device->mac_address); ?>">

        <label for="location">Standort:</label>
        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($device->location); ?>">

        <label for="rack_id">Rack:</label>
        <select id="rack_id" name="rack_id">
            <option value="">Keinem Rack zuweisen</option>
            <?php foreach ($racks as $rack): ?>
                <option value="<?php echo htmlspecialchars($rack->id); ?>"
                    <?php echo ($device->rack_id == $rack->id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($rack->name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="rack_unit_start">Rack HE Start:</label>
        <input type="number" id="rack_unit_start" name="rack_unit_start" min="1"
               value="<?php echo htmlspecialchars($device->rack_unit_start); ?>">

        <label for="rack_unit_end">Rack HE Ende (optional):</label>
        <input type="number" id="rack_unit_end" name="rack_unit_end" min="1"
               value="<?php echo htmlspecialchars($device->rack_unit_end); ?>">

        <label for="purchase_date">Kaufdatum:</label>
        <input type="date" id="purchase_date" name="purchase_date" value="<?php echo htmlspecialchars($device->purchase_date); ?>">

        <label for="warranty_expires">Garantie bis:</label>
        <input type="date" id="warranty_expires" name="warranty_expires" value="<?php echo htmlspecialchars($device->warranty_expires); ?>">

        <label for="specifications">Spezifikationen:</label>
        <textarea id="specifications" name="specifications" rows="5"><?php echo htmlspecialchars($device->specifications); ?></textarea>

        <label for="notes">Notizen:</label>
        <textarea id="notes" name="notes" rows="5"><?php echo htmlspecialchars($device->notes); ?></textarea>

        <button type="submit">Änderungen speichern</button>
    </form>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
