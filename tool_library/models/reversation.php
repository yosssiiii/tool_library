<?php

class Reservation {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // الميثود اللي بتنفذ عملية الحجز
    public function reserve($user_id, $tool_id, $start_date, $end_date, $price_per_hour) {
        
        // 1. حساب السعر الإجمالي (Total Price)
        $date1 = new DateTime($start_date);
        $date2 = new DateTime($end_date);
        $interval = $date1->diff($date2);
        $days = $interval->days + 1; // لضمان احتساب يوم البداية
        
        // حسبة بسيطة: اليوم محسوب بـ 8 ساعات عمل
        $total_price = $days * ($price_per_hour * 8);

        // 2. كود الإدخال في الداتابيز
        $sql = "INSERT INTO reservations (user_id, tool_id, start_date, end_date, total_price, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iissd", $user_id, $tool_id, $start_date, $end_date, $total_price);

        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
}