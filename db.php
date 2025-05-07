<?php
if (!class_exists('Database')) {
    class Database {
        private $host = "localhost";
        private $db_name = "clearance_db"; // Replace with your database name
        private $username = "root";
        private $password = "";
        public $conn;

        public function getConnection() {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);

            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }

            return $this->conn;
        }
    }
}
