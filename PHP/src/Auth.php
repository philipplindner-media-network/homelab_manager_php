<?php
class Auth {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register($username, $password, $email = null) {
        // Passwort hashen
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $this->db->query('INSERT INTO users (username, password_hash, email) VALUES (:username, :password_hash, :email)');
        $this->db->bind(':username', $username);
        $this->db->bind(':password_hash', $password_hash);
        $this->db->bind(':email', $email);

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            // Z.B. bei doppeltem Benutzernamen/E-Mail
            return false;
        }
    }

    public function login($username, $password) {
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $user = $this->db->single();

        if ($user && password_verify($password, $user->password_hash)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            return true;
        }
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function logout() {
        session_unset();
        session_destroy();
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return ['id' => $_SESSION['user_id'], 'username' => $_SESSION['username']];
        }
        return null;
    }
}
?>
