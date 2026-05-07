<?php

class Reservation {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // الميثود اللي بتنفذ عملية الحجز
public function reserve(
    $user_id,
    $tool_id,
    $start_date,
    $end_date,
    $total_price,
    $deposit,
    $rental_type
){

    /* ================= OVERLAP CHECK ================= */

    $check = $this->conn->prepare("

        SELECT reservation_id

        FROM reservations

        WHERE tool_id=?

        AND status IN ('pending','approved','active')

        AND (
            start_date <= ?
            AND end_date >= ?
        )

    ");

    $check->bind_param(
        "iss",
        $tool_id,
        $end_date,
        $start_date
    );

    $check->execute();

    $result = $check->get_result();

    if($result->num_rows > 0){

        return false;

    }

    /* ================= INSERT ================= */

    $sql = "

        INSERT INTO reservations

        (
            user_id,
            tool_id,
            start_date,
            end_date,
            total_price,
            deposit_amount,
            rental_type,
            status,
            payment_status
        )

        VALUES

        (
            ?, ?, ?, ?, ?, ?, ?,
            'pending',
            'pending'
        )

    ";

    $stmt = $this->conn->prepare($sql);

    $stmt->bind_param(

        "iissdds",

        $user_id,
        $tool_id,
        $start_date,
        $end_date,
        $total_price,
        $deposit,
        $rental_type

    );

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