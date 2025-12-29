<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeLab Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>HomeLab Manager</h1>
        <nav>
            <ul>
                <li><a href="index.php">Geräteübersicht</a></li>
                <?php if (isset($_SESSION['user_id'])): // Prüfen, ob Benutzer angemeldet ist ?>
                    <li><a href="quick_add.php">Schelleingabe Geräte</a></li>
		    <li><a href="add_device.php">Gerät hinzufügen</a></li>
                    <li><a href="manage_racks.php">Racks verwalten</a></li>
                    <li><a href="manage_cables.php">Kabel verwalten</a></li>
                    <li><a href="manage_patch_panels.php">Patch Paennel verwelung</a></li>
		    <li><a href="network_scanner.php">Netzwerk-Scanner</a></li>
		    <li><a href="manage_storage.php">Speicher</a></li>
                    <li><a href="asset_info.php">Asset Info</a></li>
		    <li>Willkommen, <?php echo htmlspecialchars($_SESSION['username']); ?>!</li>
                    <li><a href="logout.php">Abmelden</a></li>
                <?php else: ?>
                    <li><a href="barcode_scanner.php">Barcod Assitent</a></li>
		    <li><a href="login.php">Anmelden</a></li>
                    <li><a href="register.php">Registrieren</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
