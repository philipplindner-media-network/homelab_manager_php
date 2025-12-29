><?php
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
    $password_confirm = trim($_POST['password_confirm']);
    $email = trim($_POST['email']);

    if (empty($username) || empty($password) || empty($password_confirm)) {
        $message = '<p style="error-message">Bitte fülle alle Pflichtfelder aus.</p>';
    } elseif ($password !== $password_confirm) {
        $message = '<p style="error-message">Passwörter stimmen nicht überein.</p>';
    } elseif (strlen($password) < 6) {
        $message = '<p style="error-message">Passwort muss mindestens 6 Zeichen lang sein.</p>';
    } else {
        if ($auth->register($username, $password, $email)) {
            $message = '<p style="success-message">Registrierung erfolgreich! Du kannst dich jetzt <a href="login.php">anmelden</a>.</p>';
        } else {
            $message = '<p style="error-message">Registrierung fehlgeschlagen. Benutzername oder E-Mail existiert möglicherweise bereits.</p>';
        }
    }
}

include 'includes/header.php';
?>

<h2>Registrieren</h2>
<?php echo $message; ?>
<form action="register.php" method="POST">
    <label for="username">Benutzername:</label>
    <input type="text" id="username" name="username" required>

    <label for="email">E-Mail (optional):</label>
    <input type="email" id="email" name="email">

    <label for="password">Passwort:</label>
    <input type="password" id="password" name="password" required>

    <label for="password_confirm">Passwort bestätigen:</label>
    <input type="password" id="password_confirm" name="password_confirm" required>

    <button type="submit">Registrieren</button>
</form>
<p>Bereits registriert? <a href="login.php">Hier anmelden</a></p>

<?php include 'includes/footer.php'; ?>
