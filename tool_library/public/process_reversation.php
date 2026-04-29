<?php
session_start();
require_once("../config/Database.php");
require_once("../models/Reservation.php"); // استدعاء الكلاس الجديد

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // إعداد الاتصال
    $db = new Database();
    $conn = $db->connect();

    // إنشاء الكائن (Object) من الكلاس
    $reservation = new Reservation($conn);

    // استلام البيانات من الـ POST
    $user_id = $_SESSION['user_id'];
    $tool_id = $_POST['tool_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $price_per_hour = $_POST['price_per_hour'];

    // تنفيذ الميثود
    if ($reservation->reserve($user_id, $tool_id, $start_date, $end_date, $price_per_hour)) {
        echo "<script>
                alert('Your request has been sent to the lender!');
                window.location.href='dashboard.php';
              </script>";
    } else {
        echo "Something went wrong during the reservation.";
    }
} else {
    header("Location: login.php");
}