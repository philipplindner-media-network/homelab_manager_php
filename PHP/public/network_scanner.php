<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use UniFi_API\Client as UnifiClient;
use RouterOS\Client as MikrotikClient;
use RouterOS\Query as MikrotikQuery;
use RouterOS\Config;

include 'includes/header.php';

$db = new Database();
$message = '';
$scanned_ports = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_type'])) {
    $api_type = $_POST['api_type'];

    if ($api_type === 'unifi') {
        // UniFi-API-Logik
        $unifi_url = $_POST['unifi_url'];
        $unifi_username = $_POST['unifi_username'];
        $unifi_password = $_POST['unifi_password'];

        $unifi_port =443;

        try {
            $unifi = new UnifiClient($unifi_url, $unifi_username, $unifi_password, $unifi_port);
            $unifi->set_is_selfsigned(true); // NEUE ZEILE FÜR SSL-FIX
            
            // NEUE DEBUGGING-ZEILEN
            echo "URL, die verwendet wird: " . $unifi->get_baseurl() . "<br>";
            echo "Port, der verwendet wird: " . $unifi->get_port() . "<br>";
            exit(); 
            // ENDE DER DEBUGGING-ZEILEN
	    
            $unifi->login();
            
            $devices = $unifi->list_devices();
            
            foreach ($devices as $device) {
                if (isset($device->type) && $device->type === 'usw') {
                    $device_name = $device->name ?: $device->mac;
                    foreach ($device->port_table as $port) {
                        $scanned_ports[] = [
                            'device_name' => $device_name,
                            'device_mac' => $device->mac,
                            'port_number' => $port->port_idx,
                            'port_name' => $port->name,
                            'is_up' => $port->up,
                            'is_wired' => $port->wired,
                            'connection' => $port->up ? ($port->name ?: 'Connected') : 'Disconnected',
                            'connected_to' => $port->up ? ($port->agg_id ?: 'Unknown') : ''
                        ];
                    }
                }
            }
            $message = 'UniFi-Geräte erfolgreich gescannt.';
        } catch (Exception $e) {
            $message = 'UniFi API-Fehler: ' . $e->getMessage();
        }

    } elseif ($api_type === 'mikrotik') {
        // MikroTik-API-Logik
        $mikrotik_ip = $_POST['mikrotik_ip'];
        $mikrotik_username = $_POST['mikrotik_username'];
        $mikrotik_password = $_POST['mikrotik_password'];

        try {
            $client = new MikrotikClient(new Config([
                'host' => $mikrotik_ip,
                'user' => $mikrotik_username,
                'pass' => $mikrotik_password,
            ]));
            
            $ports_info = $client->query('/interface/ethernet/print')->read();
            $ports_status = $client->query('/interface/ethernet/monitor', ['once'])->read();
            
            $device_name = $mikrotik_ip;
            
            foreach ($ports_info as $port_info) {
                $port_name = $port_info['name'];
                $port_status_text = '';
                $is_up = false;
                
                foreach($ports_status as $status) {
                    if (isset($status['name']) && $status['name'] === $port_name) {
                        $port_status_text = $status['status'];
                        $is_up = ($port_status_text === 'link-ok');
                        break;
                    }
                }

                $scanned_ports[] = [
                    'device_name' => $device_name,
                    'device_mac' => '',
                    'port_number' => $port_info['port-id'] ?? $port_name,
                    'port_name' => $port_name,
                    'is_up' => $is_up,
                    'is_wired' => true,
                    'connection' => $is_up ? 'Connected' : 'Disconnected',
                    'connected_to' => $is_up ? 'Link OK' : ''
                ];
            }
            $message = 'MikroTik-Gerät erfolgreich gescannt.';
        } catch (Exception $e) {
            $message = 'MikroTik API-Fehler: ' . $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data_to_save'])) {
    $ports_to_save = json_decode($_POST['data_to_save'], true);

    if ($ports_to_save) {
        $db->beginTransaction();
        try {
            foreach ($ports_to_save as $port) {
                $db->query("SELECT id FROM devices WHERE name = :name");
                $db->bind(':name', $port['device_name']);
                $device = $db->single();
                
                if ($device) {
                    $device_id = $device->id;
                    
                    $db->query("SELECT id FROM switch_ports WHERE device_id = :device_id AND port_number = :port_number");
                    $db->bind(':device_id', $device_id);
                    $db->bind(':port_number', $port['port_number']);
                    $existing_port = $db->single();

                    if ($existing_port) {
                        $db->query("UPDATE switch_ports SET port_name = :port_name, is_up = :is_up, connected_to_name = :connected_to_name WHERE id = :id");
                        $db->bind(':port_name', $port['port_name']);
                        $db->bind(':is_up', $port['is_up']);
                        $db->bind(':connected_to_name', $port['connected_to']);
                        $db->bind(':id', $existing_port->id);
                        $db->execute();
                    } else {
                        $db->query("INSERT INTO switch_ports (device_id, port_number, port_name, is_up, connected_to_name) VALUES (:device_id, :port_number, :port_name, :is_up, :connected_to_name)");
                        $db->bind(':device_id', $device_id);
                        $db->bind(':port_number', $port['port_number']);
                        $db->bind(':port_name', $port['port_name']);
                        $db->bind(':is_up', $port['is_up']);
                        $db->bind(':connected_to_name', $port['connected_to']);
                        $db->execute();
                    }
                }
            }
            $db->commit();
            $message = "Ports erfolgreich in der Datenbank gespeichert!";
        } catch (Exception $e) {
            $db->rollBack();
            $message = "Fehler beim Speichern der Ports: " . $e->getMessage();
        }
    } else {
        $message = "Fehler: Ungültige Daten zum Speichern.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Netzwerk-Scanner</title>
</head>
<body>

<div class="container mt-5">
    <h2>Netzwerk-Scanner</h2>
    <p>Hier kannst du die Portbelegung deiner Switches über die API scannen.</p>

    <?php if ($message): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    UniFi API
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="api_type" value="unifi">
                        <div class="mb-3">
                            <label for="unifi_url" class="form-label">UniFi Controller URL</label>
                            <input type="text" class="form-control" id="unifi_url" name="unifi_url" placeholder="https://unifi.deine-domain.de" required>
                        </div>
                        <div class="mb-3">
                            <label for="unifi_username" class="form-label">Benutzername</label>
                            <input type="text" class="form-control" id="unifi_username" name="unifi_username" required>
                        </div>
                        <div class="mb-3">
                            <label for="unifi_password" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="unifi_password" name="unifi_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">UniFi scannen</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    MikroTik API
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="api_type" value="mikrotik">
                        <div class="mb-3">
                            <label for="mikrotik_ip" class="form-label">MikroTik IP-Adresse</label>
                            <input type="text" class="form-control" id="mikrotik_ip" name="mikrotik_ip" placeholder="192.168.1.1" required>
                        </div>
                        <div class="mb-3">
                            <label for="mikrotik_username" class="form-label">Benutzername</label>
                            <input type="text" class="form-control" id="mikrotik_username" name="mikrotik_username" required>
                        </div>
                        <div class="mb-3">
                            <label for="mikrotik_password" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="mikrotik_password" name="mikrotik_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">MikroTik scannen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($scanned_ports)): ?>
        <div class="mt-5">
            <h3>Gefundene Ports</h3>
            <p>Die folgenden Ports wurden gefunden. Klicke auf "Speichern", um sie in die Datenbank zu übernehmen.</p>
            <form method="post" action="network_scanner.php">
                <input type="hidden" name="data_to_save" value="<?= htmlspecialchars(json_encode($scanned_ports)) ?>">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Gerät</th>
                            <th>Port</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Verbindung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scanned_ports as $port): ?>
                            <tr>
                                <td><?= htmlspecialchars($port['device_name']) ?></td>
                                <td><?= htmlspecialchars($port['port_number']) ?></td>
                                <td><?= htmlspecialchars($port['port_name']) ?></td>
                                <td><?= $port['is_up'] ? 'Online' : 'Offline' ?></td>
                                <td><?= htmlspecialchars($port['connected_to']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-success">Alle Ports speichern</button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
