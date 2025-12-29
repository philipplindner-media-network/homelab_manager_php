<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';

include 'includes/header.php';

$db = new Database();

// Geräte abrufen
$db->query('SELECT * FROM devices ORDER BY name ASC');
$devices = $db->resultSet();
?>

<h2>Deine HomeLab Geräte</h2>

<?php if (empty($devices)): ?>
    <p>Noch keine Geräte vorhanden. Füge ein neues Gerät hinzu!</p>
<?php else: ?>
    <div class="device-list">
        <?php foreach ($devices as $device): ?>
            <div class="device-card">
                <h3><?php echo htmlspecialchars($device->name); ?></h3>
                <p><strong>Typ:</strong> <?php echo htmlspecialchars($device->type); ?></p>
                <p><strong>IP:</strong> <?php echo htmlspecialchars($device->ip_address); ?></p>
                <p><strong>Standort:</strong> <?php echo htmlspecialchars($device->location); ?></p>
                <div class="device-actions">
                    <a href="view_device.php?id=<?php echo $device->id; ?>">Details</a>
                    <a href="edit_device.php?id=<?php echo $device->id; ?>">Bearbeiten</a>
                    <a href="delete_device.php?id=<?php echo $device->id; ?>" onclick="return confirm('Sicher, dass du dieses Gerät löschen möchtest?');" class="delete">Löschen</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
