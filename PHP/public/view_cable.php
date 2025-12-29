<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';

include 'includes/header.php';

$db = new Database();
$cable = null;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $db->query('SELECT c.*, d1.name AS from_device_name, d1.type AS from_device_type, d2.name AS to_device_name, d2.type AS to_device_type
                FROM cables c
                LEFT JOIN devices d1 ON c.from_device_id = d1.id
                LEFT JOIN devices d2 ON c.to_device_id = d2.id
                WHERE c.id = :id');
    $db->bind(':id', $id);
    $cable = $db->single();
}
?>

<h2>Kabel-Details</h2>

<?php if (!$cable): ?>
    <p>Kabel nicht gefunden.</p>
<?php else: ?>
    <h3><?php echo htmlspecialchars($cable->name ?: 'Unbenanntes Kabel'); ?></h3>
    <p><strong>Typ:</strong> <?php echo htmlspecialchars($cable->type); ?></p>
    <p><strong>Länge:</strong> <?php echo htmlspecialchars($cable->length_meters); ?> Meter</p>
    <p><strong>Farbe:</strong> <?php echo htmlspecialchars($cable->color); ?></p>
    <p><strong>Notizen:</strong> <?php echo nl2br(htmlspecialchars($cable->notes)); ?></p>

    <h3>Verbindung</h3>
    <p><strong>Von:</strong>
        <?php if ($cable->from_device_id): ?>
            <a href="view_device.php?id=<?php echo $cable->from_device_id; ?>"><?php echo htmlspecialchars($cable->from_device_name); ?></a> (<?php echo htmlspecialchars($cable->from_device_type); ?>)
            - Port: <?php echo htmlspecialchars($cable->from_port); ?>
        <?php else: ?>
            Kein Gerät
        <?php endif; ?>
    </p>
    <p><strong>Zu:</strong>
        <?php if ($cable->to_device_id): ?>
            <a href="view_device.php?id=<?php echo $cable->to_device_id; ?>"><?php echo htmlspecialchars($cable->to_device_name); ?></a> (<?php echo htmlspecialchars($cable->to_device_type); ?>)
            - Port: <?php echo htmlspecialchars($cable->to_port); ?>
        <?php else: ?>
            Kein Gerät
        <?php endif; ?>
    </p>

    <p><a href="manage_cables.php">Zurück zur Kabel-Verwaltung</a></p>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
