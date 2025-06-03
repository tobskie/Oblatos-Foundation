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
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true
                )
            );
            $this->conn->exec("SET NAMES utf8mb4");
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new PDOException("Failed to connect to database. Please check your configuration.");
        }
    }

    // Close the database connection
    public function closeConnection() {
        $this->conn = null;
    }

    // Destructor to ensure connection is closed
    public function __destruct() {
        $this->closeConnection();
    }
}
?>