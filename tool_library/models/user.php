<?php

class User {

    private $conn;

    public function __construct($database){
        $this->conn = $database;
    }

    public function register($name, $email, $password, $phone, $address){
        $sql = "INSERT INTO users (full_name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $password, $phone, $address);
        return $stmt->execute();
    }

    public function login($email, $password){
        $sql = "SELECT * FROM users WHERE email=? AND password=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUser($id){
        $sql = "SELECT * FROM users WHERE user_id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // ميثود تحديث البيانات الشاملة
    public function updateFullProfile($id, $name, $email, $password, $phone, $address) {
        $sql = "UPDATE users SET full_name=?, email=?, password=?, phone=?, address=? WHERE user_id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssi", $name, $email, $password, $phone, $address, $id);
        return $stmt->execute();
    }

    // ميثود الترقية لـ Pro
    public function upgradeToPro($id) {
        $sql = "UPDATE users SET membership_type = 'pro' WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}