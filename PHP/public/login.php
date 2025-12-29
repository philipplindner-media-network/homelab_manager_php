<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

$db = new Database();
$auth = new Auth($db);
$message = '';

if ($auth->isLoggedIn()) {
    header('Location: index.php'); // Bereits angemeldet
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $message = '<p style="color: red;">Bitte gib Benutzername und Passwort ein.</p>';
    } else {
        if ($auth->login($username, $password)) {
            header('Location: index.php');
            exit();
        } else {
            $message = '<p style="color: red;">Ungültiger Benutzername oder Passwort.</p>';
        }
    }
}

include 'includes/header.php';
?>

<h2>Anmelden</h2>
<?php echo $message; ?>
<form action="login.php" method="POST">
    <label for="username">Benutzername:</label>
    <input type="text" id="username" name="username" required>

    <label for="password">Passwort:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Anmelden</button>
</form>
<p>Noch kein Konto? <a href="register.php">Hier registrieren</a></p>

<?php include 'includes/footer.php'; ?>
