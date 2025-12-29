<?php
session_start();

require_once __DIR__ . '/../src/config.php';

// Datenbank-Verbindung herstellen (an deine Konfiguration anpassen)

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) { die("Verbindung fehlgeschlagen: " . $conn->connect_error); }

$message = '';

// Aktuellen Schritt bestimmen
if (!isset($_SESSION['step'])) {
    $_SESSION['step'] = 1;
}

// Navigation und Datenverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next'])) {
        // Logik für Schritt 2: Rack-Anlage verarbeiten
        if ($_SESSION['step'] == 2) {
            if ($_POST['rack_id'] === 'new') {
                $new_rack_name = $_POST['new_rack_name'];
                if (!empty($new_rack_name)) {
                    // Neues Rack anlegen und ID abrufen
                    $stmt = $conn->prepare("INSERT INTO racks (name) VALUES (?)");
                    $stmt->bind_param("s", $new_rack_name);
                    $stmt->execute();
                    $new_rack_id = $stmt->insert_id;
                    $stmt->close();
                    
                    // Die ID des neu angelegten Racks in den POST-Daten speichern
                    $_POST['rack_id'] = $new_rack_id;
                }
            }
        }
        
        // Daten aus dem aktuellen Formular in der Session speichern
        $_SESSION['data'][$_SESSION['step']] = $_POST;
        $_SESSION['step']++;

    } elseif (isset($_POST['back'])) {
        $_SESSION['step']--;
    } elseif (isset($_POST['save'])) {
        // Schritt 4: Alle Daten in die Datenbank schreiben
        // HINWEIS: Hier musst du die SQL-Statements für deine Tabellen einfügen
        
        $step1_data = $_SESSION['data'][1]; // Geräte-Infos
        $step2_data = $_SESSION['data'][2]; // Rack-Infos
        $step3_data = $_SESSION['data'][3]; // Verbindungs-Infos
        
        // Beispiel: Einfügen in die 'devices' Tabelle
        $stmt_device = $conn->prepare("INSERT INTO devices (name, type, model, rack_id) VALUES (?, ?, ?, ?)");
        $stmt_device->bind_param("sssi", 
            $step1_data['name'], 
            $step1_data['type'], 
            $step1_data['model'],
            $step2_data['rack_id']
        );
        $stmt_device->execute();
        $device_id = $stmt_device->insert_id;
        $stmt_device->close();

        // Beispiel: Einfügen in die 'cables' Tabelle
        // $stmt_cable = ...
        
        // Beispiel: Aktualisieren der 'switch_ports' Tabelle
        // $stmt_port = ...

        // Session zurücksetzen, wenn alles gespeichert ist
        session_destroy();
        header("Location: neues_geraet.php?status=success");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Neues Gerät hinzufügen</title>
    <style>
        body { font-family: sans-serif; }
        form { border: 1px solid #ccc; padding: 20px; border-radius: 5px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input[type="text"], input[type="number"], select {
            width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;
        }
    </style>
</head>
<body>
    <h1>Neues Gerät hinzufügen (Schritt <?= $_SESSION['step'] ?>)</h1>

    <form method="post">
        <?php if ($_SESSION['step'] == 1): ?>
            <h2>Basisdaten des Geräts</h2>
            <p>
                <label for="name">Name:</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($_SESSION['data'][1]['name'] ?? '') ?>">
            </p>
            <p>
                <label for="type">Typ:</label>
                <input type="text" name="type" value="<?= htmlspecialchars($_SESSION['data'][1]['type'] ?? '') ?>">
            </p>
            <p>
                <label for="model">Modell:</label>
                <input type="text" name="model" value="<?= htmlspecialchars($_SESSION['data'][1]['model'] ?? '') ?>">
            </p>
            <button type="submit" name="next">Weiter</button>
        
        <?php elseif ($_SESSION['step'] == 2): ?>
            <h2>Rack-Zuweisung</h2>
            <p>
                <label for="rack_select">Rack auswählen:</label>
                <select id="rack_select" name="rack_id" required>
                    <option value="">-- Wähle ein Rack --</option>
                    <option value="new">[Neues Rack anlegen]</option>
                    <?php 
                        $racks = $conn->query("SELECT id, name FROM racks")->fetch_all(MYSQLI_ASSOC);
                        foreach ($racks as $rack) {
                            $selected = ($_SESSION['data'][2]['rack_id'] ?? '') == $rack['id'] ? 'selected' : '';
                            echo '<option value="' . $rack['id'] . '" ' . $selected . '>' . htmlspecialchars($rack['name']) . '</option>';
                        }
                    ?>
                </select>
            </p>
            
            <p id="new_rack_field" style="display:none;">
                <label for="new_rack_name">Name des neuen Racks:</label>
                <input type="text" id="new_rack_name" name="new_rack_name">
            </p>

            <button type="submit" name="back">Zurück</button>
            <button type="submit" name="next">Weiter</button>
        
        <?php elseif ($_SESSION['step'] == 3): ?>
            <h2>Netzwerk-Verbindung</h2>
            <p>
                <label for="switch_id">Switch:</label>
                <select name="switch_id">
                    <option value="">-- Wähle einen Switch --</option>
                    <?php
                        $switches = $conn->query("SELECT id, name FROM devices WHERE type='switch'")->fetch_all(MYSQLI_ASSOC);
                        foreach ($switches as $switch) {
                            $selected = ($_SESSION['data'][3]['switch_id'] ?? '') == $switch['id'] ? 'selected' : '';
                            echo '<option value="' . $switch['id'] . '" ' . $selected . '>' . htmlspecialchars($switch['name']) . '</option>';
                        }
                    ?>
                </select>
            </p>
            <p>
                <label for="port_number">Port am Switch:</label>
                <input type="number" name="port_number" min="1" value="<?= htmlspecialchars($_SESSION['data'][3]['port_number'] ?? '') ?>">
            </p>
            <p>
                <label for="cable_id">Verwendetes Kabel:</label>
                <select name="cable_id">
                    <option value="">-- Wähle ein Kabel --</option>
                    <?php
                        $cables = $conn->query("SELECT id, name FROM cables")->fetch_all(MYSQLI_ASSOC);
                        foreach ($cables as $cable) {
                            $selected = ($_SESSION['data'][3]['cable_id'] ?? '') == $cable['id'] ? 'selected' : '';
                            echo '<option value="' . $cable['id'] . '" ' . $selected . '>' . htmlspecialchars($cable['name']) . '</option>';
                        }
                    ?>
                </select>
            </p>
            <button type="submit" name="back">Zurück</button>
            <button type="submit" name="next">Weiter</button>

        <?php elseif ($_SESSION['step'] == 4): ?>
            <h2>Zusammenfassung</h2>
            <h3>Geräte-Details:</h3>
            <p>Name: <b><?= htmlspecialchars($_SESSION['data'][1]['name']) ?></b></p>
            <p>Typ: <b><?= htmlspecialchars($_SESSION['data'][1]['type']) ?></b></p>
            <p>Modell: <b><?= htmlspecialchars($_SESSION['data'][1]['model']) ?></b></p>
            <h3>Rack-Zuweisung:</h3>
            <p>Rack-ID: <b><?= htmlspecialchars($_SESSION['data'][2]['rack_id']) ?></b></p>
            <h3>Verbindung:</h3>
            <p>Switch-ID: <b><?= htmlspecialchars($_SESSION['data'][3]['switch_id'] ?? 'N/A') ?></b></p>
            <p>Port: <b><?= htmlspecialchars($_SESSION['data'][3]['port_number'] ?? 'N/A') ?></b></p>
            <p>Kabel-ID: <b><?= htmlspecialchars($_SESSION['data'][3]['cable_id'] ?? 'N/A') ?></b></p>
            <button type="submit" name="back">Zurück</button>
            <button type="submit" name="save">Speichern & Beenden</button>

        <?php endif; ?>
    </form>
    <script>
        // JavaScript, um das Feld einzublenden
        document.getElementById('rack_select').addEventListener('change', function() {
            var newRackField = document.getElementById('new_rack_field');
            var newRackInput = document.getElementById('new_rack_name');
            if (this.value === 'new') {
                newRackField.style.display = 'block';
                newRackInput.setAttribute('required', 'required');
            } else {
                newRackField.style.display = 'none';
                newRackInput.removeAttribute('required');
                newRackInput.value = '';
            }
        });

        // Beim Laden der Seite prüfen, ob das Feld sichtbar sein muss
        if (document.getElementById('rack_select').value === 'new') {
            document.getElementById('new_rack_field').style.display = 'block';
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
