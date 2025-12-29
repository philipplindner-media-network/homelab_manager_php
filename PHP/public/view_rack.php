<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';

include 'includes/header.php';

$db = new Database();
$rack = null;
$devices_in_rack = [];

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Rack-Details abrufen
    $db->query('SELECT * FROM racks WHERE id = :id');
    $db->bind(':id', $id);
    $rack = $db->single();

    if ($rack) {
        // Geräte in diesem Rack abrufen
        $db->query('SELECT * FROM devices WHERE rack_id = :rack_id ORDER BY rack_unit_start ASC');
        $db->bind(':rack_id', $id);
        $devices_in_rack = $db->resultSet();
    }
}
?>

<h2>Rack-Details</h2>

<?php if (!$rack): ?>
    <p>Rack nicht gefunden.</p>
<?php else: ?>
    <h3><?php echo htmlspecialchars($rack->name); ?></h3>
    <p><strong>Standort:</strong> <?php echo htmlspecialchars($rack->location); ?></p>
    <p><strong>Höheneinheiten (HE):</strong> <?php echo htmlspecialchars($rack->height_units); ?></p>
    <p><strong>Notizen:</strong> <?php echo nl2br(htmlspecialchars($rack->notes)); ?></p>

    <h3>Geräte in diesem Rack</h3>
    <?php if (empty($devices_in_rack)): ?>
        <p>Keine Geräte in diesem Rack zugewiesen.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Gerät</th>
                    <th>Typ</th>
                    <th>HE Start</th>
                    <th>HE Ende</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devices_in_rack as $device): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($device->name); ?></td>
                        <td><?php echo htmlspecialchars($device->type); ?></td>
                        <td><?php echo htmlspecialchars($device->rack_unit_start); ?></td>
                        <td><?php echo htmlspecialchars($device->rack_unit_end); ?></td>
                        <td><a href="view_device.php?id=<?php echo $device->id; ?>">Details</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="manage_racks.php">Zurück zur Rack-Verwaltung</a></p>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
