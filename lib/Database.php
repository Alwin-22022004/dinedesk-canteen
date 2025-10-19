<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $host = 'localhost';
        $db   = 'canteen_db';
        $user = 'root';
        $pass = '';
        $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $opt  = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $this->pdo = new PDO($dsn, $user, $pass, $opt);
    }

    public static function getInstance() {
        if (self::$instance === null)
            self::$instance = new self();
        return self::$instance;
    }

    public function pdo() {
        return $this->pdo;
    }
}
?>
