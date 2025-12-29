<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';

include 'includes/header.php';

$db = new Database();
$message = '';

/**
 * Generiert eine 10-stellige Zufallsnummer.
 * @return string
 */
function generate_random_barcode() {
    // Generiert eine Zahl zwischen 1000000000 (10-stellig) und 9999999999
    return strval(mt_rand(1000000000, 9999999999));
}

// Geräte für Dropdowns abrufen
$db->query('SELECT id, name, type, ip_address FROM devices ORDER BY name ASC');
$barcode_num = generate_random_barcode();

// Kabel hinzufügen
if (isset($_POST['add_cable'])) {
    $barcode_num= generate_random_barcode();
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $length_meters = !empty($_POST['length_meters']) ? (float)$_POST['length_meters'] : null;
    $color = trim($_POST['color']);
    $notes = trim($_POST['notes']);
    $from_device_id = !empty($_POST['from_device_id']) ? (int)$_POST['from_device_id'] : null;
    $from_port = trim($_POST['from_port']);
    $to_device_id = !empty($_POST['to_device_id']) ? (int)$_POST['to_device_id'] : null;
    $to_port = trim($_POST['to_port']);

    if (empty($type) || (empty($from_device_id) && empty($to_device_id))) {
        $message = '<p style="success-message">Kabeltyp und mindestens ein Endpunkt sind Pflichtfelder.</p>';
    } elseif ($from_device_id == $to_device_id && !empty($from_device_id)) {
        $message = '<p style="error-messag">Ein Kabel kann nicht mit demselben Gerät verbunden werden.</p>';
    } else {
        $db->query('INSERT INTO cables (name, type, length_meters, color, notes, from_device_id, from_port, to_device_id, to_port, barcode_number) VALUES (:name, :type, :length_meters, :color, :notes, :from_device_id, :from_port, :to_device_id, :to_port, :barcode_num)');
        $db->bind(':name', $name);
        $db->bind(':type', $type);
        $db->bind(':length_meters', $length_meters);
        $db->bind(':color', $color);
        $db->bind(':notes', $notes);
        $db->bind(':from_device_id', $from_device_id);
        $db->bind(':from_port', $from_port);
        $db->bind(':to_device_id', $to_device_id);
        $db->bind(':to_port', $to_port);
        $db->bind(':barcodew_num', $barcode_num);
        
        if ($db->execute()) {
            $message = '<p style="success-message">Kabel erfolgreich hinzugefügt.</p>';
        } else {
            $message = '<p style="error-messag">Fehler beim Hinzufügen des Kabels.</p>';
        }
    }
}

// Kabel bearbeiten (ähnlich wie bei Racks, hier stark vereinfacht)
// ... Implementierung für Bearbeiten ...

// Kabel löschen
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if ($id > 0) {
        $db->query('DELETE FROM cables WHERE id = :id');
        $db->bind(':id', $id);
        if ($db->execute()) {
            $message = '<p style="color: green;">Kabel erfolgreich gelöscht.</p>';
        } else {
            $message = '<p style="color: red;">Fehler beim Löschen des Kabels.</p>';
        }
    }
}

// Alle Kabel abrufen
$db->query('SELECT c.*, d1.name AS from_device_name, d2.name AS to_device_name
            FROM cables c
            LEFT JOIN devices d1 ON c.from_device_id = d1.id
            LEFT JOIN devices d2 ON c.to_device_id = d2.id
            ORDER BY c.name ASC');
$cables = $db->resultSet();
?>

<h2>Kabel-Verwaltung</h2>
<?php echo $message; ?>

<h3>Neues Kabel hinzufügen</h3>
<form action="manage_cables.php" method="POST">
    <label for="cable_name">Name (optional):</label>
    <input type="text" id="cable_name" name="name">

    <label for="cable_type">Typ:</label>
    <select id="cable_type" name="type" required>
        <option value="">Bitte wählen</option>
        <option value="LAN">LAN</option>
        <option value="Fiber Optic">Glasfaser</option>
        <option value="Power">Strom</option>
        <option value="Other">Sonstiges</option>
    </select>

    <label for="length_meters">Länge (Meter):</label>
    <input type="number" id="length_meters" name="length_meters" step="0.01" min="0">

    <label for="color">Farbe:</label>
    <input type="text" id="color" name="color">

    <label for="from_device_id">Von Gerät:</label>
    <select id="from_device_id" name="from_device_id">
        <option value="">Kein Gerät</option>
        <?php foreach ($devices as $device): ?>
            <option value="<?php echo htmlspecialchars($device->id); ?>">
                <?php echo htmlspecialchars($device->name); ?> (<?php echo htmlspecialchars($device->type); ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <label for="from_port">Von Port:</label>
    <input type="text" id="from_port" name="from_port">

    <label for="to_device_id">Zu Gerät:</label>
    <select id="to_device_id" name="to_device_id">
        <option value="">Kein Gerät</option>
        <?php foreach ($devices as $device): ?>
            <option value="<?php echo htmlspecialchars($device->id); ?>">
                <?php echo htmlspecialchars($device->name); ?> (<?php echo htmlspecialchars($device->type); ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <label for="to_port">Zu Port:</label>
    <input type="text" id="to_port" name="to_port">

    <label for="cable_notes">Notizen:</label>
    <textarea id="cable_notes" name="notes" rows="3"></textarea>

    <button type="submit" name="add_cable">Kabel hinzufügen</button>
</form>

<h3>Vorhandene Kabel</h3>
<?php if (empty($cables)): ?>
    <p>Noch keine Kabel vorhanden.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Typ</th>
                <th>Von</th>
                <th>Zu</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cables as $cable): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cable->name); ?></td>
                    <td><?php echo htmlspecialchars($cable->type); ?></td>
                    <td>
                        <?php if ($cable->from_device_id): ?>
                            <a href="view_device.php?id=<?php echo $cable->from_device_id; ?>"><?php echo htmlspecialchars($cable->from_device_name); ?></a> (<?php echo htmlspecialchars($cable->from_port); ?>)
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($cable->to_device_id): ?>
                            <a href="view_device.php?id=<?php echo $cable->to_device_id; ?>"><?php echo htmlspecialchars($cable->to_device_name); ?></a> (<?php echo htmlspecialchars($cable->to_port); ?>)
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="view_cable.php?id=<?php echo $cable->id; ?>">Details</a> |
                        <a href="manage_cables.php?edit_id=<?php echo $cable->id; ?>">Bearbeiten</a> |
                        <a href="manage_cables.php?delete_id=<?php echo $cable->id; ?>" onclick="return confirm('Sicher, dass du dieses Kabel löschen möchtest?');">Löschen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
