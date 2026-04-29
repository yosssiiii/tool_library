<?php

class Database {

    private $host = "localhost";
    private $dbname = "tool_library";
    private $username = "root";
    private $password = "";

    public $conn;

    public function connect() {

        $this->conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->dbname,
            
        );

        if ($this->conn->connect_error) {
            die("Database Connection Failed");
        }

        return $this->conn;
    }
}
?>