<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';


$db = new Database();

// Daten aus allen Tabellen abrufen
$db->query('SELECT * FROM devices ORDER BY name ASC');
$devices = $db->resultset();

$db->query('SELECT * FROM racks ORDER BY name ASC');
$racks = $db->resultset();

$db->query('SELECT * FROM patch_panels ORDER BY name ASC');
$patch_panels = $db->resultset();

$db->query('SELECT * FROM cables ORDER BY name ASC');
$cables = $db->resultset();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Inventar-Dokumentation</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 0;
        }
        .cover-page {
            text-align: center;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .cover-page h1 {
            font-size: 3em;
        }
        .cover-page p {
            font-size: 1.2em;
            margin-top: 20px;
        }
        .report-content {
            padding: 20px;
        }
        h2 {
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        th {
            background-color: #f2f2f2;
        }
        /* CSS für den Druck */
        @media print {
            .cover-page {
                page-break-after: always;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="cover-page">
        <h1>Homelab-Inventar-Dokumentation</h1>
        <p>Erstellt von: <strong>Philipp Lindner</strong></p>
        <p>Stand: <strong><?= date('d.m.Y'); ?></strong></p>
    </div>

    <div class="report-content">
        <div class="no-print" style="margin-bottom: 20px;">
            <a href="index.php">Zurück zum Hauptmenü</a>
        </div>
        
        <h2>Geräte</h2>
        <?php if ($devices): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Typ</th>
                        <th>IP-Adresse</th>
                        <th>Standort</th>
                        <th>Barcode</th>
                        <th>Spezifikationen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><?= htmlspecialchars($device->id) ?></td>
                            <td><?= htmlspecialchars($device->name) ?></td>
                            <td><?= htmlspecialchars($device->type) ?></td>
                            <td><?= htmlspecialchars($device->ip_address) ?></td>
                            <td><?= htmlspecialchars($device->location) ?></td>
                            <td><?= htmlspecialchars($device->barcode_number) ?></td>
                            <td><?= nl2br(htmlspecialchars($device->specifications)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Keine Geräte gefunden.</p>
        <?php endif; ?>

        <h2>Racks</h2>
        <?php if ($racks): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Standort</th>
                        <th>Beschreibung</th>
                        <th>Barcode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($racks as $rack): ?>
                        <tr>
                            <td><?= htmlspecialchars($rack->id) ?></td>
                            <td><?= htmlspecialchars($rack->name) ?></td>
                            <td><?= htmlspecialchars($rack->location) ?></td>
                            <td><?= nl2br(htmlspecialchars($rack->notes)) ?></td>
                            <td><?= htmlspecialchars($rack->barcode_number) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Keine Racks gefunden.</p>
        <?php endif; ?>

        <h2>Patch-Panels</h2>
        <?php if ($patch_panels): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Ports</th>
                        <th>Standort</th>
                        <th>Notizen</th>
                        <th>Barcode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patch_panels as $panel): ?>
                        <tr>
                            <td><?= htmlspecialchars($panel->id) ?></td>
                            <td><?= htmlspecialchars($panel->name) ?></td>
                            <td><?= htmlspecialchars($panel->num_ports) ?></td>
                            <td><?= htmlspecialchars($panel->location) ?></td>
                            <td><?= nl2br(htmlspecialchars($panel->notes)) ?></td>
                            <td><?= htmlspecialchars($panel->barcode_number) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Keine Patch-Panels gefunden.</p>
        <?php endif; ?>

        <h2>Kabel</h2>
        <?php if ($cables): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Beschreibung</th>
                        <th>Barcode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cables as $cable): ?>
                        <tr>
                            <td><?= htmlspecialchars($cable->id) ?></td>
                            <td><?= htmlspecialchars($cable->name) ?></td>
                            <td><?= nl2br(htmlspecialchars($cable->description)) ?></td>
                            <td><?= htmlspecialchars($cable->barcode_number) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Keine Kabel gefunden.</p>
        <?php endif; ?>
    </div>

</body>
</html>
