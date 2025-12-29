<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/Database.php';

include 'includes/header.php';

$db = new Database();
$message = '';

/**
 * Generiert eine 10-stellige Zufallsnummer für Barcode/Inventur.
 * @return string
 */
function generate_random_barcode() {
    return strval(mt_rand(1000000000, 9999999999));
}

$storage_to_edit = null;
$form_title = "Neues Speichermedium hinzufügen";

// -----------------------------------------------------------
// GET-Logik: Daten zum Bearbeiten laden
// -----------------------------------------------------------
if (isset($_GET['edit_id'])) {
    $storage_id = (int)$_GET['edit_id'];
    $db->query("SELECT * FROM storage WHERE id = :id");
    $db->bind(':id', $storage_id);
    $storage_to_edit = $db->single();
    
    if ($storage_to_edit) {
        $form_title = "Speichermedium bearbeiten (ID: {$storage_id})";
    } else {
        $message = "Fehler: Speichermedium nicht gefunden.";
    }
}


// Holen aller Geräte für die Dropdown-Liste
$db->query("SELECT id, name, ip_address FROM devices ORDER BY name");
$devices = $db->resultset();

// Holen aller gespeicherten Speichermedien
$db->query("SELECT s.*, d.name AS device_name, d.ip_address 
            FROM storage s 
            JOIN devices d ON s.device_id = d.id 
            ORDER BY d.name, s.type");
$storage_units = $db->resultset();

// -----------------------------------------------------------
// POST-Logik: Speichern/Aktualisieren
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device_id = $_POST['device_id'];
    $type = $_POST['type'];
    $capacity = $_POST['capacity'];
    $model = $_POST['model'];
    $serial_number = $_POST['serial_number'];
    $storage_id = $_POST['storage_id'] ?? null;
    $barcode_id = $_POST['barcode_id'] ?? generate_random_barcode();
    
    // Duplikatsprüfung (Seriennummer und Barcode)
    $db->query("SELECT id FROM storage 
                WHERE (serial_number = :serial AND serial_number IS NOT NULL AND serial_number != '') 
                OR (barcode_id = :barcode) 
                AND id != :current_id");
    $db->bind(':serial', $serial_number);
    $db->bind(':barcode', $barcode_id);
    // Wenn wir ein neues Element hinzufügen, setzen wir current_id auf 0, sonst auf die ID des zu bearbeitenden Elements
    $db->bind(':current_id', $storage_id ?? 0); 

    if ($db->single()) {
        $message = "Fehler: Die Seriennummer oder Barcode-ID existiert bereits in der Datenbank.";
    } else {
        try {
            if ($storage_id) {
                // UPDATE-Logik
                $db->query("UPDATE storage SET 
                            device_id = :device_id, 
                            type = :type, 
                            capacity = :capacity, 
                            model = :model, 
                            serial_number = :serial_number, 
                            barcode_id = :barcode_id
                            WHERE id = :id");
                
                $db->bind(':id', $storage_id);
                $action_message = "aktualisiert";
            } else {
                // INSERT-Logik
                $db->query("INSERT INTO storage (device_id, type, capacity, model, serial_number, barcode_id) 
                            VALUES (:device_id, :type, :capacity, :model, :serial_number, :barcode_id)");
                $action_message = "hinzugefügt";
            }
            
            // Führe Bindungen aus
            $db->bind(':device_id', $device_id);
            $db->bind(':type', $type);
            $db->bind(':capacity', $capacity);
            $db->bind(':model', $model);
            $db->bind(':serial_number', $serial_number);
            $db->bind(':barcode_id', $barcode_id);

            $db->execute();
            $message = "Speichermedium erfolgreich {$action_message}! Barcode-ID: " . $barcode_id;
            
            // Zurück zur Übersichtsseite
            header("Location: manage_storage.php");
            exit();
            
        } catch (Exception $e) {
            $message = "Fehler beim Speichern: " . $e->getMessage();
        }
    }
}

// Setze Formularwerte für Bearbeitung oder Standardwerte für Neu
$default = (object)[
    'device_id' => $storage_to_edit->device_id ?? '',
    'type' => $storage_to_edit->type ?? 'HDD',
    'capacity' => $storage_to_edit->capacity ?? '',
    'model' => $storage_to_edit->model ?? '',
    'serial_number' => $storage_to_edit->serial_number ?? '',
    'barcode_id' => $storage_to_edit->barcode_id ?? '',
    'storage_id' => $storage_to_edit->id ?? ''
];

?>

<div class="container mt-5">
    <h2>Speichermedien (HDD/SSD/NVMe) verwalten</h2>

    <?php if ($message): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><?= htmlspecialchars($form_title) ?></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="storage_id" value="<?= htmlspecialchars($default->storage_id) ?>">
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="device_id" class="form-label">Host-Gerät</label>
                        <select class="form-select" id="device_id" name="device_id" required>
                            <option value="">Wählen Sie ein Gerät</option>
                            <?php foreach ($devices as $device): ?>
                                <option value="<?= $device->id ?>" <?= ($default->device_id == $device->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($device->name) ?> (<?= htmlspecialchars($device->ip_address) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="type" class="form-label">Typ</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="HDD" <?= ($default->type == 'HDD') ? 'selected' : '' ?>>HDD</option>
                            <option value="SSD" <?= ($default->type == 'SSD') ? 'selected' : '' ?>>SSD</option>
                            <option value="NVMe" <?= ($default->type == 'NVMe') ? 'selected' : '' ?>>NVMe</option>
                        </select>
                        
                        <label for="capacity" class="form-label mt-3">Kapazität (z.B. 4TB, 500GB)</label>
                        <input type="text" class="form-control" id="capacity" name="capacity" value="<?= htmlspecialchars($default->capacity) ?>" required>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="model" class="form-label">Modell/Hersteller</label>
                        <input type="text" class="form-control" id="model" name="model" value="<?= htmlspecialchars($default->model) ?>" required>

                        <label for="serial_number" class="form-label mt-3">Seriennummer</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" value="<?= htmlspecialchars($default->serial_number) ?>">
                        <small class="form-text text-muted">Muss eindeutig sein.</small>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="barcode_id" class="form-label">Barcode ID</label>
                        <?php if ($default->storage_id): ?>
                            <input type="text" class="form-control" id="barcode_id" name="barcode_id" value="<?= htmlspecialchars($default->barcode_id) ?>" readonly>
                            <small class="form-text text-muted">Wird beim Hinzufügen automatisch generiert.</small>
                        <?php else: ?>
                            <input type="text" class="form-control" id="barcode_id" name="barcode_id" value="<?= generate_random_barcode()?>" placeholder="(Automatisch generiert)">
                            <small class="form-text text-muted">Leer lassen für automatische Generierung.</small>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">
                    <?= $default->storage_id ? 'Speichern & Aktualisieren' : 'Speichermedium hinzufügen' ?>
                </button>
                <?php if ($default->storage_id): ?>
                    <a href="manage_storage.php" class="btn btn-secondary mt-3">Abbrechen</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <h3>Gespeicherte Speichermedien</h3>
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Host-Gerät</th>
                <th>Typ</th>
                <th>Kapazität</th>
                <th>Modell</th>
                <th>Seriennummer</th>
                <th>Barcode ID</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($storage_units)): ?>
                <?php foreach ($storage_units as $unit): ?>
                    <tr>
                        <td><?= htmlspecialchars($unit->device_name) ?></td>
                        <td><?= htmlspecialchars($unit->type) ?></td>
                        <td><?= htmlspecialchars($unit->capacity) ?></td>
                        <td><?= htmlspecialchars($unit->model) ?></td>
                        <td><?= htmlspecialchars($unit->serial_number) ?></td>
                        <td><strong><?= htmlspecialchars($unit->barcode_id) ?></strong></td>
                        <td>
                            <a href="manage_storage.php?edit_id=<?= $unit->id ?>" class="btn btn-sm btn-outline-secondary">Bearbeiten</a>
                            </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">Noch keine Speichermedien erfasst.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
