<?php

class User {

    private $conn;

    public function __construct($database){
        $this->conn = $database;
    }

public function register($name, $email, $password, $phone, $address, $status = "pending"){

    $stmt = $this->conn->prepare("
        INSERT INTO users (full_name, email, password, phone, address, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("ssssss", $name, $email, $password, $phone, $address, $status);

    return $stmt->execute();
}



public function login($email, $password){

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){

        $user = $result->fetch_assoc();

        // ✅ BUG1 FIXED: secure password verification
        if(password_verify($password, $user['password'])){
            return $user;
        }
    }

    return false;
}

    public function getUser($id){
        $sql = "SELECT * FROM users WHERE user_id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    public function updateFullProfile($id, $name, $email, $password, $phone, $address) {
        $sql = "UPDATE users SET full_name=?, email=?, password=?, phone=?, address=? WHERE user_id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssi", $name, $email, $password, $phone, $address, $id);
        return $stmt->execute();
    }


    public function upgradeToPro($id) {
        $sql = "UPDATE users SET membership_type = 'pro' WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getTrustScore($user_id){

    $sql = "SELECT COALESCE(ROUND(AVG(rating),1), 5) AS trust_score
            FROM reviews
            WHERE reviewed_user_id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    return $result['trust_score'];
}
    

/*
        public function findByEmail(string $email): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: null;
    }

        public function getAll(string $role = ''): array {
        if ($role) {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC");
            $stmt->bind_param("s", $role);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function countByStatus(string $status): int {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS c FROM users WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['c'];
    }
        public function create(array $data): int {
        $stmt = $this->conn->prepare(
            "INSERT INTO users (full_name, email, password, phone, address, national_id, role, status, referral_code)
            VALUES (?, ?, ?, ?, ?, ?, 'member', 'pending', ?)"
        );
        $stmt->bind_param(
            "sssssss",
            $data['full_name'], $data['email'], $data['password'],
            $data['phone'],     $data['address'], $data['national_id'],
            $data['referral_code']
        );
        $stmt->execute();
        return (int) $this->conn->insert_id;
    }
    */


}