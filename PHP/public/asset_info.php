<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/Database.php';

include 'includes/header.php';

$db = new Database();
$barcode_query = $_GET['barcode'] ?? null;
$asset_data = null;
$related_storage = [];
$related_cables = [];
$related_rack = null;
$error_message = '';
$asset_type = ''; // Zur Identifizierung des Haupt-Assets

if ($barcode_query) {
    try {
        // -----------------------------------------------------------
        // 1. Suche in 'DEVICES' (Hauptgeräte)
        // -----------------------------------------------------------
        $db->query("SELECT * FROM devices WHERE barcode_number = :barcode");
        $db->bind(':barcode', $barcode_query);
        $asset_data = $db->single();
        
        if ($asset_data) {
            $asset_type = 'Gerät (Device)';
            $device_id = $asset_data->id;
            
            // Suche Querverweise, wenn es ein Gerät ist
            
            // Zugehöriger Speicher
            $db->query("SELECT * FROM storage WHERE device_id = :device_id");
            $db->bind(':device_id', $device_id);
            $related_storage = $db->resultset();
            
            // Zugehöriger Rack (falls verlinkt)
            if (!empty($asset_data->rack_id)) {
                 $db->query("SELECT name, location, barcode_number FROM racks WHERE id = :rack_id");
                 $db->bind(':rack_id', $asset_data->rack_id);
                 $related_rack = $db->single();
            }

        } else {
            // -----------------------------------------------------------
            // 2. Suche in 'STORAGE' (Festplatten)
            // Beachte: Hier wird 'barcode_id' verwendet!
            // -----------------------------------------------------------
            $db->query("SELECT s.*, d.name AS device_name, d.ip_address, d.barcode_number AS device_barcode
                        FROM storage s
                        JOIN devices d ON s.device_id = d.id
                        WHERE s.barcode_id = :barcode");
            $storage_data = $db->single();

            if ($storage_data) {
                $asset_type = 'Speichermedium (Storage)';
                
                // Füge die Platte selbst zur Anzeige in der Querverweis-Liste hinzu
                $related_storage = [$storage_data]; 
                
                // Setze das Host-Gerät als Haupt-Asset
                $db->query("SELECT * FROM devices WHERE id = :device_id");
                $db->bind(':device_id', $storage_data->device_id);
                $asset_data = $db->single();
                $device_id = $asset_data->id;

                // Zugehöriger Rack vom Host-Gerät
                if (!empty($asset_data->rack_id)) {
                     $db->query("SELECT name, location, barcode_number FROM racks WHERE id = :rack_id");
                     $db->bind(':rack_id', $asset_data->rack_id);
                     $related_rack = $db->single();
                }

            } else {
                // -----------------------------------------------------------
                // 3. Suche in 'CABLES' (Kabel)
                // -----------------------------------------------------------
                $db->query("SELECT * FROM cables WHERE barcode_number = :barcode");
                $asset_data = $db->single();

                if ($asset_data) {
                    $asset_type = 'Kabel (Cable)';
                    
                    // Suche Querverweise, wenn es ein Kabel ist
                    
                    // Querverweis Start-Gerät
                    if (!empty($asset_data->from_device_id)) {
                        $db->query("SELECT name, ip_address, barcode_number FROM devices WHERE id = :id");
                        $db->bind(':id', $asset_data->from_device_id);
                        $asset_data->from_device_info = $db->single();
                    }
                    // Querverweis End-Gerät
                    if (!empty($asset_data->to_device_id)) {
                        $db->query("SELECT name, ip_address, barcode_number FROM devices WHERE id = :id");
                        $db->bind(':id', $asset_data->to_device_id);
                        $asset_data->to_device_info = $db->single();
                    }

                } else {
                    // -----------------------------------------------------------
                    // 4. Suche in 'RACKS' (Racks)
                    // -----------------------------------------------------------
                    $db->query("SELECT * FROM racks WHERE barcode_number = :barcode");
                    $asset_data = $db->single();

                    if ($asset_data) {
                        $asset_type = 'Rack (Rack)';
                        // Suche alle Geräte in diesem Rack
                        $db->query("SELECT name, barcode_number, ip_address, rack_unit_start, rack_unit_end 
                                    FROM devices 
                                    WHERE rack_id = :rack_id 
                                    ORDER BY rack_unit_start");
                        $db->bind(':rack_id', $asset_data->id);
                        $asset_data->devices_in_rack = $db->resultset();
                    } else {
                        $error_message = "Kein Asset mit der Barcode ID '{$barcode_query}' gefunden.";
                    }
                }
            }
        }

    } catch (Exception $e) {
        $error_message = 'Datenbankfehler: ' . nl2br(htmlspecialchars($e->getMessage()));
    }
}
?>

<div class="container mt-5">
    <h2>Asset-Informations-Dashboard</h2>
    <p>Geben Sie eine Barcode ID ein, um alle zugehörigen Informationen zu sehen.</p>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="asset_info.php" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="barcode" class="form-label">Barcode ID eingeben:</label>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="barcode" name="barcode" 
                           value="<?= htmlspecialchars($barcode_query ?? '') ?>" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Suchen</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>

    <?php if ($asset_data): ?>
        <hr>
        <div class="row">
            <div class="col-md-12">
                <h3 class="mb-3">
                    Haupt-Asset gefunden: <span class="badge bg-primary"><?= htmlspecialchars($asset_type) ?></span>
                    
                    <?php if (in_array($asset_type, ['Gerät (Device)', 'Rack (Rack)'])): ?>
                         <small class="text-muted">(<?= htmlspecialchars($asset_data->name) ?>)</small>
                    <?php elseif ($asset_type === 'Speichermedium (Storage)'): ?>
                         <small class="text-muted">(Host: <?= htmlspecialchars($asset_data->device_name) ?>)</small>
                    <?php endif; ?>
                </h3>
            </div>
            
            <?php if ($asset_type === 'Kabel (Cable)'): ?>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5>Kabel-Details</h5>
                            <p><strong>Typ:</strong> <?= htmlspecialchars($asset_data->type) ?> (<?= htmlspecialchars($asset_data->length_meters) ?>m)</p>
                            <p><strong>Barcode ID:</strong> <?= htmlspecialchars($asset_data->barcode_number) ?></p>
                            <p><strong>Start:</strong> <?= htmlspecialchars($asset_data->from_port) ?> 
                                <?php if (!empty($asset_data->from_device_info)): ?>
                                    (Gerät: <a href="?barcode=<?= $asset_data->from_device_info->barcode_number ?>"><?= htmlspecialchars($asset_data->from_device_info->name) ?></a>)
                                <?php endif; ?>
                            </p>
                            <p><strong>Ende:</strong> <?= htmlspecialchars($asset_data->to_port) ?>
                                <?php if (!empty($asset_data->to_device_info)): ?>
                                    (Gerät: <a href="?barcode=<?= $asset_data->to_device_info->barcode_number ?>"><?= htmlspecialchars($asset_data->to_device_info->name) ?></a>)
                                <?php endif; ?>
                            </p>
                            <p><strong>Beschreibung:</strong> <?= nl2br(htmlspecialchars($asset_data->description)) ?></p>
                        </div>
                    </div>
                </div>
            <?php elseif ($asset_type === 'Rack (Rack)'): ?>
                 <div class="col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>Rack-Details</h5>
                            <p><strong>Name:</strong> <?= htmlspecialchars($asset_data->name) ?></p>
                            <p><strong>Barcode ID:</strong> <?= htmlspecialchars($asset_data->barcode_number) ?></p>
                            <p><strong>Location:</strong> <?= htmlspecialchars($asset_data->location) ?></p>
                            <p><strong>Höheneinheiten:</strong> <?= htmlspecialchars($asset_data->height_units) ?></p>
                            <p><strong>Notizen:</strong> <?= nl2br(htmlspecialchars($asset_data->notes)) ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>Geräte-Details</h5>
                            <p><strong>Name:</strong> <?= htmlspecialchars($asset_data->name) ?></p>
                            <p><strong>Barcode ID:</strong> <?= htmlspecialchars($asset_data->barcode_number) ?></p>
                            <p><strong>IP-Adresse:</strong> <?= htmlspecialchars($asset_data->ip_address) ?></p>
                            <p><strong>MAC-Adresse:</strong> <?= htmlspecialchars($asset_data->mac_address) ?></p>
                            <p><strong>Location:</strong> <?= htmlspecialchars($asset_data->location) ?></p>
                            <p><strong>Beschreibung:</strong> <?= nl2br(htmlspecialchars($asset_data->description)) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($related_rack)): ?>
            <h4 class="mt-5">Zugehöriges Rack</h4>
             <p>Das Gerät befindet sich in Rack: 
                <a href="?barcode=<?= $related_rack->barcode_number ?>">
                    <?= htmlspecialchars($related_rack->name) ?> (<?= htmlspecialchars($related_rack->location) ?>)
                </a>
             </p>
        <?php endif; ?>

        <?php if ($asset_type === 'Rack (Rack)' && !empty($asset_data->devices_in_rack)): ?>
            <h4 class="mt-5">Geräte in diesem Rack (<?= count($asset_data->devices_in_rack) ?>)</h4>
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Gerät</th>
                        <th>IP</th>
                        <th>Barcode</th>
                        <th>HE Start/Ende</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($asset_data->devices_in_rack as $device): ?>
                        <tr>
                            <td><?= htmlspecialchars($device->name) ?></td>
                            <td><?= htmlspecialchars($device->ip_address) ?></td>
                            <td>
                                <a href="?barcode=<?= htmlspecialchars($device->barcode_number) ?>">
                                    <?= htmlspecialchars($device->barcode_number) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($device->rack_unit_start) ?> - <?= htmlspecialchars($device->rack_unit_end) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <?php if (!empty($related_storage)): ?>
            <h4 class="mt-5">Zugehörige Speichermedien (<?= count($related_storage) ?>)</h4>
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Typ</th>
                        <th>Kapazität</th>
                        <th>Modell</th>
                        <th>Seriennummer</th>
                        <th>Barcode ID</th>
                        <th>Beschreibung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($related_storage as $unit): ?>
                        <tr>
                            <td><?= htmlspecialchars($unit->type) ?></td>
                            <td><?= htmlspecialchars($unit->capacity) ?></td>
                            <td><?= htmlspecialchars($unit->model) ?></td>
                            <td><?= htmlspecialchars($unit->serial_number) ?></td>
                            <td>
                                <a href="?barcode=<?= htmlspecialchars($unit->barcode_id) ?>">
                                    <?= htmlspecialchars($unit->barcode_id) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($unit->description) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
