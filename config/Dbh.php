<?php

class Dbh {
    // Holds the single instance of the class
    private static $instance = null;

    // Holds the PDO connection
    private $connection;

    // Private constructor prevents direct instantiation
    private function __construct() {
        try {
            $host = 'localhost';
            $dbname = 'ooplogin';
            $username = 'root';
            $password = '';
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

            $this->connection = new PDO($dsn, $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Public method to get the singleton instance
    public static function getInstance(): Dbh {
        if (self::$instance === null) {
            self::$instance = new Dbh();
        }
        return self::$instance;
    }

    // Method to access the PDO connection
    public function getConnection(): PDO {
        return $this->connection;
    }
}
