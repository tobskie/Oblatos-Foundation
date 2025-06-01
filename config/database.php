<?php
// Database configuration
class Database {
    private $host = "localhost";
    private $username = "root";  // Default XAMPP username
    private $password = "";      // Default XAMPP password
    private $database = "oblatos_foundation";
    protected $conn;

    // Database connection
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->conn;
    }
}
?>