<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';

include 'includes/header.php';

$db = new Database();
$message = '';

// Rack hinzufügen
if (isset($_POST['add_rack'])) {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $height_units = (int)$_POST['height_units'];
    $notes = trim($_POST['notes']);

    if (empty($name) || $height_units <= 0) {
        $message = '<p style="error-message">Name und Höheneinheiten sind Pflichtfelder und HE muss > 0 sein.</p>';
    } else {
        $db->query('INSERT INTO racks (name, location, height_units, notes) VALUES (:name, :location, :height_units, :notes)');
        $db->bind(':name', $name);
        $db->bind(':location', $location);
        $db->bind(':height_units', $height_units);
        $db->bind(':notes', $notes);
        if ($db->execute()) {
            $message = '<p style="success-message">Rack erfolgreich hinzugefügt.</p>';
        } else {
            $message = '<p style="error-message">Fehler beim Hinzufügen des Racks. Name möglicherweise bereits vergeben.</p>';
        }
    }
}

// Rack bearbeiten
if (isset($_POST['edit_rack'])) {
    $id = (int)$_POST['rack_id'];
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $height_units = (int)$_POST['height_units'];
    $notes = trim($_POST['notes']);

    if (empty($name) || $height_units <= 0 || $id <= 0) {
        $message = '<p style="error-message">Ungültige Daten für die Bearbeitung.</p>';
    } else {
        $db->query('UPDATE racks SET name = :name, location = :location, height_units = :height_units, notes = :notes WHERE id = :id');
        $db->bind(':name', $name);
        $db->bind(':location', $location);
        $db->bind(':height_units', $height_units);
        $db->bind(':notes', $notes);
        $db->bind(':id', $id);
        if ($db->execute()) {
            $message = '<p style="success-message">Rack erfolgreich aktualisiert.</p>';
        } else {
            $message = '<p style="error-message">Fehler beim Aktualisieren des Racks. Name möglicherweise bereits vergeben.</p>';
        }
    }
}

// Rack löschen
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if ($id > 0) {
        // Geräte, die diesem Rack zugeordnet sind, werden automatisch auf NULL gesetzt (ON DELETE SET NULL)
        $db->query('DELETE FROM racks WHERE id = :id');
        $db->bind(':id', $id);
        if ($db->execute()) {
            $message = '<p style="color: green;">Rack erfolgreich gelöscht.</p>';
        } else {
            $message = '<p style="color: red;">Fehler beim Löschen des Racks.</p>';
        }
    }
}

// Alle Racks abrufen
$db->query('SELECT * FROM racks ORDER BY name ASC');
$racks = $db->resultSet();
?>

<h2>Rack-Verwaltung</h2>
<?php echo $message; ?>

<h3>Neues Rack hinzufügen</h3>
<form action="manage_racks.php" method="POST">
    <label for="rack_name">Name:</label>
    <input type="text" id="rack_name" name="name" required>

    <label for="rack_location">Standort:</label>
    <input type="text" id="rack_location" name="location">

    <label for="height_units">Höheneinheiten (HE):</label>
    <input type="number" id="height_units" name="height_units" min="1" required>

    <label for="rack_notes">Notizen:</label>
    <textarea id="rack_notes" name="notes" rows="3"></textarea>

    <button type="submit" name="add_rack">Rack hinzufügen</button>
</form>

<h3>Vorhandene Racks</h3>
<?php if (empty($racks)): ?>
    <p>Noch keine Racks vorhanden.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Standort</th>
                <th>HE</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($racks as $rack): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rack->name); ?></td>
                    <td><?php echo htmlspecialchars($rack->location); ?></td>
                    <td><?php echo htmlspecialchars($rack->height_units); ?></td>
                    <td>
                        <a href="view_rack.php?id=<?php echo $rack->id; ?>">Details</a> |
                        <a href="manage_racks.php?edit_id=<?php echo $rack->id; ?>">Bearbeiten</a> |
                        <a href="manage_racks.php?delete_id=<?php echo $rack->id; ?>" onclick="return confirm('Sicher, dass du dieses Rack und alle Gerätezuweisungen löschen möchtest?');">Löschen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    // Bearbeitungsformular anzeigen, wenn edit_id gesetzt ist
    if (isset($_GET['edit_id'])) {
        $edit_id = (int)$_GET['edit_id'];
        $db->query('SELECT * FROM racks WHERE id = :id');
        $db->bind(':id', $edit_id);
        $edit_rack = $db->single();
        if ($edit_rack):
    ?>
            <h3>Rack bearbeiten: <?php echo htmlspecialchars($edit_rack->name); ?></h3>
            <form action="manage_racks.php" method="POST">
                <input type="hidden" name="rack_id" value="<?php echo htmlspecialchars($edit_rack->id); ?>">
                
                <label for="edit_name">Name:</label>
                <input type="text" id="edit_name" name="name" value="<?php echo htmlspecialchars($edit_rack->name); ?>" required>

                <label for="edit_location">Standort:</label>
                <input type="text" id="edit_location" name="location" value="<?php echo htmlspecialchars($edit_rack->location); ?>">

                <label for="edit_height_units">Höheneinheiten (HE):</label>
                <input type="number" id="edit_height_units" name="height_units" value="<?php echo htmlspecialchars($edit_rack->height_units); ?>" min="1" required>

                <label for="edit_notes">Notizen:</label>
                <textarea id="edit_notes" name="notes" rows="3"><?php echo htmlspecialchars($edit_rack->notes); ?></textarea>

                <button type="submit" name="edit_rack">Änderungen speichern</button>
                <a href="manage_racks.php">Abbrechen</a>
            </form>
    <?php
        endif;
    }
    ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
