<?php
require_once 'includes/auth_check.php'; // Stellt sicher, dass der Benutzer angemeldet ist
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';

include 'includes/header.php';

$db = new Database();
$device = null;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; // ID als Integer behandeln für Sicherheit und korrekte Abfrage

    // Gerätedetails abrufen
    $db->query('SELECT * FROM devices WHERE id = :id');
    $db->bind(':id', $id);
    $device = $db->single();
}

// Hardware-Daten von den normalen Spezifikationen trennen
$hardware_info = '';
$normal_specs = $device->specifications ?? '';

// Suche nach dem Trenner für Hardware-Berichte
$separator = "--- Hardware-Bericht (";
$separator_pos = strpos($normal_specs, $separator);

if ($separator_pos !== false) {
    // Wenn der Trenner gefunden wurde, teile den String
    $hardware_info = substr($normal_specs, $separator_pos);
    $normal_specs = substr($normal_specs, 0, $separator_pos);
}

if (!$device): ?>
    <p>Gerät nicht gefunden.</p>
<?php else: ?>
    <h2>Details zu: <?php echo htmlspecialchars($device->name); ?> (ID:<?php echo htmlspecialchars($device->id); ?> Barcod: <?php echo htmlspecialchars($device->barcode_number); ?></h2>

    <div class="device-detail-section">
        <h3>Allgemeine Informationen</h3>
        <p><strong>Typ:</strong> <?php echo htmlspecialchars($device->type); ?></p>
        <p><strong>Standort:</strong> <?php echo htmlspecialchars($device->location); ?></p>
        <p><strong>Kaufdatum:</strong> <?php echo htmlspecialchars($device->purchase_date); ?></p>
        <p><strong>Garantie bis:</strong> <?php echo htmlspecialchars($device->warranty_expires); ?></p>
    </div>

    <div class="device-detail-section">
        <h3>Netzwerk</h3>
        <p><strong>IP-Adresse:</strong> <?php echo htmlspecialchars($device->ip_address); ?></p>
        <p><strong>MAC-Adresse:</strong> <?php echo htmlspecialchars($device->mac_address); ?></p>
    </div>

    <div class="device-detail-section">
        <h3>Rack-Informationen</h3>
        <?php if ($device->rack_id): ?>
            <?php
            // Rack-Namen abrufen
            $db->query('SELECT name FROM racks WHERE id = :rack_id');
            $db->bind(':rack_id', $device->rack_id);
            $rack_info = $db->single();
            ?>
            <p><strong>Rack:</strong> <a href="view_rack.php?id=<?php echo htmlspecialchars($device->rack_id); ?>"><?php echo htmlspecialchars($rack_info->name); ?></a></p>
            <p><strong>HE Position:</strong> <?php echo htmlspecialchars($device->rack_unit_start); ?>
                <?php if ($device->rack_unit_end && $device->rack_unit_end > $device->rack_unit_start): ?>
                    - <?php echo htmlspecialchars($device->rack_unit_end); ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p>Keinem Rack zugewiesen.</p>
        <?php endif; ?>
    </div>
    <div class="curl-link-box">
            <h4>Hardware-Daten aktualisieren</h4>
            <p>Führe den passenden Befehl auf dem Server aus, um die Hardware-Daten zu aktualisieren:</p>
            
            <h5>Für Linux / Unix (z.B. FreeBSD, pfSense)</h5>
            <pre>sudo /bin/bash -c "$(curl -sS https://_YOUR_URL/C/collect_hardware.sh)" -- <?= htmlspecialchars($device->barcode_number) ?> homeLab2015_123456789</pre>

            <h5>Für Windows (PowerShell)</h5>
            <pre>powershell -ExecutionPolicy Bypass -File "C:\Path\to\collect_hardware.ps1" -Barcode "<?= htmlspecialchars($device->barcode_number) ?>" -SecretKey "homeLab2015_123456789"</pre>
            <p style="font-size: 0.8em; color: #555;">Hinweis: Passe den Pfad zur Datei "collect_hardware.ps1" an.</p>
        </div>
    <div class="detail-item">
            <strong>Spezifikationen:</strong>
            <p><?= nl2br(htmlspecialchars(trim($normal_specs))) ?></p>
        </div>
    <?php if (!empty($hardware_info)): ?>
        <div class="hardware-info-block">
            <h3>Automatischer Hardware-Bericht</h3>
            <pre><?= htmlspecialchars($hardware_info) ?></pre>
        </div>
        <?php endif; ?>

    <div class="device-detail-section">
        <h3>Notizen</h3>
        <p><?php echo nl2br(htmlspecialchars($device->notes)); ?></p>
    </div>

    <div class="device-detail-section">
        <h3>Kabelverbindungen</h3>
        <?php
        $db->query('SELECT c.*, d1.name AS from_device_name, d2.name AS to_device_name
                    FROM cables c
                    LEFT JOIN devices d1 ON c.from_device_id = d1.id
                    LEFT JOIN devices d2 ON c.to_device_id = d2.id
                    WHERE c.from_device_id = :device_id OR c.to_device_id = :device_id
                    ORDER BY c.name ASC');
        $db->bind(':device_id', $device->id);
        $connected_cables = $db->resultSet();
        ?>
        <?php if (empty($connected_cables)): ?>
            <p>Keine Kabel mit diesem Gerät verbunden.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Kabel</th>
                        <th>Typ</th>
                        <th>Verbindung</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($connected_cables as $cable): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cable->name ?: 'Unbenanntes Kabel'); ?></td>
                            <td><?php echo htmlspecialchars($cable->type); ?></td>
                            <td>
                                <?php if ($cable->from_device_id == $device->id): ?>
                                    Von Port <?php echo htmlspecialchars($cable->from_port); ?> zu
                                    <?php if ($cable->to_device_id): ?>
                                        <a href="view_device.php?id=<?php echo htmlspecialchars($cable->to_device_id); ?>"><?php echo htmlspecialchars($cable->to_device_name); ?></a> (Port <?php echo htmlspecialchars($cable->to_port); ?>)
                                    <?php else: ?>
                                        Unbekannt (Port <?php echo htmlspecialchars($cable->to_port); ?>)
                                    <?php endif; ?>
                                <?php else: ?>
                                    Von
                                    <?php if ($cable->from_device_id): ?>
                                        <a href="view_device.php?id=<?php echo htmlspecialchars($cable->from_device_id); ?>"><?php echo htmlspecialchars($cable->from_device_name); ?></a> (Port <?php echo htmlspecialchars($cable->from_port); ?>)
                                    <?php else: ?>
                                        Unbekannt (Port <?php htmlspecialchars($cable->from_port); ?>)
                                    <?php endif; ?>
                                    zu Port <?php echo htmlspecialchars($cable->to_port); ?>
                                <?php endif; ?>
                            </td>
                            <td><a href="view_cable.php?id=<?php echo htmlspecialchars($cable->id); ?>">Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>


    <?php if ($device->qr_code_path): ?>
        <div class="qr-code-container">
            <h3>QR-Code für dieses Gerät</h3>
            <img src="<?php echo htmlspecialchars($device->qr_code_path); ?>" alt="QR Code für <?php echo htmlspecialchars($device->name); ?>">
            <p>Scanne diesen Code, um schnell auf diese Details zuzugreifen.</p>
            <p><a href="<?php echo htmlspecialchars($device->qr_code_path); ?>" download="qr_code_<?php echo htmlspecialchars($device->id); ?>.png">QR-Code herunterladen</a></p>
        </div>
    <?php else: ?>
        <p>Kein QR-Code für dieses Gerät verfügbar. (Sollte bei neuen Geräten automatisch generiert werden)</p>
    <?php endif; ?>

    <div class="device-actions">
        <a href="edit_device.php?id=<?php echo htmlspecialchars($device->id); ?>">Gerät bearbeiten</a>
        <a href="index.php">Zurück zur Übersicht</a>
    </div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
