<?php

class Database {
    private $host = '127.0.0.1';
    private $db_name = 'ecommerce_test';
    private $username = 'root';
    private $password = '';
    private $conn;

    private static $instance = null;

    private function __construct() {
        $this->conn = null;
        try {
            $this->conn = new PDO('mysql:host='.$this->host.';dbname='.$this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
