<?php
require_once __DIR__ . '/../lib/Database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->pdo();
    }

    public function register($name, $email, $password, $phone = null) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $email, $hash, $phone]);
    }

    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
?>
