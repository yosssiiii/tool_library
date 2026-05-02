<?php

class Reservation {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // الميثود اللي بتنفذ عملية الحجز
public function reserve($user_id, $tool_id, $start_date, $end_date, $price_per_day) {
    
    // حساب عدد الأيام
    $date1 = new DateTime($start_date);
    $date2 = new DateTime($end_date);
    $days = $date1->diff($date2)->days + 1;

    // الحسابات
    $total_price = $days * $price_per_day;
    $deposit = $price_per_day * 2; // تأمين يومين

    // INSERT
    $sql = "INSERT INTO reservations 
    (user_id, tool_id, start_date, end_date, total_price, deposit_amount, status, payment_status)
    VALUES (?, ?, ?, ?, ?, ?, 'pending','pending')";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iissdd", $user_id, $tool_id, $start_date, $end_date, $total_price, $deposit);

    return $stmt->execute();
}



public function getUserRentals($user_id) {
        $sql = "SELECT reservations.*, tools.tool_name, tools.photo, users.full_name AS owner_name
                FROM reservations
                JOIN tools ON reservations.tool_id = tools.tool_id
                JOIN users ON tools.owner_id = users.user_id
                WHERE reservations.user_id = ?
                ORDER BY reservations.reservation_id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        return $stmt->get_result();
}

}